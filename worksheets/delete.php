<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Worksheet.php';
require_once __DIR__ . '/../classes/Material.php';

// Csak POST kérést fogadunk el - biztonsági ellenőrzés
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Érvénytelen kérés!');
    header('Location: list.php');
    exit();
}

// CSRF token validáció
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('danger', 'Érvénytelen törlési kérés! Token hibás.');
    header('Location: list.php');
    exit();
}

// ID ellenőrzés - SQL injection védelem
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('danger', 'Érvénytelen munkalap azonosító!');
    header('Location: list.php');
    exit();
}

// Delete gomb ellenőrzés - CSRF védelem
if (!isset($_POST['delete'])) {
    setFlashMessage('danger', 'Érvénytelen törlési kérés!');
    header('Location: list.php');
    exit();
}

$id = intval($_POST['id']);

try {
    $worksheet = new Worksheet();
    $materialObj = new Material();

    // Ellenőrizzük, hogy létezik-e a munkalap
    $worksheetData = $worksheet->getById($id);
    if (!$worksheetData) {
        setFlashMessage('danger', 'A munkalap nem található!');
        header('Location: list.php');
        exit();
    }

    // Kapcsolódó anyagok törlése
    $materialObj->deleteByWorksheetId($id);

    // Munkalap törlése
    if ($worksheet->delete($id)) {
        setFlashMessage('success', 'A munkalap sikeresen törölve! (Munkalap szám: ' . escape($worksheetData['worksheet_number']) . ')');
    } else {
        setFlashMessage('danger', 'Hiba történt a munkalap törlése során!');
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Hiba történt: ' . $e->getMessage());
    error_log("Worksheet delete error: " . $e->getMessage());
}

// Vissza a lista oldalra
header('Location: list.php');
exit();
?>
