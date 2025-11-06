<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Worksheet.php';
require_once __DIR__ . '/../classes/Company.php';
require_once __DIR__ . '/../classes/Material.php';

$worksheet = new Worksheet();
$company = new Company();
$errors = [];

// Cégek listája a dropdownhoz
$companies = $company->getAll();

// Automatikus munkalap szám előnézet
$previewNumber = $worksheet->generateWorksheetNumber();

$data = [
    'company_id' => '',
    'worksheet_number' => $previewNumber,
    'work_date' => date('Y-m-d'),
    'work_hours' => '',
    'description' => '',
    'reporter_name' => '',
    'device_name' => '',
    'worker_name' => '',
    'work_type' => 'Helyi',
    'transport_fee' => '',
    'travel_fee' => '',
    'payment_type' => 'Eseti',
    'work_time' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Adatok begyűjtése
    $data['company_id'] = trim($_POST['company_id'] ?? '');
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
    
    // Validáció
    if (empty($data['company_id'])) {
        $errors[] = 'Válasszon céget!';
    }
    
    if (empty($data['work_date'])) {
        $errors[] = 'A dátum megadása kötelező!';
    }
    
    if (empty($data['work_hours']) || $data['work_hours'] <= 0) {
        $errors[] = 'A munka órák száma kötelező és nagyobb kell legyen 0-nál!';
    }
    
    // Ha helyi munka, transport_fee használata
    if ($data['work_type'] === 'Helyi' && empty($data['transport_fee'])) {
        $data['transport_fee'] = 0;
    }
    
    // Ha távoli, transport_fee null
    if ($data['work_type'] === 'Távoli') {
        $data['transport_fee'] = 0;
    }
    
    // Anyagok adatainak begyűjtése
    $materials = [];
    if (isset($_POST['materials']) && is_array($_POST['materials'])) {
        foreach ($_POST['materials'] as $material) {
            if (!empty($material['product_name'])) {
                $materials[] = [
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
            $materialObj = new Material();
            
            // Konverziók
            $data['work_hours'] = floatval($data['work_hours']);
            $data['transport_fee'] = floatval($data['transport_fee']);
            
            // Munkalap mentése
            $id = $worksheet->create($data);
            if ($id) {
                // Anyagok mentése
                foreach ($materials as $materialData) {
                    $materialData['worksheet_id'] = $id;
                    $materialObj->create($materialData);
                }
                
                setFlashMessage('success', 'A munkalap sikeresen létrehozva!');
                header('Location: list.php');
                exit();
            } else {
                $errors[] = 'Hiba történt a mentés során!';
            }
        } catch (Exception $e) {
            $errors[] = 'Hiba történt: ' . $e->getMessage();
            error_log("Worksheet create error: " . $e->getMessage());
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
    <title>Új munkalap - Munkalap App</title>
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
                        <h2><i class="bi bi-file-earmark-plus"></i> Új munkalap</h2>
                        <p class="text-muted mb-0">Adja meg az új munkalap adatait</p>
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
                                        Munkalap száma
                                    </label>
                                    <input type="text" class="form-control" id="worksheet_number" 
                                           name="worksheet_number" value="<?php echo escape($data['worksheet_number']); ?>" 
                                           readonly>
                                    <small class="text-muted">Automatikusan generált</small>
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
                                <div class="col-md-6 mb-3">
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

                                <div class="col-md-6 mb-3">
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
                        </form>
                    </div>
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

        // Dátum változásakor új munkalap szám generálása
        document.getElementById('work_date').addEventListener('change', function() {
            const date = new Date(this.value);
            const year = date.getFullYear();
            const currentNumber = document.getElementById('worksheet_number').value;
            const newNumber = currentNumber.replace(/^\d{4}/, year);
            document.getElementById('worksheet_number').value = newNumber;
        });

        // Anyagok kezelése
        let materialCounter = 0;
        const materialsContainer = document.getElementById('materialsContainer');

        function addMaterialRow() {
            materialCounter++;
            const row = document.createElement('div');
            row.className = 'material-row border rounded p-3 mb-3';
            row.id = 'material-row-' + materialCounter;
            
            row.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Termék neve</label>
                        <input type="text" class="form-control" name="materials[${materialCounter}][product_name]" 
                               placeholder="Például: Csavar M6x20">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mennyiség</label>
                        <input type="number" step="0.01" min="0" class="form-control" 
                               name="materials[${materialCounter}][quantity]" value="1" placeholder="1">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Mért.egys.</label>
                        <input type="text" class="form-control" name="materials[${materialCounter}][unit]" 
                               value="db" placeholder="db">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Nettó ár (Ft)</label>
                        <input type="number" step="0.01" min="0" class="form-control material-net-price" 
                               name="materials[${materialCounter}][net_price]" value="0" 
                               data-row="${materialCounter}" placeholder="0">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">ÁFA %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control material-vat-rate" 
                               name="materials[${materialCounter}][vat_rate]" value="27" 
                               data-row="${materialCounter}" placeholder="27">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bruttó ár (Ft)</label>
                        <input type="text" class="form-control material-gross-price" 
                               id="gross-price-${materialCounter}" readonly value="0">
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

        // Új anyag gomb eseménykezelő
        document.getElementById('addMaterialBtn').addEventListener('click', addMaterialRow);

        // Első üres sor hozzáadása
        addMaterialRow();
    </script>
</body>
</html>

