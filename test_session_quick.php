<?php
/**
 * Quick Session Timeout Test
 *
 * FIGYELEM: Ez egy TESZTELÉSI CÉLÚ timeout (60 sec) demonstrációhoz!
 * PRODUCTION-ban SOHA ne használd ezt!
 */

require_once 'config.php';

// Ha nincs session változó, inicializáljuk
if (!isset($_SESSION['test_start_time'])) {
    $_SESSION['test_start_time'] = time();
    $_SESSION['test_last_activity'] = time();
}

$elapsed = time() - $_SESSION['test_start_time'];
$since_activity = time() - $_SESSION['test_last_activity'];

// 60 másodperces timeout DEMO
if ($since_activity > 60) {
    $timeout_msg = "TIMEOUT! - 60 másodperc telt el aktivitás nélkül.";
} else {
    $timeout_msg = "Session aktív - " . (60 - $since_activity) . " másodperc van még timeout-ig.";
}

// Aktivitás frissítése
$_SESSION['test_last_activity'] = time();

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Session Timeout Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 2rem;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .countdown {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin: 2rem 0;
        }
        .status-active {
            color: #28a745;
        }
        .status-timeout {
            color: #dc3545;
        }
    </style>
    <script>
        // Auto-refresh countdown
        let countdown = <?php echo max(0, 60 - $since_activity); ?>;

        setInterval(function() {
            if (countdown > 0) {
                countdown--;
                document.getElementById('countdown').textContent = countdown;

                if (countdown <= 10) {
                    document.getElementById('countdown').classList.add('text-danger');
                }
            } else {
                document.getElementById('status-message').innerHTML =
                    '<div class="alert alert-danger"><strong>TIMEOUT!</strong> Frissítsd az oldalt a teszt újraindításához.</div>';
            }
        }, 1000);
    </script>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Quick Session Timeout Test (60 sec)</h1>

        <div class="test-box">
            <h2>Timeout Countdown</h2>
            <div class="countdown">
                <span id="countdown"><?php echo max(0, 60 - $since_activity); ?></span>
                <small>másodperc</small>
            </div>
            <div id="status-message" class="text-center">
                <?php if ($since_activity > 60): ?>
                    <div class="alert alert-danger">
                        <strong>TIMEOUT!</strong> A session lejárt 60 másodperc inaktivitás után.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>Aktív session</strong> - <?php echo 60 - $since_activity; ?> másodperc van még.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="test-box">
            <h3>Session Info</h3>
            <table class="table">
                <tr>
                    <th>Teszt kezdete:</th>
                    <td><?php echo date('Y-m-d H:i:s', $_SESSION['test_start_time']); ?></td>
                </tr>
                <tr>
                    <th>Utolsó aktivitás:</th>
                    <td><?php echo date('Y-m-d H:i:s', $_SESSION['test_last_activity']); ?></td>
                </tr>
                <tr>
                    <th>Eltelt idő (összesen):</th>
                    <td><?php echo $elapsed; ?> sec</td>
                </tr>
                <tr>
                    <th>Aktivitás óta eltelt:</th>
                    <td class="<?php echo $since_activity > 60 ? 'status-timeout' : 'status-active'; ?>">
                        <strong><?php echo $since_activity; ?> sec</strong>
                    </td>
                </tr>
                <tr>
                    <th>Státusz:</th>
                    <td><?php echo $timeout_msg; ?></td>
                </tr>
            </table>
        </div>

        <div class="test-box">
            <h3>Teszt Útmutató</h3>
            <ol>
                <li><strong>NE FRISSÍTSD</strong> ezt az oldalt 60 másodpercig</li>
                <li>Figyeld a countdown számlálót</li>
                <li>60 másodperc után a timeout üzenet megjelenik</li>
                <li>Ez szimulálja a 3600 sec (1 órás) timeout működését</li>
            </ol>

            <div class="alert alert-warning">
                <strong>Figyelem:</strong> Ez csak egy demonstrációs teszt. Az éles rendszerben a timeout 3600 másodperc (1 óra).
            </div>

            <div class="mt-3">
                <a href="test_session_quick.php" class="btn btn-primary">Oldal frissítése (aktivitás)</a>
                <a href="?reset=1" class="btn btn-secondary">Teszt újraindítása</a>
                <a href="test_session_timeout.php" class="btn btn-info">Teljes teszt oldal</a>
            </div>
        </div>

        <div class="test-box">
            <h3>Éles Session Config</h3>
            <table class="table table-sm">
                <tr>
                    <th>gc_maxlifetime:</th>
                    <td><?php echo ini_get('session.gc_maxlifetime'); ?> sec</td>
                </tr>
                <tr>
                    <th>cookie_httponly:</th>
                    <td><?php echo ini_get('session.cookie_httponly') ? 'Enabled ✓' : 'Disabled ✗'; ?></td>
                </tr>
                <tr>
                    <th>cookie_secure:</th>
                    <td><?php echo ini_get('session.cookie_secure') ? 'Enabled ✓' : 'Disabled ✗'; ?></td>
                </tr>
                <tr>
                    <th>cookie_samesite:</th>
                    <td><?php echo ini_get('session.cookie_samesite'); ?></td>
                </tr>
            </table>
        </div>

        <?php if (isLoggedIn()): ?>
        <div class="test-box">
            <h3>Bejelentkezett Felhasználó</h3>
            <table class="table">
                <tr>
                    <th>User ID:</th>
                    <td><?php echo $_SESSION['user_id']; ?></td>
                </tr>
                <tr>
                    <th>Username:</th>
                    <td><?php echo $_SESSION['username']; ?></td>
                </tr>
                <tr>
                    <th>Last Activity (éles):</th>
                    <td>
                        <?php
                        if (isset($_SESSION['last_activity'])) {
                            echo date('Y-m-d H:i:s', $_SESSION['last_activity']);
                            $real_elapsed = time() - $_SESSION['last_activity'];
                            echo " (" . $real_elapsed . " sec ago)";
                        } else {
                            echo "Not set";
                        }
                        ?>
                    </td>
                </tr>
            </table>
            <a href="dashboard.php" class="btn btn-success">Dashboard (védett oldal)</a>
            <a href="logout.php" class="btn btn-danger">Kijelentkezés</a>
        </div>
        <?php else: ?>
        <div class="test-box">
            <div class="alert alert-info">
                Nem vagy bejelentkezve. Az éles timeout teszteléséhez jelentkezz be először.
                <br><br>
                <a href="login.php" class="btn btn-primary">Bejelentkezés</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Reset funkció
if (isset($_GET['reset'])) {
    unset($_SESSION['test_start_time']);
    unset($_SESSION['test_last_activity']);
    header('Location: test_session_quick.php');
    exit();
}
?>
