<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../classes/Company.php';

$company = new Company();
$errors = [];

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

$data = [
    'name' => $existing['name'],
    'address' => $existing['address'] ?? '',
    'tax_number' => $existing['tax_number'] ?? '',
    'email' => $existing['email'] ?? '',
    'contact_person' => $existing['contact_person'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Adatok begyűjtése
    $data['name'] = trim($_POST['name'] ?? '');
    $data['address'] = trim($_POST['address'] ?? '');
    $data['tax_number'] = trim($_POST['tax_number'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['contact_person'] = trim($_POST['contact_person'] ?? '');
    
    // Validáció
    if (empty($data['name'])) {
        $errors[] = 'A cég neve kötelező!';
    }
    
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Az email cím formátuma nem megfelelő!';
    }
    
    // Ha nincs hiba, mentés
    if (empty($errors)) {
        try {
            if ($company->update($id, $data)) {
                setFlashMessage('success', 'A cég sikeresen frissítve!');
                header('Location: list.php');
                exit();
            } else {
                $errors[] = 'Hiba történt a mentés során!';
            }
        } catch (Exception $e) {
            $errors[] = 'Hiba történt: ' . $e->getMessage();
            error_log("Company update error: " . $e->getMessage());
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
    <title>Cég szerkesztése - Munkalap App</title>
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
            <div class="col-md-8">
                <!-- Fejléc -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-pencil"></i> Cég szerkesztése</h2>
                        <p class="text-muted mb-0">Módosítsa a cég adatait</p>
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
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    Cég neve <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control <?php echo (!empty($errors) && empty($data['name'])) ? 'is-invalid' : ''; ?>" 
                                    id="name" 
                                    name="name" 
                                    value="<?php echo escape($data['name']); ?>" 
                                    required
                                    placeholder="Például: ABC Kft."
                                >
                                <div class="invalid-feedback">
                                    A cég neve kötelező!
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Cím</label>
                                <textarea 
                                    class="form-control" 
                                    id="address" 
                                    name="address" 
                                    rows="3"
                                    placeholder="Például: 1234 Budapest, Fő utca 1."
                                ><?php echo escape($data['address']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="tax_number" class="form-label">Adószám</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="tax_number" 
                                    name="tax_number" 
                                    value="<?php echo escape($data['tax_number']); ?>"
                                    placeholder="Például: 12345678-1-23"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email cím</label>
                                <input 
                                    type="email" 
                                    class="form-control <?php echo (!empty($errors) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) ? 'is-invalid' : ''; ?>" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo escape($data['email']); ?>"
                                    placeholder="Például: info@pelda.hu"
                                >
                                <div class="invalid-feedback">
                                    Az email cím formátuma nem megfelelő!
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Kapcsolattartó</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="contact_person" 
                                    name="contact_person" 
                                    value="<?php echo escape($data['contact_person']); ?>"
                                    placeholder="Például: Kovács János"
                                >
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
</body>
</html>


