<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Worksheet.php';
require_once __DIR__ . '/../classes/Company.php';
require_once __DIR__ . '/../classes/Material.php';

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
    die('TCPDF nincs telepítve!<br><br>
         <strong>Composer telepítés:</strong><br>
         Futtasd le: <code>composer install</code><br><br>
         <strong>VAGY manuális telepítés:</strong><br>
         1. Töltsd le a TCPDF-t: https://github.com/tecnickcom/TCPDF<br>
         2. Másold be: vendor/tecnickcom/tcpdf/<br>
         3. Hozz létre: vendor/autoload.php<br><br>
         Vagy futtasd le a PowerShell scriptet: <code>install_tcpdf_manual.ps1</code>');
}

// TCPDF használata

$worksheet = new Worksheet();
$company = new Company();
$material = new Material();

// Munkalap ID ellenőrzése
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Érvénytelen munkalap azonosító!');
}

// Munkalap lekérése
$ws = $worksheet->getById($id);
if (!$ws) {
    die('A munkalap nem található!');
}

// Cég adatok lekérése
$comp = $company->getById($ws['company_id']);

// Anyagok lekérése
$materials = $material->getByWorksheetId($id);

// PDF létrehozása
// Ha a TCPDF konstansok nincsenek definiálva, definiáljuk őket
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P'); // P = Portrait (Álló)
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}

$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Dokumentum információk
$pdf->SetCreator('Munkalap App');
$pdf->SetAuthor('Euro-Creativity Kft');
$pdf->SetTitle('Munkalap - ' . $ws['worksheet_number']);
$pdf->SetSubject('Munkalap');

// Fejléc és lábléc eltávolítása
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Margók beállítása
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Oldal hozzáadása
$pdf->AddPage();

// Betűtípus beállítása (magyar karakterekhez)
$pdf->SetFont('dejavusans', '', 10);

// Fejléc - Cégek adatai táblázatban
$html = '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';

// Megrendelő cég (bal oldal)
$html .= '<tr>';
$html .= '<td style="width: 50%; background-color: #f0f0f0;"><strong>MEGRENDELŐ CÉG</strong></td>';
$html .= '<td style="width: 50%; background-color: #f0f0f0;"><strong>SZÁLLÍTÓ CÉG</strong></td>';
$html .= '</tr>';

$html .= '<tr>';
// Bal oldal - Megrendelő cég
$html .= '<td style="width: 50%;">';
$html .= '<strong>' . htmlspecialchars($comp['name'] ?? '') . '</strong><br>';
if (!empty($comp['address'])) {
    $html .= htmlspecialchars($comp['address']) . '<br>';
}
if (!empty($comp['tax_number'])) {
    $html .= 'Adószám: ' . htmlspecialchars($comp['tax_number']) . '<br>';
}
if (!empty($comp['email'])) {
    $html .= 'Email: ' . htmlspecialchars($comp['email']) . '<br>';
}
if (!empty($comp['contact_person'])) {
    $html .= 'Kapcsolattartó: ' . htmlspecialchars($comp['contact_person']);
}
$html .= '</td>';

// Jobb oldal - Szállító cég (Euro-Creativity Kft)
$html .= '<td style="width: 50%;">';
$html .= '<strong>Euro-Creativity Kft</strong><br>';
$html .= '1234 Budapest, Példa utca 12.<br>';
$html .= 'Adószám: 12345678-1-23<br>';
$html .= 'Email: info@euro-creativity.hu<br>';
$html .= 'Telefon: +36 1 234 5678';
$html .= '</td>';
$html .= '</tr>';

$html .= '</table>';

// Munkalap adatok
$html .= '<br><table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';

// Munkalap szám
$html .= '<tr>';
$html .= '<td style="width: 30%; background-color: #e8e8e8;"><strong>Munkalap száma:</strong></td>';
$html .= '<td style="width: 70%;">' . htmlspecialchars($ws['worksheet_number']) . '</td>';
$html .= '</tr>';

// Dátum
$html .= '<tr>';
$html .= '<td style="background-color: #e8e8e8;"><strong>Dátum:</strong></td>';
$html .= '<td>' . date('Y.m.d', strtotime($ws['work_date'])) . '</td>';
$html .= '</tr>';

// Munkavégzés ideje
if (!empty($ws['work_time'])) {
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Munkavégzés ideje:</strong></td>';
    $html .= '<td>' . htmlspecialchars($ws['work_time']) . '</td>';
    $html .= '</tr>';
}

// Munka órák
$html .= '<tr>';
$html .= '<td style="background-color: #e8e8e8;"><strong>Munka órák száma:</strong></td>';
$html .= '<td>' . number_format($ws['work_hours'], 2) . ' óra</td>';
$html .= '</tr>';

// Munka típusa
$html .= '<tr>';
$html .= '<td style="background-color: #e8e8e8;"><strong>Munka típusa:</strong></td>';
$html .= '<td>' . htmlspecialchars($ws['work_type'] ?? 'Helyi') . '</td>';
$html .= '</tr>';

// Kiszállási díj (ha helyi)
if ($ws['work_type'] === 'Helyi' && !empty($ws['transport_fee'])) {
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Kiszállási díj:</strong></td>';
    $html .= '<td>' . number_format($ws['transport_fee'], 0) . ' Ft</td>';
    $html .= '</tr>';
}

// Díjazás típusa
$html .= '<tr>';
$html .= '<td style="background-color: #e8e8e8;"><strong>Díjazás:</strong></td>';
$html .= '<td>' . htmlspecialchars($ws['payment_type'] ?? 'Eseti') . '</td>';
$html .= '</tr>';

// Hiba bejelentő neve
if (!empty($ws['reporter_name'])) {
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Hiba bejelentő neve:</strong></td>';
    $html .= '<td>' . htmlspecialchars($ws['reporter_name']) . '</td>';
    $html .= '</tr>';
}

// Eszköz neve
if (!empty($ws['device_name'])) {
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Eszköz megnevezése:</strong></td>';
    $html .= '<td>' . htmlspecialchars($ws['device_name']) . '</td>';
    $html .= '</tr>';
}

// Munkát végző neve
if (!empty($ws['worker_name'])) {
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Munkát végző neve:</strong></td>';
    $html .= '<td>' . htmlspecialchars($ws['worker_name']) . '</td>';
    $html .= '</tr>';
}

// Munka leírása
if (!empty($ws['description'])) {
    $html .= '<tr>';
    $html .= '<td style="background-color: #e8e8e8;"><strong>Munka leírása:</strong></td>';
    $html .= '<td>' . nl2br(htmlspecialchars($ws['description'])) . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';

// Anyagok táblázat (ha van)
if (count($materials) > 0) {
    $html .= '<br><h3 style="font-size: 12pt;">ANYAGFELHASZNÁLÁS</h3>';
    $html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">';
    
    // Fejléc
    $html .= '<tr style="background-color: #e8e8e8;">';
    $html .= '<th style="width: 30%;"><strong>Termék neve</strong></th>';
    $html .= '<th style="width: 10%;"><strong>Mennyiség</strong></th>';
    $html .= '<th style="width: 10%;"><strong>Mért.egys.</strong></th>';
    $html .= '<th style="width: 12%;"><strong>Nettó ár (Ft)</strong></th>';
    $html .= '<th style="width: 10%;"><strong>ÁFA %</strong></th>';
    $html .= '<th style="width: 13%;"><strong>ÁFA összeg (Ft)</strong></th>';
    $html .= '<th style="width: 15%;"><strong>Bruttó ár (Ft)</strong></th>';
    $html .= '</tr>';
    
    $totalNet = 0;
    $totalVat = 0;
    $totalGross = 0;
    
    // Anyag sorok
    foreach ($materials as $mat) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($mat['product_name']) . '</td>';
        $html .= '<td style="text-align: right;">' . number_format($mat['quantity'], 2) . '</td>';
        $html .= '<td>' . htmlspecialchars($mat['unit']) . '</td>';
        $html .= '<td style="text-align: right;">' . number_format($mat['net_price'], 0) . '</td>';
        $html .= '<td style="text-align: right;">' . number_format($mat['vat_rate'], 0) . '%</td>';
        $html .= '<td style="text-align: right;">' . number_format($mat['vat_amount'], 0) . '</td>';
        $html .= '<td style="text-align: right;"><strong>' . number_format($mat['gross_price'], 0) . '</strong></td>';
        $html .= '</tr>';
        
        $totalNet += $mat['net_price'];
        $totalVat += $mat['vat_amount'];
        $totalGross += $mat['gross_price'];
    }
    
    // Összesítő sor
    $html .= '<tr style="background-color: #f0f0f0;">';
    $html .= '<td colspan="3" style="text-align: right;"><strong>ÖSSZESEN:</strong></td>';
    $html .= '<td style="text-align: right;"><strong>' . number_format($totalNet, 0) . '</strong></td>';
    $html .= '<td></td>';
    $html .= '<td style="text-align: right;"><strong>' . number_format($totalVat, 0) . '</strong></td>';
    $html .= '<td style="text-align: right;"><strong>' . number_format($totalGross, 0) . '</strong></td>';
    $html .= '</tr>';
    
    $html .= '</table>';
}

// HTML kiírása PDF-be
$pdf->writeHTML($html, true, false, true, false, '');

// PDF generálása és küldése böngészőnek
$pdf->Output('munkalap_' . $ws['worksheet_number'] . '.pdf', 'I');
exit();
?>

