<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Worksheet.php';
require_once __DIR__ . '/../classes/Company.php';
require_once __DIR__ . '/../includes/email.php';

$worksheet = new Worksheet();
$company = new Company();
$emailSender = new EmailSender();

// Aktuális dátum alapértelmezettként
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

// Email küldés feldolgozása
$emailResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $companyId = (int)$_POST['company_id'];
    $year = (int)$_POST['year'];
    $month = (int)$_POST['month'];
    
    $comp = $company->getById($companyId);
    if ($comp && !empty($comp['email'])) {
        // Összesítő generálása
        $summary = getMonthlySummary($worksheet, $companyId, $year, $month);
        
        // PDF generálása ideiglenes fájlba
        $pdfPath = generatePdfToFile($worksheet, $company, $companyId, $year, $month);
        
        // Email küldése
        $contactPerson = $comp['contact_person'] ?? 'Tisztelt Ügyfél';
        $emailBody = $emailSender->generateEmailTemplate($contactPerson, $year, $month, $summary);
        
        $monthName = getMonthName($month);
        $pdfFileName = 'havi_osszesito_' . $comp['name'] . '_' . $year . '_' . $month . '.pdf';
        
        // Email küldése PDF csatolmánnyal
        $emailResult = $emailSender->send(
            $comp['email'],
            $year . '. ' . $monthName . ' havi munkalap összesítő',
            $emailBody,
            $pdfPath,
            $pdfFileName
        );
        
        // Ideiglenes PDF fájl törlése
        if ($pdfPath && file_exists($pdfPath)) {
            unlink($pdfPath);
        }
        
        if ($emailResult['success']) {
            if (isset($emailResult['test_mode']) && $emailResult['test_mode']) {
                setFlashMessage('info', 'Email teszt módban lett mentve (nem lett elküldve). ' . ($emailResult['message'] ?? ''));
            } else {
                setFlashMessage('success', 'Email sikeresen elküldve!');
            }
        } else {
            setFlashMessage('error', 'Email küldés sikertelen: ' . ($emailResult['message'] ?? 'Ismeretlen hiba'));
        }
    } else {
        $emailResult = ['success' => false, 'message' => 'A cégnek nincs email címe!'];
        setFlashMessage('error', 'A cégnek nincs email címe!');
    }
}

// Havi összesítő lekérése cégenként
function getMonthlySummary($worksheet, $companyId, $year, $month) {
    $filters = [
        'company_id' => $companyId,
        'date_from' => sprintf('%04d-%02d-01', $year, $month),
        'date_to' => sprintf('%04d-%02d-%d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year)))
    ];
    
    $worksheets = $worksheet->getAll($filters);
    
    $summary = [
        'total_hours' => 0,
        'lump_hours' => 0,
        'case_hours' => 0,
        'transports' => 0
    ];
    
    foreach ($worksheets as $ws) {
        $summary['total_hours'] += $ws['work_hours'];
        
        if ($ws['payment_type'] === 'Átalány') {
            $summary['lump_hours'] += $ws['work_hours'];
        } else {
            $summary['case_hours'] += $ws['work_hours'];
        }
        
        if ($ws['work_type'] === 'Helyi' && $ws['transport_fee'] > 0) {
            $summary['transports']++;
        }
    }
    
    return $summary;
}

function getMonthName($month) {
    $months = [
        1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április',
        5 => 'május', 6 => 'június', 7 => 'július', 8 => 'augusztus',
        9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december'
    ];
    return $months[(int)$month] ?? '';
}

// PDF generálása fájlba (visszatér az elérési úttal)
function generatePdfToFile($worksheet, $company, $companyId, $year, $month) {
    require_once __DIR__ . '/../classes/Material.php';
    require_once __DIR__ . '/../classes/Settings.php';
    
    // TCPDF betöltése
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } elseif (file_exists(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php')) {
        require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
        if (!defined('PDF_PAGE_ORIENTATION')) {
            define('PDF_PAGE_ORIENTATION', 'P');
        }
        if (!defined('PDF_UNIT')) {
            define('PDF_UNIT', 'mm');
        }
        if (!defined('PDF_PAGE_FORMAT')) {
            define('PDF_PAGE_FORMAT', 'A4');
        }
    } else {
        return null;
    }
    
    $material = new Material();
    $settings = new Settings();
    
    $comp = $company->getById($companyId);
    if (!$comp) {
        return null;
    }
    
    // Munkalapok lekérése
    $filters = [
        'company_id' => $companyId,
        'date_from' => sprintf('%04d-%02d-01', $year, $month),
        'date_to' => sprintf('%04d-%02d-%d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year)))
    ];
    
    $worksheets = $worksheet->getAll($filters);
    if (count($worksheets) === 0) {
        return null;
    }
    
    // PDF létrehozása
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Munkalap App');
    $pdf->SetAuthor($settings->get('company_name', 'Euro-Creativity Kft'));
    
    $monthNames = [
        1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április',
        5 => 'május', 6 => 'június', 7 => 'július', 8 => 'augusztus',
        9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december'
    ];
    $monthName = $monthNames[$month] ?? $month;
    
    $pdf->SetTitle('Havi összesítő - ' . $comp['name'] . ' - ' . $year . '. ' . $monthName);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Első oldal - Összesítő
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 10);
    
    // Összesítő számítás
    $totalHours = 0;
    $lumpHours = 0;
    $caseHours = 0;
    $transports = 0;
    
    foreach ($worksheets as $ws) {
        $totalHours += $ws['work_hours'];
        if ($ws['payment_type'] === 'Átalány') {
            $lumpHours += $ws['work_hours'];
        } else {
            $caseHours += $ws['work_hours'];
        }
        if ($ws['work_type'] === 'Helyi' && $ws['transport_fee'] > 0) {
            $transports++;
        }
    }
    
    // Összesítő HTML (ugyanaz mint a summary_pdf.php-ben)
    $html = '<h2 style="text-align: center;">Havi Munkalap Összesítő</h2>';
    $html .= '<p style="text-align: center;"><strong>' . $year . '. ' . $monthName . '</strong></p><br>';
    
    // Cég adatai táblázat
    $html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
    $html .= '<tr>';
    $html .= '<td style="width: 50%; background-color: #f0f0f0;"><strong>MEGRENDELŐ CÉG</strong></td>';
    $html .= '<td style="width: 50%; background-color: #f0f0f0;"><strong>SZÁLLÍTÓ CÉG</strong></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td><strong>' . htmlspecialchars($comp['name']) . '</strong><br>';
    if (!empty($comp['address'])) {
        $html .= htmlspecialchars($comp['address']) . '<br>';
    }
    if (!empty($comp['tax_number'])) {
        $html .= 'Adószám: ' . htmlspecialchars($comp['tax_number']) . '<br>';
    }
    if (!empty($comp['email'])) {
        $html .= 'Email: ' . htmlspecialchars($comp['email']);
    }
    $html .= '</td>';
    $html .= '<td><strong>' . htmlspecialchars($settings->get('company_name', 'Euro-Creativity Kft')) . '</strong><br>';
    $html .= htmlspecialchars($settings->get('company_address', '')) . '<br>';
    $html .= 'Adószám: ' . htmlspecialchars($settings->get('company_tax_number', '')) . '<br>';
    $html .= 'Email: ' . htmlspecialchars($settings->get('company_email', ''));
    $html .= '</td></tr></table>';
    
    $html .= '<br><h3>Összesítés</h3>';
    $html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
    $html .= '<tr style="background-color: #e8e8e8;"><th style="width: 50%;">Kategória</th><th style="width: 50%;">Érték</th></tr>';
    $html .= '<tr><td><strong>Összes munkaóra</strong></td><td>' . number_format($totalHours, 2) . ' óra</td></tr>';
    $html .= '<tr><td><strong>Átalány órák</strong></td><td>' . number_format($lumpHours, 2) . ' óra</td></tr>';
    $html .= '<tr><td><strong>Eseti órák</strong></td><td>' . number_format($caseHours, 2) . ' óra</td></tr>';
    $html .= '<tr><td><strong>Kiszállások száma</strong></td><td>' . $transports . ' db</td></tr>';
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Munkalapok részletei
    foreach ($worksheets as $ws) {
        $pdf->AddPage();
        $materials = $material->getByWorksheetId($ws['id']);
        
        $html = '<h3>Munkalap: ' . htmlspecialchars($ws['worksheet_number']) . '</h3>';
        $html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
        $html .= '<tr><td style="width: 30%; background-color: #e8e8e8;"><strong>Munkalap száma:</strong></td>';
        $html .= '<td style="width: 70%;">' . htmlspecialchars($ws['worksheet_number']) . '</td></tr>';
        $html .= '<tr><td style="background-color: #e8e8e8;"><strong>Dátum:</strong></td>';
        $html .= '<td>' . date('Y.m.d', strtotime($ws['work_date'])) . '</td></tr>';
        $html .= '<tr><td style="background-color: #e8e8e8;"><strong>Munka órák száma:</strong></td>';
        $html .= '<td>' . number_format($ws['work_hours'], 2) . ' óra</td></tr>';
        $html .= '<tr><td style="background-color: #e8e8e8;"><strong>Munka típusa:</strong></td>';
        $html .= '<td>' . htmlspecialchars($ws['work_type'] ?? 'Helyi') . '</td></tr>';
        if ($ws['work_type'] === 'Helyi' && !empty($ws['transport_fee'])) {
            $html .= '<tr><td style="background-color: #e8e8e8;"><strong>Kiszállási díj:</strong></td>';
            $html .= '<td>' . number_format($ws['transport_fee'], 0) . ' Ft</td></tr>';
        }
        $html .= '<tr><td style="background-color: #e8e8e8;"><strong>Díjazás:</strong></td>';
        $html .= '<td>' . htmlspecialchars($ws['payment_type'] ?? 'Eseti') . '</td></tr>';
        if (!empty($ws['description'])) {
            $html .= '<tr><td style="background-color: #e8e8e8;"><strong>Munka leírása:</strong></td>';
            $html .= '<td>' . nl2br(htmlspecialchars($ws['description'])) . '</td></tr>';
        }
        $html .= '</table>';
        
        if (count($materials) > 0) {
            $html .= '<br><h4>Anyagok</h4>';
            $html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
            $html .= '<tr style="background-color: #e8e8e8;"><th>Termék neve</th><th>Mennyiség</th><th>Mért.egys.</th><th>Nettó ár (Ft)</th><th>Bruttó ár (Ft)</th></tr>';
            foreach ($materials as $mat) {
                $html .= '<tr><td>' . htmlspecialchars($mat['product_name']) . '</td>';
                $html .= '<td style="text-align: right;">' . number_format($mat['quantity'], 2) . '</td>';
                $html .= '<td>' . htmlspecialchars($mat['unit']) . '</td>';
                $html .= '<td style="text-align: right;">' . number_format($mat['net_price'], 0) . '</td>';
                $html .= '<td style="text-align: right;">' . number_format($mat['gross_price'], 0) . '</td></tr>';
            }
            $html .= '</table>';
        }
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }
    
    // Ideiglenes fájlba mentés
    $tempDir = __DIR__ . '/../tmp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $pdfFileName = 'havi_osszesito_' . $companyId . '_' . $year . '_' . $month . '_' . time() . '.pdf';
    $pdfPath = $tempDir . '/' . $pdfFileName;
    
    // PDF mentése fájlba
    $pdf->Output($pdfPath, 'F');
    
    return $pdfPath;
}

// Összes cég lekérése
$companies = $company->getAll();

// Cégenkénti összesítők
$companySummaries = [];
foreach ($companies as $comp) {
    $summary = getMonthlySummary($worksheet, $comp['id'], $selectedYear, $selectedMonth);
    if ($summary['total_hours'] > 0 || $summary['transports'] > 0) {
        $companySummaries[] = [
            'company' => $comp,
            'summary' => $summary
        ];
    }
}

$user = getCurrentUser();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Havi zárás - Munkalap App</title>
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
                <h2><i class="bi bi-calendar-month"></i> Havi zárás</h2>
                <p class="text-muted mb-0">Havi összesítő és email küldés</p>
            </div>
        </div>

        <!-- Év/hónap választó -->
        <div class="card table-card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="year" class="form-label">Év</label>
                        <select class="form-select" id="year" name="year">
                            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="month" class="form-label">Hónap</label>
                        <select class="form-select" id="month" name="month">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $selectedMonth == $m ? 'selected' : ''; ?>>
                                    <?php echo getMonthName($m); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Szűrés
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cégenkénti összesítő táblázat -->
        <div class="card table-card">
            <div class="card-body">
                <?php if (count($companySummaries) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cég neve</th>
                                    <th>Összes munkaóra</th>
                                    <th>Átalány órák</th>
                                    <th>Eseti órák</th>
                                    <th>Kiszállások száma</th>
                                    <th class="text-end">Műveletek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companySummaries as $item): ?>
                                    <tr>
                                        <td><strong><?php echo escape($item['company']['name']); ?></strong></td>
                                        <td><?php echo number_format($item['summary']['total_hours'], 2); ?> óra</td>
                                        <td><?php echo number_format($item['summary']['lump_hours'], 2); ?> óra</td>
                                        <td><?php echo number_format($item['summary']['case_hours'], 2); ?> óra</td>
                                        <td><?php echo $item['summary']['transports']; ?> db</td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <a href="summary_pdf.php?company_id=<?php echo $item['company']['id']; ?>&year=<?php echo $selectedYear; ?>&month=<?php echo $selectedMonth; ?>" 
                                                   target="_blank"
                                                   class="btn btn-sm btn-outline-primary" title="PDF letöltése">
                                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                                </a>
                                                <?php if (!empty($item['company']['email'])): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-success show-summary-modal" 
                                                            data-company-id="<?php echo $item['company']['id']; ?>"
                                                            data-company-name="<?php echo escape($item['company']['name']); ?>"
                                                            data-company-email="<?php echo escape($item['company']['email']); ?>"
                                                            data-year="<?php echo $selectedYear; ?>"
                                                            data-month="<?php echo $selectedMonth; ?>"
                                                            data-total-hours="<?php echo $item['summary']['total_hours']; ?>"
                                                            data-lump-hours="<?php echo $item['summary']['lump_hours']; ?>"
                                                            data-case-hours="<?php echo $item['summary']['case_hours']; ?>"
                                                            data-transports="<?php echo $item['summary']['transports']; ?>">
                                                        <i class="bi bi-envelope"></i> Elszámolás
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Nincs email cím">
                                                        <i class="bi bi-envelope"></i> Email
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted mt-3">Nincs munkalap a kiválasztott időszakban.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Összesítő Modal -->
    <div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="summaryModalLabel">
                        <i class="bi bi-calculator"></i> Havi Elszámolás - <span id="modalCompanyName"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6><strong>Időszak:</strong> <span id="modalPeriod"></span></h6>
                        <h6><strong>Cég:</strong> <span id="modalCompanyNameBody"></span></h6>
                        <h6><strong>Email cím:</strong> <span id="modalCompanyEmail"></span></h6>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-graph-up"></i> Összesítés</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td style="width: 60%;"><strong>Összes munkaóra</strong></td>
                                        <td style="width: 40%;" class="text-end"><strong id="modalTotalHours"></strong> óra</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Átalány órák</strong></td>
                                        <td class="text-end"><strong id="modalLumpHours"></strong> óra</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Eseti órák</strong></td>
                                        <td class="text-end"><strong id="modalCaseHours"></strong> óra</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kiszállások száma</strong></td>
                                        <td class="text-end"><strong id="modalTransports"></strong> db</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="" id="emailForm" style="display: inline;">
                        <input type="hidden" name="company_id" id="modalCompanyId">
                        <input type="hidden" name="year" id="modalYear">
                        <input type="hidden" name="month" id="modalMonth">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Bezárás
                        </button>
                        <a href="#" id="modalPdfLink" target="_blank" class="btn btn-primary">
                            <i class="bi bi-file-earmark-pdf"></i> PDF letöltése
                        </a>
                        <button type="submit" name="send_email" class="btn btn-success">
                            <i class="bi bi-envelope"></i> Email küldése
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal megnyitása az összesítő adatokkal
        document.querySelectorAll('.show-summary-modal').forEach(function(button) {
            button.addEventListener('click', function() {
                const companyId = this.getAttribute('data-company-id');
                const companyName = this.getAttribute('data-company-name');
                const companyEmail = this.getAttribute('data-company-email');
                const year = this.getAttribute('data-year');
                const month = this.getAttribute('data-month');
                const totalHours = parseFloat(this.getAttribute('data-total-hours'));
                const lumpHours = parseFloat(this.getAttribute('data-lump-hours'));
                const caseHours = parseFloat(this.getAttribute('data-case-hours'));
                const transports = parseInt(this.getAttribute('data-transports'));
                
                // Hónap neve
                const monthNames = {
                    1: 'január', 2: 'február', 3: 'március', 4: 'április',
                    5: 'május', 6: 'június', 7: 'július', 8: 'augusztus',
                    9: 'szeptember', 10: 'október', 11: 'november', 12: 'december'
                };
                const monthName = monthNames[parseInt(month)] || month;
                
                // Modal adatok kitöltése
                document.getElementById('modalCompanyName').textContent = companyName;
                document.getElementById('modalCompanyNameBody').textContent = companyName;
                document.getElementById('modalCompanyEmail').textContent = companyEmail;
                document.getElementById('modalPeriod').textContent = year + '. ' + monthName;
                document.getElementById('modalTotalHours').textContent = totalHours.toFixed(2);
                document.getElementById('modalLumpHours').textContent = lumpHours.toFixed(2);
                document.getElementById('modalCaseHours').textContent = caseHours.toFixed(2);
                document.getElementById('modalTransports').textContent = transports;
                
                // Form adatok
                document.getElementById('modalCompanyId').value = companyId;
                document.getElementById('modalYear').value = year;
                document.getElementById('modalMonth').value = month;
                
                // PDF link
                document.getElementById('modalPdfLink').href = 'summary_pdf.php?company_id=' + companyId + '&year=' + year + '&month=' + month;
                
                // Modal megnyitása
                const modal = new bootstrap.Modal(document.getElementById('summaryModal'));
                modal.show();
            });
        });
    </script>
</body>
</html>

