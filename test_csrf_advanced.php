<?php
/**
 * CSRF Token Védelem - Átfogó Tesztelő Script
 * Dátum: 2025-11-10
 *
 * Ez a script végigfuttatja az összes CSRF védelmi tesztet
 */

require_once __DIR__ . '/config.php';

// HTML kimenet helyett terminál kimenet
if (php_sapi_name() === 'cli') {
    define('CLI_MODE', true);
} else {
    define('CLI_MODE', false);
    header('Content-Type: text/html; charset=utf-8');
}

$testResults = [];
$testLog = [];

/**
 * Teszt futtatása és eredmény naplózása
 */
function runTest($testNumber, $testName, $callback) {
    global $testResults, $testLog;

    $testLog[] = "\n" . str_repeat("=", 80);
    $testLog[] = "[$testNumber] $testName";
    $testLog[] = str_repeat("=", 80);

    try {
        $result = $callback();
        $testResults[$testNumber] = [
            'name' => $testName,
            'passed' => $result['passed'],
            'message' => $result['message'],
            'details' => $result['details'] ?? null
        ];

        $status = $result['passed'] ? '✓ SIKERES' : '✗ SIKERTELEN';
        $testLog[] = "$status: {$result['message']}";

        if (isset($result['details'])) {
            $testLog[] = "Részletek: {$result['details']}";
        }

        return $result['passed'];
    } catch (Exception $e) {
        $testResults[$testNumber] = [
            'name' => $testName,
            'passed' => false,
            'message' => 'Hiba történt: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];

        $testLog[] = "✗ HIBA: " . $e->getMessage();
        return false;
    }
}

// ============================================
// POZITÍV TESZTEK
// ============================================

// 1. Token generálás teszt
runTest(1, "Token generálás", function() {
    $token = generateCsrfToken();

    if (empty($token)) {
        return [
            'passed' => false,
            'message' => 'Token üres'
        ];
    }

    if (strlen($token) !== 64) {
        return [
            'passed' => false,
            'message' => 'Token hossza nem megfelelő',
            'details' => "Várt: 64, Kapott: " . strlen($token)
        ];
    }

    return [
        'passed' => true,
        'message' => 'Token sikeresen generálva',
        'details' => "Token hossza: " . strlen($token) . " karakter"
    ];
});

// 2. Token perzisztencia teszt
runTest(2, "Token perzisztencia (ugyanaz a token többszöri hívásra)", function() {
    $token1 = generateCsrfToken();
    $token2 = generateCsrfToken();

    if ($token1 !== $token2) {
        return [
            'passed' => false,
            'message' => 'Token nem perzisztens, mindig új token generálódik'
        ];
    }

    return [
        'passed' => true,
        'message' => 'Token perzisztens, ugyanaz a token jön vissza'
    ];
});

// 3. Érvényes token validáció
runTest(3, "Érvényes token elfogadása", function() {
    $validToken = getCsrfToken();
    $isValid = validateCsrfToken($validToken);

    if (!$isValid) {
        return [
            'passed' => false,
            'message' => 'Érvényes token elutasítva (HIBÁS MŰKÖDÉS!)'
        ];
    }

    return [
        'passed' => true,
        'message' => 'Érvényes token helyesen elfogadva'
    ];
});

// ============================================
// NEGATÍV TESZTEK
// ============================================

// 4. Érvénytelen token elutasítása
runTest(4, "Érvénytelen token elutasítása", function() {
    $invalidToken = "invalid_fake_token_12345";
    $isValid = validateCsrfToken($invalidToken);

    if ($isValid) {
        return [
            'passed' => false,
            'message' => 'Érvénytelen token ELFOGADVA (BIZTONSÁGI RÉS!)'
        ];
    }

    return [
        'passed' => true,
        'message' => 'Érvénytelen token helyesen elutasítva'
    ];
});

// 5. Üres token elutasítása
runTest(5, "Üres token elutasítása", function() {
    $isValid = validateCsrfToken('');

    if ($isValid) {
        return [
            'passed' => false,
            'message' => 'Üres token ELFOGADVA (BIZTONSÁGI RÉS!)'
        ];
    }

    return [
        'passed' => true,
        'message' => 'Üres token helyesen elutasítva'
    ];
});

// 6. NULL token elutasítása
runTest(6, "NULL token elutasítása", function() {
    $isValid = validateCsrfToken(null);

    if ($isValid) {
        return [
            'passed' => false,
            'message' => 'NULL token ELFOGADVA (BIZTONSÁGI RÉS!)'
        ];
    }

    return [
        'passed' => true,
        'message' => 'NULL token helyesen elutasítva'
    ];
});

// 7. Módosított token elutasítása
runTest(7, "Módosított token elutasítása", function() {
    $originalToken = getCsrfToken();
    $modifiedToken = substr($originalToken, 0, -1) . 'X';
    $isValid = validateCsrfToken($modifiedToken);

    if ($isValid) {
        return [
            'passed' => false,
            'message' => 'Módosított token ELFOGADVA (BIZTONSÁGI RÉS!)'
        ];
    }

    return [
        'passed' => true,
        'message' => 'Módosított token helyesen elutasítva'
    ];
});

// ============================================
// BIZTONSÁGI TESZTEK
// ============================================

// 8. hash_equals() használat (Timing Attack védelem)
runTest(8, "hash_equals() használat - Timing Attack védelem", function() {
    $configContent = file_get_contents(__DIR__ . '/config.php');

    if (strpos($configContent, 'hash_equals') === false) {
        return [
            'passed' => false,
            'message' => 'hash_equals() NEM használva (TIMING ATTACK SEBEZHETŐSÉG!)'
        ];
    }

    return [
        'passed' => true,
        'message' => 'hash_equals() használva (Timing Attack védelem működik)',
        'details' => 'A hash_equals() konstans időben végzi az összehasonlítást'
    ];
});

// 9. Token entrópia teszt
runTest(9, "Token entrópia és randomság", function() {
    $token = getCsrfToken();
    $uniqueChars = count(array_unique(str_split($token)));

    if ($uniqueChars < 10) {
        return [
            'passed' => false,
            'message' => 'Token entrópiája alacsony',
            'details' => "Különböző karakterek: $uniqueChars (minimum 10 ajánlott)"
        ];
    }

    // Ellenőrizzük, hogy hexadecimális-e
    if (!ctype_xdigit($token)) {
        return [
            'passed' => false,
            'message' => 'Token nem hexadecimális formátumú'
        ];
    }

    return [
        'passed' => true,
        'message' => 'Token entrópiája megfelelő',
        'details' => "Különböző karakterek: $uniqueChars, Formátum: hexadecimális"
    ];
});

// 10. Token uniqueness (különböző session-ök)
runTest(10, "Token uniqueness - új session új token", function() {
    $firstToken = $_SESSION['csrf_token'];

    // Session token törlése és újragenerálás
    unset($_SESSION['csrf_token']);
    $secondToken = generateCsrfToken();

    // Token visszaállítása
    $_SESSION['csrf_token'] = $firstToken;

    if ($firstToken === $secondToken) {
        return [
            'passed' => false,
            'message' => 'Azonos token generálódott (BIZTONSÁGI PROBLÉMA!)'
        ];
    }

    return [
        'passed' => true,
        'message' => 'Új session új tokent generál (helyes működés)',
        'details' => 'Első token: ' . substr($firstToken, 0, 16) . '... | Második token: ' . substr($secondToken, 0, 16) . '...'
    ];
});

// ============================================
// IMPLEMENTÁCIÓS TESZTEK
// ============================================

// 11. CSRF védelem edit.php-ban
runTest(11, "CSRF védelem implementálva edit.php-ban", function() {
    $editContent = file_get_contents(__DIR__ . '/worksheets/edit.php');

    $hasValidation = strpos($editContent, 'validateCsrfToken') !== false;
    $hasHiddenField = strpos($editContent, 'getCsrfToken') !== false;

    if (!$hasValidation || !$hasHiddenField) {
        return [
            'passed' => false,
            'message' => 'CSRF védelem hiányos az edit.php-ban'
        ];
    }

    return [
        'passed' => true,
        'message' => 'CSRF védelem implementálva az edit.php-ban',
        'details' => 'Token validáció és hidden field is megtalálható'
    ];
});

// 12. CSRF védelem delete.php-ban
runTest(12, "CSRF védelem implementálva delete.php-ban", function() {
    $deleteContent = file_get_contents(__DIR__ . '/worksheets/delete.php');

    $hasValidation = strpos($deleteContent, 'validateCsrfToken') !== false;

    if (!$hasValidation) {
        return [
            'passed' => false,
            'message' => 'CSRF védelem hiányzik a delete.php-ból (KRITIKUS!)'
        ];
    }

    return [
        'passed' => true,
        'message' => 'CSRF védelem implementálva a delete.php-ban'
    ];
});

// 13. CSRF védelem add.php-ban
runTest(13, "CSRF védelem implementálva add.php-ban", function() {
    $addContent = file_get_contents(__DIR__ . '/worksheets/add.php');

    $hasValidation = strpos($addContent, 'validateCsrfToken') !== false;
    $hasHiddenField = strpos($addContent, 'getCsrfToken') !== false;

    if (!$hasValidation || !$hasHiddenField) {
        return [
            'passed' => false,
            'message' => 'CSRF védelem hiányos az add.php-ban'
        ];
    }

    return [
        'passed' => true,
        'message' => 'CSRF védelem implementálva az add.php-ban',
        'details' => 'Token validáció és hidden field is megtalálható'
    ];
});

// ============================================
// EREDMÉNYEK KIÉRTÉKELÉSE
// ============================================

$totalTests = count($testResults);
$passedTests = 0;
$failedTests = 0;

foreach ($testResults as $result) {
    if ($result['passed']) {
        $passedTests++;
    } else {
        $failedTests++;
    }
}

$successRate = round(($passedTests / $totalTests) * 100, 2);

// ============================================
// KIMENET GENERÁLÁSA
// ============================================

if (CLI_MODE) {
    // Terminál kimenet
    foreach ($testLog as $line) {
        echo $line . "\n";
    }

    echo "\n" . str_repeat("=", 80) . "\n";
    echo "TESZT ÖSSZEGZÉS\n";
    echo str_repeat("=", 80) . "\n";
    echo "Összes teszt: $totalTests\n";
    echo "Sikeres tesztek: $passedTests\n";
    echo "Sikertelen tesztek: $failedTests\n";
    echo "Sikerességi arány: $successRate%\n";
    echo str_repeat("=", 80) . "\n";

    if ($passedTests === $totalTests) {
        echo "\n✓✓✓ MINDEN TESZT SIKERES - CSRF VÉDELEM MŰKÖDIK ✓✓✓\n\n";
    } else {
        echo "\n✗✗✗ VAN SIKERTELEN TESZT - TOVÁBBI VIZSGÁLAT SZÜKSÉGES ✗✗✗\n\n";
    }
} else {
    // HTML kimenet
    ?>
    <!DOCTYPE html>
    <html lang="hu">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CSRF Token Védelem - Teszt Eredmények</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <style>
            body {
                background-color: #f8f9fa;
                padding: 30px 0;
            }
            .test-passed {
                background-color: #d4edda;
                border-left: 4px solid #28a745;
            }
            .test-failed {
                background-color: #f8d7da;
                border-left: 4px solid #dc3545;
            }
            .summary-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="mb-4"><i class="bi bi-shield-check"></i> CSRF Token Védelem - Teszt Eredmények</h1>
            <p class="lead">Tesztelés dátuma: <?php echo date('Y-m-d H:i:s'); ?></p>

            <!-- Összegzés -->
            <div class="card summary-card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Teszt Összegzés</h3>
                    <div class="row text-center mt-4">
                        <div class="col-md-3">
                            <h2><?php echo $totalTests; ?></h2>
                            <p>Összes teszt</p>
                        </div>
                        <div class="col-md-3">
                            <h2><?php echo $passedTests; ?></h2>
                            <p>Sikeres</p>
                        </div>
                        <div class="col-md-3">
                            <h2><?php echo $failedTests; ?></h2>
                            <p>Sikertelen</p>
                        </div>
                        <div class="col-md-3">
                            <h2><?php echo $successRate; ?>%</h2>
                            <p>Sikerességi arány</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tesztek részletei -->
            <h3 class="mb-3">Teszt Részletek</h3>
            <?php foreach ($testResults as $number => $result): ?>
                <div class="card mb-3 <?php echo $result['passed'] ? 'test-passed' : 'test-failed'; ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php if ($result['passed']): ?>
                                <i class="bi bi-check-circle-fill text-success"></i>
                            <?php else: ?>
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            <?php endif; ?>
                            [<?php echo $number; ?>] <?php echo htmlspecialchars($result['name']); ?>
                        </h5>
                        <p class="card-text mb-0"><?php echo htmlspecialchars($result['message']); ?></p>
                        <?php if (!empty($result['details'])): ?>
                            <p class="card-text mt-2">
                                <small class="text-muted"><?php echo htmlspecialchars($result['details']); ?></small>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Végső státusz -->
            <div class="alert <?php echo ($passedTests === $totalTests) ? 'alert-success' : 'alert-warning'; ?> mt-4">
                <?php if ($passedTests === $totalTests): ?>
                    <h4 class="alert-heading"><i class="bi bi-check-circle"></i> Minden teszt sikeres!</h4>
                    <p>A CSRF token védelem megfelelően működik. Az alkalmazás védett a Cross-Site Request Forgery támadások ellen.</p>
                <?php else: ?>
                    <h4 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Van sikertelen teszt!</h4>
                    <p>Kérjük, javítsa ki a fent jelzett problémákat a biztonságos működés érdekében.</p>
                <?php endif; ?>
            </div>

            <div class="mt-4">
                <a href="index.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Vissza a főoldalra</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// Tesztelési eredmények mentése JSON formátumban
$jsonReport = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => $totalTests,
    'passed_tests' => $passedTests,
    'failed_tests' => $failedTests,
    'success_rate' => $successRate,
    'tests' => $testResults
];

file_put_contents(__DIR__ . '/csrf_test_results.json', json_encode($jsonReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
?>
