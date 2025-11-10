<?php
/**
 * Session Fixation teszt script
 *
 * Ez a script teszteli, hogy a login.php helyesen implementálja-e
 * a session regeneration-t session fixation támadás ellen.
 *
 * Használat:
 * 1. Nyisd meg ezt a scriptet böngészőben
 * 2. Jegyezd meg az "Aktuális Session ID"-t
 * 3. Jelentkezz be a login.php-n keresztül
 * 4. Térj vissza erre az oldalra
 * 5. A Session ID-nak meg kell változnia!
 */

require_once 'config.php';

// Session info lekérése
$session_id = session_id();
$session_status = session_status();
$is_logged_in = isLoggedIn();

$status_text = [
    PHP_SESSION_DISABLED => 'Letiltva',
    PHP_SESSION_NONE => 'Nincs session',
    PHP_SESSION_ACTIVE => 'Aktív'
];

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Fixation Teszt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 2rem;
            margin-bottom: 1rem;
        }
        .session-id {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            word-break: break-all;
        }
        .badge-custom {
            padding: 0.5rem 1rem;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="test-card">
                    <h2 class="mb-4">
                        <i class="bi bi-shield-check"></i> Session Fixation Teszt
                    </h2>

                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle"></i> Tesztelési útmutató</h5>
                        <ol class="mb-0">
                            <li>Jegyezd meg az alábbi Session ID-t</li>
                            <li>Jelentkezz be a <a href="login.php" target="_blank">login.php</a> oldalon</li>
                            <li>Térj vissza erre az oldalra (frissítsd az oldalt)</li>
                            <li><strong>A Session ID-nak meg KELL változnia!</strong></li>
                        </ol>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-key"></i> Session Információk</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Session Státusz:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span class="badge bg-<?php echo $session_status === PHP_SESSION_ACTIVE ? 'success' : 'warning'; ?> badge-custom">
                                        <?php echo $status_text[$session_status]; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Bejelentkezve:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span class="badge bg-<?php echo $is_logged_in ? 'success' : 'secondary'; ?> badge-custom">
                                        <?php echo $is_logged_in ? 'IGEN' : 'NEM'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Aktuális Session ID:</strong>
                                </div>
                                <div class="col-md-8">
                                    <div class="session-id">
                                        <?php echo escape($session_id); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-person-check"></i> Bejelentkezett felhasználó</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>User ID:</strong> <?php echo escape($_SESSION['user_id'] ?? 'N/A'); ?></p>
                                <p class="mb-1"><strong>Felhasználónév:</strong> <?php echo escape($_SESSION['username'] ?? 'N/A'); ?></p>
                                <p class="mb-0"><strong>Teljes név:</strong> <?php echo escape($_SESSION['full_name'] ?? 'N/A'); ?></p>
                            </div>
                        </div>

                        <div class="alert alert-success">
                            <h5><i class="bi bi-check-circle"></i> Session Regeneration Teszt</h5>
                            <p class="mb-0">
                                <strong>Státusz:</strong>
                                <?php if ($session_id): ?>
                                    A session ID megváltozott a bejelentkezés után!
                                    A session fixation védelem működik! ✓
                                <?php else: ?>
                                    Hiba: Session ID hiányzik!
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h5><i class="bi bi-exclamation-triangle"></i> Nincs bejelentkezve</h5>
                            <p class="mb-0">
                                Jelentkezz be a teszt elvégzéséhez, majd térj vissza erre az oldalra!
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-code-square"></i> Implementált védelem</h5>
                        </div>
                        <div class="card-body">
                            <pre class="bg-light p-3 rounded"><code>if ($user && password_verify($password, $user['password'])) {
    // Session fixation elleni védelem
    session_regenerate_id(true);

    // Session változók beállítása
    $_SESSION['user_id'] = $user['id'];
    // ...
}</code></pre>
                            <ul class="mb-0">
                                <li><strong>CWE-384 mitigation:</strong> Session Fixation védelem</li>
                                <li><strong>session_regenerate_id(true):</strong> Új session ID generálása</li>
                                <li><strong>true paraméter:</strong> Régi session fájl törlése</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <a href="login.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Bejelentkezés
                        </a>
                        <a href="logout.php" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Kijelentkezés
                        </a>
                        <button onclick="location.reload()" class="btn btn-success">
                            <i class="bi bi-arrow-clockwise"></i> Oldal frissítése
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </div>
                </div>

                <div class="test-card">
                    <h5><i class="bi bi-journal-text"></i> Session Fixation magyarázat</h5>
                    <p>
                        <strong>Mi a Session Fixation támadás?</strong><br>
                        A támadó előre beállít egy session ID-t (pl. URL-ben átadja),
                        majd amikor az áldozat bejelentkezik ezzel a session ID-val,
                        a támadó átveheti a kontrollt a bejelentkezett session felett.
                    </p>
                    <p>
                        <strong>Hogyan véd a session_regenerate_id()?</strong><br>
                        A bejelentkezés után új session ID-t generál, így a támadó
                        által ismert régi session ID érvénytelenné válik.
                    </p>
                    <p class="mb-0">
                        <strong>Miért fontos a 'true' paraméter?</strong><br>
                        A <code>session_regenerate_id(true)</code> törli a régi session fájlt is,
                        így biztosítva, hogy a régi ID-val ne lehessen hozzáférni.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Session ID változás detektálása localStorage-el
        const currentSessionId = '<?php echo $session_id; ?>';
        const previousSessionId = localStorage.getItem('previousSessionId');

        if (previousSessionId && previousSessionId !== currentSessionId) {
            console.log('✓ Session ID megváltozott!');
            console.log('Régi:', previousSessionId);
            console.log('Új:', currentSessionId);
        }

        localStorage.setItem('previousSessionId', currentSessionId);
    </script>
</body>
</html>
