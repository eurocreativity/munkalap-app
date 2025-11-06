<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Company.php';

$company = new Company();

// ID ellenőrzése
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    setFlashMessage('error', 'Érvénytelen cég azonosító!');
    header('Location: list.php');
    exit();
}

// Cég lekérése
$existing = $company->getById($id);
if (!$existing) {
    setFlashMessage('error', 'A cég nem található!');
    header('Location: list.php');
    exit();
}

// Ellenőrzés, hogy van-e munkalap a céghez
if ($company->hasWorksheets($id)) {
    setFlashMessage('error', 'Ez a cég nem törölhető, mert vannak hozzá rendelt munkalapok!');
    header('Location: list.php');
    exit();
}

// Törlés megerősítése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        if ($company->delete($id)) {
            setFlashMessage('success', 'A cég sikeresen törölve!');
        } else {
            setFlashMessage('error', 'Hiba történt a törlés során!');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Hiba történt: ' . $e->getMessage());
        error_log("Company delete error: " . $e->getMessage());
    }
    
    header('Location: list.php');
    exit();
}

// Ha nem POST, akkor a megerősítő oldal
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cég törlése - Munkalap App</title>
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
        .confirm-card {
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
            <a class="navbar-brand" href="../dashboard.php">
                <i class="bi bi-clipboard-data"></i> Munkalap App
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../dashboard.php">
                            <i class="bi bi-house-door"></i> Főoldal
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text text-white me-3">
                            <i class="bi bi-person-circle"></i> <?php echo escape($user['full_name']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Kijelentkezés
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <!-- Fejléc -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-exclamation-triangle text-danger"></i> Cég törlése</h2>
                        <p class="text-muted mb-0">Biztosan törölni szeretnéd ezt a céget?</p>
                    </div>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Vissza
                    </a>
                </div>

                <!-- Megerősítő kártya -->
                <div class="card confirm-card border-danger">
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Figyelem!</strong> Ez a művelet nem visszavonható!
                        </div>

                        <h5 class="card-title">Cég adatai:</h5>
                        <ul class="list-unstyled">
                            <li><strong>Név:</strong> <?php echo escape($existing['name']); ?></li>
                            <?php if (!empty($existing['address'])): ?>
                                <li><strong>Cím:</strong> <?php echo escape($existing['address']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($existing['email'])): ?>
                                <li><strong>Email:</strong> <?php echo escape($existing['email']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($existing['tax_number'])): ?>
                                <li><strong>Adószám:</strong> <?php echo escape($existing['tax_number']); ?></li>
                            <?php endif; ?>
                        </ul>

                        <form method="POST" action="">
                            <div class="d-flex justify-content-between mt-4">
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Mégse
                                </a>
                                <button type="submit" name="confirm_delete" class="btn btn-danger">
                                    <i class="bi bi-trash"></i> Igen, törlöm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


