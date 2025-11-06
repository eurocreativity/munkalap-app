<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Company.php';

$company = new Company();
$companies = $company->getAll();

$flash = getFlashMessage();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cégek - Munkalap App</title>
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
        .table-card {
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
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo escape($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Fejléc -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-building"></i> Cégek</h2>
                <p class="text-muted mb-0">Cégek kezelése és nyomon követése</p>
            </div>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Új cég hozzáadása
            </a>
        </div>

        <!-- Táblázat -->
        <div class="card table-card">
            <div class="card-body">
                <?php if (count($companies) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Név</th>
                                    <th>Cím</th>
                                    <th>Email</th>
                                    <th>Kapcsolattartó</th>
                                    <th class="text-end">Műveletek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $comp): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo escape($comp['name']); ?></strong>
                                            <?php if (!empty($comp['tax_number'])): ?>
                                                <br><small class="text-muted">Adószám: <?php echo escape($comp['tax_number']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo escape($comp['address'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (!empty($comp['email'])): ?>
                                                <a href="mailto:<?php echo escape($comp['email']); ?>">
                                                    <?php echo escape($comp['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo escape($comp['contact_person'] ?? '-'); ?></td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <a href="edit.php?id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Szerkesztés
                                                </a>
                                                <a href="delete.php?id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Biztosan törölni szeretnéd ezt a céget?');">
                                                    <i class="bi bi-trash"></i> Törlés
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-building text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-3">Még nincs rögzített cég.</p>
                        <a href="add.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Első cég hozzáadása
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


