<?php
require_once 'config.php';
require_once 'classes/Database.php';

// Ha már be van jelentkezve, irányítsuk a dashboard-ra
if (isLoggedIn()) {
    redirect('dashboard.php');
    exit();
}

$error = '';
$username = '';

// Bejelentkezés feldolgozása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Kérlek add meg a felhasználónevet és jelszót!';
    } else {
        try {
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT id, username, password, full_name, email FROM users WHERE username = ?",
                [$username]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                // Sikeres bejelentkezés
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                
                setFlashMessage('success', 'Sikeres bejelentkezés! Üdvözöljük, ' . escape($user['full_name']) . '!');
                redirect('dashboard.php');
                exit();
            } else {
                $error = 'Hibás felhasználónév vagy jelszó!';
            }
        } catch (Exception $e) {
            $error = 'Hiba történt a bejelentkezés során. Kérlek próbáld újra!';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Flash üzenet ellenőrzése
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés - Munkalap App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-card {
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            border-radius: 15px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2.5rem;
            background: white;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="login-header">
                        <h2 class="mb-0"><i class="bi bi-clipboard-data"></i> Munkalap App</h2>
                        <p class="mb-0 mt-2">Bejelentkezés</p>
                    </div>
                    <div class="login-body">
                        <?php if ($flash): ?>
                            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                                <?php echo escape($flash['message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo escape($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person"></i> Felhasználónév
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg" 
                                    id="username" 
                                    name="username" 
                                    value="<?php echo escape($username); ?>" 
                                    required 
                                    autofocus
                                    placeholder="Add meg a felhasználóneved"
                                >
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Jelszó
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control form-control-lg" 
                                    id="password" 
                                    name="password" 
                                    required
                                    placeholder="Add meg a jelszavad"
                                >
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary btn-lg btn-login">
                                    <i class="bi bi-box-arrow-in-right"></i> Bejelentkezés
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <strong>Teszt felhasználók:</strong><br>
                                admin / admin123<br>
                                user / user123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


