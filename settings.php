<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'classes/Settings.php';

$settings = new Settings();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $data = [
        'company_name' => trim($_POST['company_name'] ?? ''),
        'company_address' => trim($_POST['company_address'] ?? ''),
        'company_tax_number' => trim($_POST['company_tax_number'] ?? ''),
        'company_email' => trim($_POST['company_email'] ?? ''),
        'company_phone' => trim($_POST['company_phone'] ?? ''),
        'sender_name' => trim($_POST['sender_name'] ?? ''),
        'sender_email' => trim($_POST['sender_email'] ?? ''),
        'smtp_host' => trim($_POST['smtp_host'] ?? ''),
        'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
        'smtp_username' => trim($_POST['smtp_username'] ?? ''),
        'smtp_password' => trim($_POST['smtp_password'] ?? ''),
        'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
        'default_transport_fee' => trim($_POST['default_transport_fee'] ?? '0'),
        'test_mode' => isset($_POST['test_mode']) ? '1' : '0'
    ];
    
    // Email validáció
    if (!empty($data['sender_email']) && !filter_var($data['sender_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Az email cím formátuma nem megfelelő!';
    }
    
    if (!empty($data['company_email']) && !filter_var($data['company_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A cég email címének formátuma nem megfelelő!';
    }
    
    if (empty($errors)) {
        try {
            $settings->setMultiple($data);
            $success = true;
            setFlashMessage('success', 'Beállítások sikeresen mentve!');
        } catch (Exception $e) {
            $errors[] = 'Hiba történt: ' . $e->getMessage();
        }
    }
}

// Beállítások lekérése
$currentSettings = $settings->getAll();

$user = getCurrentUser();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beállítások - Munkalap App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-expand-lg mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-clipboard-data"></i> Munkalap App
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="bi bi-house-door"></i> Főoldal
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text text-white me-3">
                            <i class="bi bi-person-circle"></i> <?php echo escape($user['full_name']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Kijelentkezés
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo escape($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Fejléc -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-gear"></i> Beállítások</h2>
                <p class="text-muted mb-0">Rendszerbeállítások és konfiguráció</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Vissza
            </a>
        </div>

        <!-- Hibaüzenetek -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Hiba történt:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Cég adatai -->
            <div class="card form-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Saját cég adatai</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">Cég neve</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo escape($currentSettings['company_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="company_email" class="form-label">Email cím</label>
                            <input type="email" class="form-control" id="company_email" name="company_email" 
                                   value="<?php echo escape($currentSettings['company_email'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="company_address" class="form-label">Cím</label>
                        <textarea class="form-control" id="company_address" name="company_address" rows="2"><?php echo escape($currentSettings['company_address'] ?? ''); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_tax_number" class="form-label">Adószám</label>
                            <input type="text" class="form-control" id="company_tax_number" name="company_tax_number" 
                                   value="<?php echo escape($currentSettings['company_tax_number'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="company_phone" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="company_phone" name="company_phone" 
                                   value="<?php echo escape($currentSettings['company_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email beállítások -->
            <div class="card form-card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-envelope"></i> Email beállítások</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sender_name" class="form-label">Küldő neve</label>
                            <input type="text" class="form-control" id="sender_name" name="sender_name" 
                                   value="<?php echo escape($currentSettings['sender_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sender_email" class="form-label">Küldő email</label>
                            <input type="email" class="form-control" id="sender_email" name="sender_email" 
                                   value="<?php echo escape($currentSettings['sender_email'] ?? ''); ?>">
                        </div>
                    </div>
                    <hr>
                    <h6>SMTP beállítások (opcionális)</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_host" class="form-label">SMTP szerver</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                   placeholder="smtp.example.com" 
                                   value="<?php echo escape($currentSettings['smtp_host'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_port" class="form-label">SMTP port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                   value="<?php echo escape($currentSettings['smtp_port'] ?? '587'); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_username" class="form-label">SMTP felhasználónév</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                   value="<?php echo escape($currentSettings['smtp_username'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_password" class="form-label">SMTP jelszó</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                   value="<?php echo escape($currentSettings['smtp_password'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_encryption" class="form-label">Titkosítás</label>
                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                            <option value="tls" <?php echo ($currentSettings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($currentSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="" <?php echo empty($currentSettings['smtp_encryption']) ? 'selected' : ''; ?>>Nincs</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="test_mode" name="test_mode" 
                               <?php echo ($currentSettings['test_mode'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="test_mode">
                            <strong>Teszt mód</strong> (email nem lesz elküldve, csak logolva)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Egyéb beállítások -->
            <div class="card form-card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-sliders"></i> Egyéb beállítások</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="default_transport_fee" class="form-label">Alapértelmezett kiszállási díj (Ft)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="default_transport_fee" 
                               name="default_transport_fee" 
                               value="<?php echo escape($currentSettings['default_transport_fee'] ?? '0'); ?>">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Mégse
                </a>
                <button type="submit" name="save" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Mentés
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


