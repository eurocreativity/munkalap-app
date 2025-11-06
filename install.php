<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Munkalap App - Telepítés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .install-card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border-radius: 15px;
        }
        .step-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 5px;
        }
        .success-box {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .error-box {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card install-card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">🗄️ Munkalap App - Adatbázis Telepítés</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $host = 'localhost';
                        $username = 'root';
                        $password = '';
                        $dbname = 'munkalap_db';
                        
                        $messages = [];
                        $success = false;
                        
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
                            try {
                                // 1. Csatlakozás MySQL-hez
                                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
                                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                $messages[] = ['type' => 'success', 'text' => '✓ Sikeres kapcsolat a MySQL szerverhez'];
                                
                                // 2. Adatbázis létrehozása ha még nincs
                                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                                $messages[] = ['type' => 'success', 'text' => "✓ Adatbázis '$dbname' ellenőrizve/létrehozva"];
                                
                                // 3. Adatbázis kiválasztása
                                $pdo->exec("USE `$dbname`");
                                $messages[] = ['type' => 'success', 'text' => "✓ Adatbázis '$dbname' kiválasztva"];
                                
                                // 4. Táblák létrehozása
                                
                                // Users tábla
                                $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    `username` VARCHAR(50) NOT NULL UNIQUE,
                                    `password` VARCHAR(255) NOT NULL,
                                    `full_name` VARCHAR(100) NOT NULL,
                                    `email` VARCHAR(100) NOT NULL,
                                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    INDEX `idx_username` (`username`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                                $messages[] = ['type' => 'success', 'text' => '✓ Users tábla létrehozva'];
                                
                                // Companies tábla
                                $pdo->exec("CREATE TABLE IF NOT EXISTS `companies` (
                                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    `name` VARCHAR(200) NOT NULL,
                                    `address` TEXT,
                                    `tax_number` VARCHAR(50),
                                    `email` VARCHAR(100),
                                    `contact_person` VARCHAR(100),
                                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                                $messages[] = ['type' => 'success', 'text' => '✓ Companies tábla létrehozva'];
                                
                                // Worksheets tábla
                                $pdo->exec("CREATE TABLE IF NOT EXISTS `worksheets` (
                                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    `company_id` INT(11) UNSIGNED NOT NULL,
                                    `worksheet_number` VARCHAR(50) NOT NULL,
                                    `work_date` DATE NOT NULL,
                                    `work_hours` DECIMAL(5,2) NOT NULL,
                                    `description` TEXT,
                                    `reporter_name` VARCHAR(100),
                                    `device_name` VARCHAR(200),
                                    `worker_name` VARCHAR(100),
                                    `work_type` ENUM('Helyi', 'Távoli') DEFAULT 'Helyi',
                                    `transport_fee` DECIMAL(10,2) DEFAULT 0,
                                    `travel_fee` DECIMAL(10,2) DEFAULT 0,
                                    `payment_type` ENUM('Átalány', 'Eseti') DEFAULT 'Eseti',
                                    `work_time` VARCHAR(10),
                                    `status` VARCHAR(50) DEFAULT 'Aktív',
                                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
                                    INDEX `idx_company_id` (`company_id`),
                                    INDEX `idx_worksheet_number` (`worksheet_number`),
                                    INDEX `idx_work_date` (`work_date`),
                                    INDEX `idx_status` (`status`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                                $messages[] = ['type' => 'success', 'text' => '✓ Worksheets tábla létrehozva'];
                                
                                // Materials tábla
                                $pdo->exec("CREATE TABLE IF NOT EXISTS `materials` (
                                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    `worksheet_id` INT(11) UNSIGNED NOT NULL,
                                    `product_name` VARCHAR(200) NOT NULL,
                                    `quantity` DECIMAL(10,2) NOT NULL,
                                    `unit` VARCHAR(20) DEFAULT 'db',
                                    `net_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
                                    `vat_rate` DECIMAL(5,2) DEFAULT 27.00,
                                    `vat_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                                    `gross_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
                                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (`worksheet_id`) REFERENCES `worksheets`(`id`) ON DELETE CASCADE,
                                    INDEX `idx_worksheet_id` (`worksheet_id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                                $messages[] = ['type' => 'success', 'text' => '✓ Materials tábla létrehozva'];
                                
                                // Settings tábla
                                $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                                    `setting_value` TEXT,
                                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    INDEX `idx_setting_key` (`setting_key`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                                $messages[] = ['type' => 'success', 'text' => '✓ Settings tábla létrehozva'];
                                
                                // Alapértelmezett beállítások beszúrása
                                $defaultSettings = [
                                    ['company_name', 'Euro-Creativity Kft'],
                                    ['company_address', '1234 Budapest, Példa utca 12.'],
                                    ['company_tax_number', '12345678-1-23'],
                                    ['company_email', 'info@euro-creativity.hu'],
                                    ['company_phone', '+36 1 234 5678'],
                                    ['sender_name', 'Munkalap App'],
                                    ['sender_email', 'noreply@munkalap.app'],
                                    ['smtp_host', ''],
                                    ['smtp_port', '587'],
                                    ['smtp_username', ''],
                                    ['smtp_password', ''],
                                    ['smtp_encryption', 'tls'],
                                    ['default_transport_fee', '0'],
                                    ['test_mode', '1']
                                ];
                                
                                $stmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) 
                                                       VALUES (?, ?) 
                                                       ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`");
                                
                                foreach ($defaultSettings as $setting) {
                                    $stmt->execute($setting);
                                }
                                $messages[] = ['type' => 'success', 'text' => '✓ Alapértelmezett beállítások létrehozva'];
                                
                                // Új mezők hozzáadása, ha már létezik a tábla
                                try {
                                    // Ellenőrizzük, hogy létezik-e már a tábla és vannak-e benne mezők
                                    $columns = $pdo->query("SHOW COLUMNS FROM worksheets LIKE 'reporter_name'")->fetch();
                                    if (!$columns) {
                                        // Új mezők hozzáadása egyenként
                                        $alterStatements = [
                                            "ALTER TABLE `worksheets` ADD COLUMN `reporter_name` VARCHAR(100) AFTER `description`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `device_name` VARCHAR(200) AFTER `reporter_name`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `worker_name` VARCHAR(100) AFTER `device_name`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `work_type` ENUM('Helyi', 'Távoli') DEFAULT 'Helyi' AFTER `worker_name`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `travel_fee` DECIMAL(10,2) DEFAULT 0 AFTER `work_type`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `transport_fee` DECIMAL(10,2) DEFAULT 0 AFTER `travel_fee`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `payment_type` ENUM('Átalány', 'Eseti') DEFAULT 'Eseti' AFTER `transport_fee`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `work_time` VARCHAR(10) AFTER `payment_type`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `status` VARCHAR(50) DEFAULT 'Aktív' AFTER `work_time`"
                                        ];
                                        
                                        foreach ($alterStatements as $stmt) {
                                            try {
                                                $pdo->exec($stmt);
                                            } catch (PDOException $e) {
                                                // Ha már létezik a mező, folytatjuk
                                            }
                                        }
                                        
                                        // Indexek hozzáadása
                                        try {
                                            $pdo->exec("ALTER TABLE `worksheets` ADD INDEX `idx_work_date` (`work_date`)");
                                        } catch (PDOException $e) {
                                            // Ha már létezik az index, nem baj
                                        }
                                        
                                        try {
                                            $pdo->exec("ALTER TABLE `worksheets` ADD INDEX `idx_status` (`status`)");
                                        } catch (PDOException $e) {
                                            // Ha már létezik az index, nem baj
                                        }
                                        
                                        $messages[] = ['type' => 'success', 'text' => '✓ Worksheets tábla mezői frissítve'];
                                    } else {
                                        // Ha már léteznek a mezők, csak az újakat adjuk hozzá
                                        $newFields = [
                                            "ALTER TABLE `worksheets` ADD COLUMN `worker_name` VARCHAR(100) AFTER `device_name`",
                                            "ALTER TABLE `worksheets` ADD COLUMN `transport_fee` DECIMAL(10,2) DEFAULT 0 AFTER `travel_fee`"
                                        ];
                                        
                                        // Ellenőrizzük és hozzáadjuk a work_time mező típusát (TIME -> VARCHAR)
                                        $workTimeColumn = $pdo->query("SHOW COLUMNS FROM worksheets LIKE 'work_time'")->fetch();
                                        if ($workTimeColumn && $workTimeColumn['Type'] === 'time') {
                                            try {
                                                $pdo->exec("ALTER TABLE `worksheets` MODIFY COLUMN `work_time` VARCHAR(10)");
                                            } catch (PDOException $e) {
                                                // Ha nem sikerült, nem baj
                                            }
                                        }
                                        
                                        foreach ($newFields as $stmt) {
                                            try {
                                                $pdo->exec($stmt);
                                            } catch (PDOException $e) {
                                                // Ha már létezik a mező, folytatjuk
                                            }
                                        }
                                    }
                                } catch (PDOException $e) {
                                    // Ha nem létezik a tábla, nem baj (új lesz létrehozva)
                                }
                                
                                // 5. Teszt felhasználók beszúrása
                                $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `full_name`, `email`) 
                                                       VALUES (?, ?, ?, ?) 
                                                       ON DUPLICATE KEY UPDATE `username`=`username`");
                                
                                // Admin felhasználó
                                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                                $stmt->execute(['admin', $admin_password, 'Adminisztrátor', 'admin@munkalap.app']);
                                $messages[] = ['type' => 'success', 'text' => '✓ Admin felhasználó létrehozva (admin / admin123)'];
                                
                                // User felhasználó
                                $user_password = password_hash('user123', PASSWORD_DEFAULT);
                                $stmt->execute(['user', $user_password, 'Teszt Felhasználó', 'user@munkalap.app']);
                                $messages[] = ['type' => 'success', 'text' => '✓ User felhasználó létrehozva (user / user123)'];
                                
                                $success = true;
                                $messages[] = ['type' => 'success', 'text' => '<strong>🎉 Telepítés sikeresen befejezve!</strong>'];
                                
                            } catch (PDOException $e) {
                                $messages[] = ['type' => 'error', 'text' => '✗ Hiba történt: ' . $e->getMessage()];
                            }
                        }
                        
                        // Üzenetek kiírása
                        foreach ($messages as $msg) {
                            $class = $msg['type'] === 'success' ? 'success-box' : 'error-box';
                            echo '<div class="step-box ' . $class . '">' . $msg['text'] . '</div>';
                        }
                        
                        if (!$success) {
                            ?>
                            <form method="POST" class="mt-4">
                                <div class="alert alert-info">
                                    <h5>Telepítési információk:</h5>
                                    <ul class="mb-0">
                                        <li>Adatbázis: <strong><?php echo $dbname; ?></strong></li>
                                        <li>MySQL szerver: <strong><?php echo $host; ?></strong></li>
                                        <li>Felhasználó: <strong><?php echo $username; ?></strong></li>
                                    </ul>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="install" class="btn btn-primary btn-lg">
                                        🚀 Telepítés indítása
                                    </button>
                                </div>
                            </form>
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-success mt-4">
                                <h5>✅ Minden kész!</h5>
                                <p class="mb-2">Az adatbázis és táblák sikeresen létre lettek hozva.</p>
                                <p class="mb-0"><strong>Fontos:</strong> Töröld vagy védd jelszóval az install.php fájlt éles környezetben!</p>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-success">🏠 Főoldal</a>
                                <a href="test_db.php" class="btn btn-outline-primary">🧪 Adatbázis teszt</a>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

