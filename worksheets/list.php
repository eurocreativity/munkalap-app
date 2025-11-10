<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Worksheet.php';
require_once __DIR__ . '/../classes/Company.php';

$worksheet = new Worksheet();
$company = new Company();

// Szűrők
$filters = [];
if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
    $filters['company_id'] = (int)$_GET['company_id'];
}
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

// Munkalapok lekérése
$worksheets = $worksheet->getAll($filters);
$companies = $company->getAll();

$flash = getFlashMessage();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Munkalapok - Munkalap App</title>
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
        .filter-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .badge-status {
            padding: 0.35em 0.65em;
            font-size: 0.875em;
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
                <h2><i class="bi bi-file-earmark-text"></i> Munkalapok</h2>
                <p class="text-muted mb-0">Munkalapok kezelése és nyomon követése</p>
            </div>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Új munkalap
            </a>
        </div>

        <!-- Szűrők -->
        <div class="card filter-card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="company_id" class="form-label">Cég</label>
                        <select class="form-select" id="company_id" name="company_id">
                            <option value="">-- Összes cég --</option>
                            <?php foreach ($companies as $comp): ?>
                                <option value="<?php echo $comp['id']; ?>" 
                                        <?php echo (isset($filters['company_id']) && $filters['company_id'] == $comp['id']) ? 'selected' : ''; ?>>
                                    <?php echo escape($comp['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">Dátum tól</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo escape($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">Dátum ig</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo escape($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Státusz</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">-- Összes státusz --</option>
                            <option value="Aktív" <?php echo (isset($filters['status']) && $filters['status'] === 'Aktív') ? 'selected' : ''; ?>>Aktív</option>
                            <option value="Lezárt" <?php echo (isset($filters['status']) && $filters['status'] === 'Lezárt') ? 'selected' : ''; ?>>Lezárt</option>
                            <option value="Folyamatban" <?php echo (isset($filters['status']) && $filters['status'] === 'Folyamatban') ? 'selected' : ''; ?>>Folyamatban</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Szűrés
                        </button>
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Szűrés törlése
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Táblázat -->
        <div class="card table-card">
            <div class="card-body">
                <?php if (count($worksheets) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sorszám</th>
                                    <th>Munkalap száma</th>
                                    <th>Cég</th>
                                    <th>Dátum</th>
                                    <th>Munka órák</th>
                                    <th>Munka típusa</th>
                                    <th>Státusz</th>
                                    <th class="text-end">Műveletek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($worksheets as $index => $ws): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo escape($ws['worksheet_number']); ?></strong></td>
                                        <td><?php echo escape($ws['company_name'] ?? '-'); ?></td>
                                        <td><?php echo date('Y.m.d', strtotime($ws['work_date'])); ?></td>
                                        <td><?php echo number_format($ws['work_hours'], 2); ?> óra</td>
                                        <td>
                                            <span class="badge bg-<?php echo ($ws['work_type'] === 'Helyi') ? 'primary' : 'info'; ?>">
                                                <?php echo escape($ws['work_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            if ($ws['status'] === 'Aktív') $statusClass = 'success';
                                            elseif ($ws['status'] === 'Lezárt') $statusClass = 'dark';
                                            elseif ($ws['status'] === 'Folyamatban') $statusClass = 'warning';
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?> badge-status">
                                                <?php echo escape($ws['status'] ?? 'Aktív'); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <a href="pdf.php?id=<?php echo $ws['id']; ?>"
                                                   target="_blank"
                                                   class="btn btn-sm btn-outline-danger"
                                                   title="PDF letöltése">
                                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-primary" title="Megtekintés">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $ws['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Szerkesztés">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Törlés"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $ws['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>

                                            <!-- Törlés megerősítő modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $ws['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $ws['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $ws['id']; ?>">
                                                                <i class="bi bi-exclamation-triangle"></i> Munkalap törlése
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-3">Biztosan törölni szeretné ezt a munkalapot?</p>
                                                            <div class="alert alert-warning">
                                                                <strong>Figyelem:</strong> Ez a művelet nem visszavonható! Az összes kapcsolódó anyag is törlődni fog.
                                                            </div>
                                                            <p class="mb-0"><strong>Munkalap száma:</strong> <?php echo escape($ws['worksheet_number']); ?></p>
                                                            <p class="mb-0"><strong>Cég:</strong> <?php echo escape($ws['company_name'] ?? 'N/A'); ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="bi bi-x-circle"></i> Mégse
                                                            </button>
                                                            <form method="POST" action="delete.php" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                                                                <input type="hidden" name="id" value="<?php echo $ws['id']; ?>">
                                                                <button type="submit" name="delete" class="btn btn-danger">
                                                                    <i class="bi bi-trash"></i> Törlés megerősítése
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-text text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-3">
                            <?php if (!empty($filters)): ?>
                                Nincs találat a szűrési feltételek alapján.
                            <?php else: ?>
                                Még nincs rögzített munkalap.
                            <?php endif; ?>
                        </p>
                        <a href="add.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Első munkalap hozzáadása
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

