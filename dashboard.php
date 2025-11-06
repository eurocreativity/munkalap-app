<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'classes/Database.php';

$db = Database::getInstance();
$user = getCurrentUser();

// Statisztikák lekérése
$stats = [
    'worksheets_count' => $db->fetchOne("SELECT COUNT(*) as count FROM worksheets")['count'] ?? 0,
    'companies_count' => $db->fetchOne("SELECT COUNT(*) as count FROM companies")['count'] ?? 0,
    'users_count' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Munkalap App</title>
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
        .stats-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 3rem;
            opacity: 0.3;
        }
        .menu-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            color: inherit;
        }
        .menu-icon {
            font-size: 2.5rem;
            color: #667eea;
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

        <!-- Üdvözlő üzenet -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title mb-0">
                            <i class="bi bi-house-door"></i> Üdvözöljük!
                        </h1>
                        <p class="card-text text-muted mb-0">
                            Köszöntjük a Munkalap App-ban, <strong><?php echo escape($user['full_name']); ?></strong>!
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statisztikák -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stats-card text-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Munkalapok</h6>
                                <h2 class="mb-0"><?php echo $stats['worksheets_count']; ?></h2>
                            </div>
                            <i class="bi bi-file-earmark-text stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stats-card text-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Cégek</h6>
                                <h2 class="mb-0"><?php echo $stats['companies_count']; ?></h2>
                            </div>
                            <i class="bi bi-building stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stats-card text-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Felhasználók</h6>
                                <h2 class="mb-0"><?php echo $stats['users_count']; ?></h2>
                            </div>
                            <i class="bi bi-people stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menü -->
        <div class="row">
            <div class="col-md-3 mb-3">
                <a href="worksheets/list.php" class="card menu-card">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text menu-icon mb-3"></i>
                        <h5 class="card-title">Munkalapok</h5>
                        <p class="card-text text-muted">Munkalapok kezelése és nyomon követése</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="companies/list.php" class="card menu-card">
                    <div class="card-body text-center">
                        <i class="bi bi-building menu-icon mb-3"></i>
                        <h5 class="card-title">Cégek</h5>
                        <p class="card-text text-muted">Cégek kezelése és adatainak módosítása</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="monthly/close.php" class="card menu-card">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-month menu-icon mb-3"></i>
                        <h5 class="card-title">Havi zárás</h5>
                        <p class="card-text text-muted">Havi összesítő és email küldés</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="settings.php" class="card menu-card">
                    <div class="card-body text-center">
                        <i class="bi bi-gear menu-icon mb-3"></i>
                        <h5 class="card-title">Beállítások</h5>
                        <p class="card-text text-muted">Rendszerbeállítások és profil kezelés</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

