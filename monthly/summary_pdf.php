<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Worksheet.php';
require_once __DIR__ . '/../classes/Company.php';
require_once __DIR__ . '/../classes/Material.php';
require_once __DIR__ . '/../classes/Settings.php';

// Composer autoload vagy manuális TCPDF
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php')) {
    // Manuális telepítés esetén
    require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    // TCPDF konstansok definiálása
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
    die('TCPDF nincs telepítve! Látogasd meg a MANUAL_TCPDF_INSTALL.md fájlt a telepítési útmutatóért.');
}

$worksheet = new Worksheet();
$company = new Company();
$material = new Material();
$settings = new Settings();

// Paraméterek ellenőrzése
$companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

if ($companyId <= 0) {
    die('Érvénytelen cég azonosító!');
}

// Cég adatok lekérése
$comp = $company->getById($companyId);
if (!$comp) {
    die('A cég nem található!');
}

// Munkalapok lekérése az adott hónapra
$filters = [
    'company_id' => $companyId,
    'date_from' => sprintf('%04d-%02d-01', $year, $month),
    'date_to' => sprintf('%04d-%02d-%d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year)))
];

$worksheets = $worksheet->getAll($filters);

if (count($worksheets) === 0) {
    die('Nincs munkalap a kiválasztott időszakban!');
}

// Hónap neve
$monthNames = [
    1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április',
    5 => 'május', 6 => 'június', 7 => 'július', 8 => 'augusztus',
    9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december'
];
$monthName = $monthNames[$month] ?? $month;

// PDF konstansok
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}

// PDF létrehozása
$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Munkalap App');
$pdf->SetAuthor($settings->get('company_name', 'Euro-Creativity Kft'));
$pdf->SetTitle('Havi összesítő - ' . $comp['name'] . ' - ' . $year . '. ' . $monthName);
$pdf->SetSubject('Havi összesítő');

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

// Összesítő táblázat
$html = '<h2 style="text-align: center;">Havi Munkalap Összesítő</h2>';
$html .= '<p style="text-align: center;"><strong>' . $year . '. ' . $monthName . '</strong></p>';
$html .= '<br>';

// Cég adatai
$html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
$html .= '<tr>';
$html .= '<td style="width: 50%; background-color: #f0f0f0;"><strong>MEGRENDELŐ CÉG</strong></td>';
$html .= '<td style="width: 50%; background-color: #f0f0f0;"><strong>SZÁLLÍTÓ CÉG</strong></td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td>';
$html .= '<strong>' . htmlspecialchars($comp['name']) . '</strong><br>';
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
$html .= '<td>';
$html .= '<strong>' . htmlspecialchars($settings->get('company_name', 'Euro-Creativity Kft')) . '</strong><br>';
$html .= htmlspecialchars($settings->get('company_address', '')) . '<br>';
$html .= 'Adószám: ' . htmlspecialchars($settings->get('company_tax_number', '')) . '<br>';
$html .= 'Email: ' . htmlspecialchars($settings->get('company_email', ''));
$html .= '</td>';
$html .= '</tr>';
$html .= '</table>';

$html .= '<br><h3>Összesítés</h3>';
$html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
$html .= '<tr style="background-color: #e8e8e8;">';
$html .= '<th style="width: 50%;">Kategória</th>';
$html .= '<th style="width: 50%;">Érték</th>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td><strong>Összes munkaóra</strong></td>';
$html .= '<td>' . number_format($totalHours, 2) . ' óra</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td><strong>Átalány órák</strong></td>';
$html .= '<td>' . number_format($lumpHours, 2) . ' óra</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td><strong>Eseti órák</strong></td>';
$html .= '<td>' . number_format($caseHours, 2) . ' óra</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td><strong>Kiszállások száma</strong></td>';
$html .= '<td>' . $transports . ' db</td>';
$html .= '</tr>';
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Munkalapok részletei
foreach ($worksheets as $ws) {
    $pdf->AddPage();
    
    $materials = $material->getByWorksheetId($ws['id']);
    
    // Munkalap fejléc
    $html = '<h3>Munkalap: ' . htmlspecialchars($ws['worksheet_number']) . '</h3>';
    
    // Munkalap adatok
    $html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
    
    $html .= '<tr>';
    $html .= '<td style="width: 30%; background-color: #e8e8e8;"><strong>Munkalap száma:</strong></td>';
    $html .= '<td style="width: 70%;">' . htmlspecialchars($ws['worksheet_number']) . '</td>';
    $html .= '</tr>';
    
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Dátum:</strong></td>';
    $html .= '<td>' . date('Y.m.d', strtotime($ws['work_date'])) . '</td>';
    $html .= '</tr>';
    
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Munka órák száma:</strong></td>';
    $html .= '<td>' . number_format($ws['work_hours'], 2) . ' óra</td>';
    $html .= '</tr>';
    
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Munka típusa:</strong></td>';
    $html .= '<td>' . htmlspecialchars($ws['work_type'] ?? 'Helyi') . '</td>';
    $html .= '</tr>';
    
    if ($ws['work_type'] === 'Helyi' && !empty($ws['transport_fee'])) {
        $html .= '<tr>';
        $html .= '<td style="background-color: #e8e8e8;"><strong>Kiszállási díj:</strong></td>';
        $html .= '<td>' . number_format($ws['transport_fee'], 0) . ' Ft</td>';
        $html .= '</tr>';
    }
    
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Díjazás:</strong></td>';
    $html .= '<td>' . htmlspecialchars($ws['payment_type'] ?? 'Eseti') . '</td>';
    $html .= '</tr>';
    
    if (!empty($ws['description'])) {
        $html .= '<tr>';
        $html .= '<td style="background-color: #e8e8e8;"><strong>Munka leírása:</strong></td>';
        $html .= '<td>' . nl2br(htmlspecialchars($ws['description'])) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Anyagok táblázat (ha van)
    if (count($materials) > 0) {
        $html .= '<br><h4>Anyagok</h4>';
        $html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
        $html .= '<tr style="background-color: #e8e8e8;">';
        $html .= '<th>Termék neve</th>';
        $html .= '<th>Mennyiség</th>';
        $html .= '<th>Mért.egys.</th>';
        $html .= '<th>Nettó ár (Ft)</th>';
        $html .= '<th>Bruttó ár (Ft)</th>';
        $html .= '</tr>';
        
        foreach ($materials as $mat) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($mat['product_name']) . '</td>';
            $html .= '<td style="text-align: right;">' . number_format($mat['quantity'], 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($mat['unit']) . '</td>';
            $html .= '<td style="text-align: right;">' . number_format($mat['net_price'], 0) . '</td>';
            $html .= '<td style="text-align: right;">' . number_format($mat['gross_price'], 0) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
    }
    
    $pdf->writeHTML($html, true, false, true, false, '');
}

// PDF generálása
$pdf->Output('havi_osszesito_' . $comp['name'] . '_' . $year . '_' . $month . '.pdf', 'I');
exit();
?>

