<?php
/**
 * Gyors session fixation védelem ellenőrzés
 *
 * Ez a script gyorsan ellenőrzi, hogy a login.php tartalmazza-e
 * a session_regenerate_id() hívást.
 */

$login_file = __DIR__ . '/login.php';
$content = file_get_contents($login_file);

// Keressük a session_regenerate_id hívást
$has_regenerate = strpos($content, 'session_regenerate_id') !== false;
$has_true_param = strpos($content, 'session_regenerate_id(true)') !== false;
$has_comment = strpos($content, 'Session fixation') !== false || strpos($content, 'CWE-384') !== false;

// Ellenőrizzük a helyes sorrendet
$password_verify_pos = strpos($content, 'password_verify');
$regenerate_pos = strpos($content, 'session_regenerate_id');
$session_user_id_pos = strpos($content, '$_SESSION[\'user_id\']');

$correct_order = ($password_verify_pos !== false &&
                  $regenerate_pos !== false &&
                  $session_user_id_pos !== false &&
                  $password_verify_pos < $regenerate_pos &&
                  $regenerate_pos < $session_user_id_pos);

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Fixation Védelem Ellenőrzés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .check-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 2rem;
        }
        .check-item {
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .check-pass {
            background: #d4edda;
            border-color: #28a745;
        }
        .check-fail {
            background: #f8d7da;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="check-card">
                    <h2 class="mb-4">
                        <i class="bi bi-shield-check"></i> Session Fixation Védelem Ellenőrzés
                    </h2>

                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle"></i> Fájl:</strong> <?php echo $login_file; ?>
                    </div>

                    <h4 class="mt-4 mb-3">Ellenőrzési eredmények:</h4>

                    <div class="check-item <?php echo $has_regenerate ? 'check-pass' : 'check-fail'; ?>">
                        <?php if ($has_regenerate): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>session_regenerate_id() megtalálva</strong>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger"></i>
                            <strong>session_regenerate_id() HIÁNYZIK!</strong>
                        <?php endif; ?>
                    </div>

                    <div class="check-item <?php echo $has_true_param ? 'check-pass' : 'check-fail'; ?>">
                        <?php if ($has_true_param): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>session_regenerate_id(true) - helyes paraméter</strong>
                            <br><small class="text-muted">A régi session fájl törlésre kerül</small>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger"></i>
                            <strong>session_regenerate_id(true) - paraméter HIÁNYZIK!</strong>
                            <br><small class="text-danger">A régi session fájl nem törlődik!</small>
                        <?php endif; ?>
                    </div>

                    <div class="check-item <?php echo $has_comment ? 'check-pass' : 'check-fail'; ?>">
                        <?php if ($has_comment): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>Biztonsági megjegyzés található</strong>
                            <br><small class="text-muted">CWE-384 vagy Session fixation megjegyzés</small>
                        <?php else: ?>
                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                            <strong>Biztonsági megjegyzés hiányzik</strong>
                        <?php endif; ?>
                    </div>

                    <div class="check-item <?php echo $correct_order ? 'check-pass' : 'check-fail'; ?>">
                        <?php if ($correct_order): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong>Helyes sorrend</strong>
                            <br><small class="text-muted">
                                1. password_verify() → 2. session_regenerate_id() → 3. $_SESSION beállítása
                            </small>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger"></i>
                            <strong>HIBÁS SORREND!</strong>
                            <br><small class="text-danger">
                                A session_regenerate_id()-t a session változók beállítása ELŐTT kell meghívni!
                            </small>
                        <?php endif; ?>
                    </div>

                    <?php
                    $all_checks_pass = $has_regenerate && $has_true_param && $correct_order;
                    ?>

                    <div class="alert alert-<?php echo $all_checks_pass ? 'success' : 'danger'; ?> mt-4">
                        <h5>
                            <?php if ($all_checks_pass): ?>
                                <i class="bi bi-check-circle-fill"></i> Védelem megfelelően implementálva!
                            <?php else: ?>
                                <i class="bi bi-x-circle-fill"></i> Védelem HIÁNYOS vagy HIBÁS!
                            <?php endif; ?>
                        </h5>
                        <?php if ($all_checks_pass): ?>
                            <p class="mb-0">
                                A login.php megfelelően védekezik a Session Fixation támadás ellen.
                                A session_regenerate_id(true) meghívásra kerül sikeres autentikáció után.
                            </p>
                        <?php else: ?>
                            <p class="mb-0">
                                A login.php NEM védekezik megfelelően a Session Fixation támadás ellen!
                                Javítsd a fenti hibákat!
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-code-square"></i> Helyes implementáció</h5>
                        </div>
                        <div class="card-body">
                            <pre class="bg-light p-3 rounded mb-0"><code>if ($user && password_verify($password, $user['password'])) {
    // Session fixation elleni védelem
    // CWE-384 mitigation
    session_regenerate_id(true);

    // Session változók beállítása
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    // ...
}</code></pre>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <a href="test_session_fixation.php" class="btn btn-primary">
                            <i class="bi bi-clipboard-check"></i> Részletes teszt
                        </a>
                        <a href="login.php" class="btn btn-success">
                            <i class="bi bi-box-arrow-in-right"></i> Bejelentkezés
                        </a>
                        <a href="docs/security/SESSION_FIXATION_FIX.md" class="btn btn-info" target="_blank">
                            <i class="bi bi-file-text"></i> Dokumentáció
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
