<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Worksheet.php';
require_once __DIR__ . '/../classes/Company.php';
require_once __DIR__ . '/../classes/Material.php';

$worksheet = new Worksheet();
$company = new Company();
$materialObj = new Material();
$errors = [];

// ID ellenőrzés - SQL injection védelem
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('danger', 'Érvénytelen munkalap azonosító!');
    header('Location: list.php');
    exit();
}

$id = intval($_GET['id']);

// Munkalap betöltése
$worksheetData = $worksheet->getById($id);
if (!$worksheetData) {
    setFlashMessage('danger', 'A munkalap nem található!');
    header('Location: list.php');
    exit();
}

// Anyagok betöltése
$materials = $materialObj->getByWorksheetId($id);

// Cégek listája a dropdownhoz
$companies = $company->getAll();

// Adatok előkészítése a formhoz
$data = [
    'company_id' => $worksheetData['company_id'],
    'worksheet_number' => $worksheetData['worksheet_number'],
    'work_date' => $worksheetData['work_date'],
    'work_hours' => $worksheetData['work_hours'],
    'description' => $worksheetData['description'],
    'reporter_name' => $worksheetData['reporter_name'],
    'device_name' => $worksheetData['device_name'],
    'worker_name' => $worksheetData['worker_name'],
    'work_type' => $worksheetData['work_type'],
    'transport_fee' => $worksheetData['transport_fee'],
    'travel_fee' => $worksheetData['travel_fee'],
    'payment_type' => $worksheetData['payment_type'],
    'work_time' => $worksheetData['work_time'],
    'status' => $worksheetData['status'] ?? 'Aktív'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Adatok begyűjtése
    $data['company_id'] = trim($_POST['company_id'] ?? '');
    $data['worksheet_number'] = trim($_POST['worksheet_number'] ?? '');
    $data['work_date'] = trim($_POST['work_date'] ?? '');
    $data['work_hours'] = trim($_POST['work_hours'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['reporter_name'] = trim($_POST['reporter_name'] ?? '');
    $data['device_name'] = trim($_POST['device_name'] ?? '');
    $data['worker_name'] = trim($_POST['worker_name'] ?? '');
    $data['work_type'] = trim($_POST['work_type'] ?? 'Helyi');
    $data['transport_fee'] = trim($_POST['transport_fee'] ?? '0');
    $data['travel_fee'] = trim($_POST['travel_fee'] ?? '0');
    $data['payment_type'] = trim($_POST['payment_type'] ?? 'Eseti');
    $data['work_time'] = trim($_POST['work_time'] ?? '');
    $data['status'] = trim($_POST['status'] ?? 'Aktív');

    // Validáció
    if (empty($data['company_id']) || !is_numeric($data['company_id'])) {
        $errors[] = 'Válasszon céget!';
    }

    if (empty($data['worksheet_number'])) {
        $errors[] = 'A munkalap száma kötelező!';
    }

    if (empty($data['work_date'])) {
        $errors[] = 'A dátum megadása kötelező!';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['work_date'])) {
        $errors[] = 'Érvénytelen dátum formátum!';
    }

    if (empty($data['work_hours']) || !is_numeric($data['work_hours']) || $data['work_hours'] <= 0) {
        $errors[] = 'A munka órák száma kötelező és nagyobb kell legyen 0-nál!';
    }

    // Munka típus validáció
    if (!in_array($data['work_type'], ['Helyi', 'Távoli'])) {
        $errors[] = 'Érvénytelen munka típus!';
    }

    // Díjazás típus validáció
    if (!in_array($data['payment_type'], ['Átalány', 'Eseti'])) {
        $errors[] = 'Érvénytelen díjazás típus!';
    }

    // Státusz validáció
    if (!in_array($data['status'], ['Aktív', 'Lezárt', 'Törölt'])) {
        $errors[] = 'Érvénytelen státusz!';
    }

    // Ha helyi munka, transport_fee használata
    if ($data['work_type'] === 'Helyi' && empty($data['transport_fee'])) {
        $data['transport_fee'] = 0;
    }

    // Ha távoli, transport_fee null
    if ($data['work_type'] === 'Távoli') {
        $data['transport_fee'] = 0;
    }

    // Munkaidő formátum validáció (ha meg van adva)
    if (!empty($data['work_time']) && !preg_match('/^([0-9]{1,2}):([0-9]{2})$/', $data['work_time'])) {
        $errors[] = 'Érvénytelen munkaidő formátum! (óó:pp)';
    }

    // Anyagok adatainak begyűjtése és validálása
    $updatedMaterials = [];
    if (isset($_POST['materials']) && is_array($_POST['materials'])) {
        foreach ($_POST['materials'] as $material) {
            if (!empty($material['product_name'])) {
                // Validáció
                if (!is_numeric($material['quantity'] ?? 0) || floatval($material['quantity']) < 0) {
                    $errors[] = 'Érvénytelen mennyiség az anyagoknál!';
                }
                if (!is_numeric($material['net_price'] ?? 0) || floatval($material['net_price']) < 0) {
                    $errors[] = 'Érvénytelen nettó ár az anyagoknál!';
                }
                if (!is_numeric($material['vat_rate'] ?? 0) || floatval($material['vat_rate']) < 0 || floatval($material['vat_rate']) > 100) {
                    $errors[] = 'Érvénytelen ÁFA kulcs az anyagoknál!';
                }

                $updatedMaterials[] = [
                    'product_name' => trim($material['product_name'] ?? ''),
                    'quantity' => floatval($material['quantity'] ?? 0),
                    'unit' => trim($material['unit'] ?? 'db'),
                    'net_price' => floatval($material['net_price'] ?? 0),
                    'vat_rate' => floatval($material['vat_rate'] ?? 27)
                ];
            }
        }
    }

    // Ha nincs hiba, mentés
    if (empty($errors)) {
        try {
            // Konverziók
            $data['company_id'] = intval($data['company_id']);
            $data['work_hours'] = floatval($data['work_hours']);
            $data['transport_fee'] = floatval($data['transport_fee']);
            $data['travel_fee'] = floatval($data['travel_fee']);

            // Munkalap frissítése
            if ($worksheet->update($id, $data)) {
                // Régi anyagok törlése
                $materialObj->deleteByWorksheetId($id);

                // Új anyagok mentése
                foreach ($updatedMaterials as $materialData) {
                    $materialData['worksheet_id'] = $id;
                    $materialObj->create($materialData);
                }

                setFlashMessage('success', 'A munkalap sikeresen módosítva!');
                header('Location: list.php');
                exit();
            } else {
                $errors[] = 'Hiba történt a mentés során!';
            }
        } catch (Exception $e) {
            $errors[] = 'Hiba történt: ' . $e->getMessage();
            error_log("Worksheet update error: " . $e->getMessage());
        }
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Munkalap szerkesztése - Munkalap App</title>
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
        .delete-section {
            border-top: 2px solid #dc3545;
            margin-top: 30px;
            padding-top: 20px;
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
            <div class="col-md-10">
                <!-- Fejléc -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-pencil-square"></i> Munkalap szerkesztése</h2>
                        <p class="text-muted mb-0">Munkalap száma: <strong><?php echo escape($worksheetData['worksheet_number']); ?></strong></p>
                    </div>
                    <a href="list.php" class="btn btn-outline-secondary">
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

                <!-- Form -->
                <div class="card form-card">
                    <div class="card-body">
                        <form method="POST" action="" id="worksheetForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="company_id" class="form-label">
                                        Cég <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select <?php echo (!empty($errors) && empty($data['company_id'])) ? 'is-invalid' : ''; ?>"
                                            id="company_id" name="company_id" required>
                                        <option value="">-- Válasszon céget --</option>
                                        <?php foreach ($companies as $comp): ?>
                                            <option value="<?php echo $comp['id']; ?>"
                                                    <?php echo ($data['company_id'] == $comp['id']) ? 'selected' : ''; ?>>
                                                <?php echo escape($comp['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Válasszon céget!
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="worksheet_number" class="form-label">
                                        Munkalap száma <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="worksheet_number"
                                           name="worksheet_number" value="<?php echo escape($data['worksheet_number']); ?>"
                                           required>
                                    <small class="text-muted">Módosítható szükség esetén</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="work_date" class="form-label">
                                        Dátum <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control <?php echo (!empty($errors) && empty($data['work_date'])) ? 'is-invalid' : ''; ?>"
                                           id="work_date" name="work_date"
                                           value="<?php echo escape($data['work_date']); ?>" required>
                                    <div class="invalid-feedback">
                                        A dátum megadása kötelező!
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="work_time" class="form-label">
                                        Munkavégzés ideje (óra:perc)
                                    </label>
                                    <input type="text" class="form-control" id="work_time"
                                           name="work_time" value="<?php echo escape($data['work_time']); ?>"
                                           placeholder="Például: 08:30" pattern="[0-9]{1,2}:[0-9]{2}">
                                    <small class="text-muted">Formátum: óó:pp (pl: 08:30)</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="reporter_name" class="form-label">
                                        Hiba bejelentő neve
                                    </label>
                                    <input type="text" class="form-control" id="reporter_name"
                                           name="reporter_name" value="<?php echo escape($data['reporter_name']); ?>"
                                           placeholder="Például: Kovács János">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="device_name" class="form-label">
                                        Eszköz megnevezése
                                    </label>
                                    <input type="text" class="form-control" id="device_name"
                                           name="device_name" value="<?php echo escape($data['device_name']); ?>"
                                           placeholder="Például: Számítógép - Dell">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="worker_name" class="form-label">
                                        Munkát végző neve
                                    </label>
                                    <input type="text" class="form-control" id="worker_name"
                                           name="worker_name" value="<?php echo escape($data['worker_name']); ?>"
                                           placeholder="Például: Nagy Péter">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Munka típusa <span class="text-danger">*</span>
                                    </label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="work_type"
                                                   id="work_type_local" value="Helyi"
                                                   <?php echo ($data['work_type'] === 'Helyi') ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="work_type_local">
                                                Helyi
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="work_type"
                                                   id="work_type_remote" value="Távoli"
                                                   <?php echo ($data['work_type'] === 'Távoli') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="work_type_remote">
                                                Távoli
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3" id="transport_fee_container"
                                     style="<?php echo ($data['work_type'] === 'Távoli') ? 'display: none;' : ''; ?>">
                                    <label for="transport_fee" class="form-label">
                                        Kiszállási díj (Ft)
                                    </label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                           id="transport_fee" name="transport_fee"
                                           value="<?php echo escape($data['transport_fee']); ?>"
                                           placeholder="0">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Díjazás <span class="text-danger">*</span>
                                    </label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="payment_type"
                                                   id="payment_type_lump" value="Átalány"
                                                   <?php echo ($data['payment_type'] === 'Átalány') ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="payment_type_lump">
                                                Átalány
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="payment_type"
                                                   id="payment_type_case" value="Eseti"
                                                   <?php echo ($data['payment_type'] === 'Eseti') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="payment_type_case">
                                                Eseti
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="work_hours" class="form-label">
                                        Munka órák száma <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" step="0.01" min="0" class="form-control <?php echo (!empty($errors) && empty($data['work_hours'])) ? 'is-invalid' : ''; ?>"
                                           id="work_hours" name="work_hours"
                                           value="<?php echo escape($data['work_hours']); ?>"
                                           required placeholder="0.00">
                                    <div class="invalid-feedback">
                                        A munka órák száma kötelező!
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">
                                        Státusz <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Aktív" <?php echo ($data['status'] === 'Aktív') ? 'selected' : ''; ?>>Aktív</option>
                                        <option value="Lezárt" <?php echo ($data['status'] === 'Lezárt') ? 'selected' : ''; ?>>Lezárt</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    Munka leírása
                                </label>
                                <textarea class="form-control" id="description" name="description"
                                          rows="5" placeholder="Részletes leírás a munkáról..."><?php echo escape($data['description']); ?></textarea>
                            </div>

                            <!-- Anyagok szekció -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> Anyagok</h5>
                                    <button type="button" class="btn btn-sm btn-success" id="addMaterialBtn">
                                        <i class="bi bi-plus-circle"></i> Új anyag hozzáadása
                                    </button>
                                </div>
                                <div id="materialsContainer">
                                    <!-- Anyag sorok itt jelennek meg -->
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Mégse
                                </a>
                                <button type="submit" name="save" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Mentés
                                </button>
                            </div>

                            <!-- Törlés szekció -->
                            <div class="delete-section">
                                <h5 class="text-danger mb-3"><i class="bi bi-trash"></i> Veszélyes művelet</h5>
                                <p class="text-muted">A munkalap törlése végleges és nem visszavonható. Az összes kapcsolódó anyag is törlődni fog.</p>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="bi bi-trash"></i> Munkalap törlése
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Törlés megerősítő modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Munkalap törlése
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Biztosan törölni szeretné ezt a munkalapot?</p>
                    <div class="alert alert-warning">
                        <strong>Figyelem:</strong> Ez a művelet nem visszavonható! Az összes kapcsolódó anyag is törlődni fog.
                    </div>
                    <p class="mb-0"><strong>Munkalap száma:</strong> <?php echo escape($worksheetData['worksheet_number']); ?></p>
                    <p class="mb-0"><strong>Cég:</strong> <?php echo escape($worksheetData['company_name'] ?? 'N/A'); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Mégse
                    </button>
                    <form method="POST" action="delete.php" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <button type="submit" name="delete" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Törlés megerősítése
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Kiszállási díj mező megjelenítése/elrejtése
        document.querySelectorAll('input[name="work_type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const transportFeeContainer = document.getElementById('transport_fee_container');
                if (this.value === 'Helyi') {
                    transportFeeContainer.style.display = 'block';
                    document.getElementById('transport_fee').value = document.getElementById('transport_fee').value || '0';
                } else {
                    transportFeeContainer.style.display = 'none';
                    document.getElementById('transport_fee').value = '0';
                }
            });
        });

        // Anyagok kezelése
        let materialCounter = 0;
        const materialsContainer = document.getElementById('materialsContainer');

        // Meglévő anyagok betöltése
        const existingMaterials = <?php echo json_encode($materials); ?>;

        function addMaterialRow(material = null) {
            materialCounter++;
            const row = document.createElement('div');
            row.className = 'material-row border rounded p-3 mb-3';
            row.id = 'material-row-' + materialCounter;

            const productName = material ? material.product_name : '';
            const quantity = material ? material.quantity : 1;
            const unit = material ? material.unit : 'db';
            const netPrice = material ? material.net_price : 0;
            const vatRate = material ? material.vat_rate : 27;
            const grossPrice = material ? material.gross_price : 0;

            row.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Termék neve</label>
                        <input type="text" class="form-control" name="materials[${materialCounter}][product_name]"
                               value="${escapeHtml(productName)}" placeholder="Például: Csavar M6x20">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mennyiség</label>
                        <input type="number" step="0.01" min="0" class="form-control"
                               name="materials[${materialCounter}][quantity]" value="${quantity}" placeholder="1">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Mért.egys.</label>
                        <input type="text" class="form-control" name="materials[${materialCounter}][unit]"
                               value="${escapeHtml(unit)}" placeholder="db">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Nettó ár (Ft)</label>
                        <input type="number" step="0.01" min="0" class="form-control material-net-price"
                               name="materials[${materialCounter}][net_price]" value="${netPrice}"
                               data-row="${materialCounter}" placeholder="0">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">ÁFA %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control material-vat-rate"
                               name="materials[${materialCounter}][vat_rate]" value="${vatRate}"
                               data-row="${materialCounter}" placeholder="27">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bruttó ár (Ft)</label>
                        <input type="text" class="form-control material-gross-price"
                               id="gross-price-${materialCounter}" readonly value="${grossPrice}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm w-100 remove-material"
                                data-row="${materialCounter}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            materialsContainer.appendChild(row);

            // Eseménykezelők hozzáadása
            const netPriceInput = row.querySelector('.material-net-price');
            const vatRateInput = row.querySelector('.material-vat-rate');
            const removeBtn = row.querySelector('.remove-material');

            netPriceInput.addEventListener('input', function() {
                calculateGrossPrice(materialCounter);
            });

            vatRateInput.addEventListener('input', function() {
                calculateGrossPrice(materialCounter);
            });

            removeBtn.addEventListener('click', function() {
                row.remove();
            });
        }

        function calculateGrossPrice(rowId) {
            const row = document.getElementById('material-row-' + rowId);
            if (!row) return;

            const netPriceInput = row.querySelector('.material-net-price');
            const vatRateInput = row.querySelector('.material-vat-rate');
            const grossPriceInput = document.getElementById('gross-price-' + rowId);

            const netPrice = parseFloat(netPriceInput.value) || 0;
            const vatRate = parseFloat(vatRateInput.value) || 27;
            const vatAmount = netPrice * (vatRate / 100);
            const grossPrice = netPrice + vatAmount;

            grossPriceInput.value = grossPrice.toFixed(2);
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Új anyag gomb eseménykezelő
        document.getElementById('addMaterialBtn').addEventListener('click', function() {
            addMaterialRow();
        });

        // Meglévő anyagok betöltése
        if (existingMaterials.length > 0) {
            existingMaterials.forEach(function(material) {
                addMaterialRow(material);
            });
        } else {
            // Ha nincs anyag, adjunk hozzá egy üres sort
            addMaterialRow();
        }
    </script>
</body>
</html>
