<?php
/**
 * Munkalap App - Email küldő osztály
 */
require_once __DIR__ . '/../classes/Settings.php';

class EmailSender {
    private $settings;
    
    public function __construct() {
        $this->settings = new Settings();
    }
    
    /**
     * Email küldése
     */
    public function send($to, $subject, $body, $attachment = null, $attachmentName = null) {
        $testMode = $this->settings->get('test_mode', '1') === '1';
        
        if ($testMode) {
            // Teszt mód - csak logolás
            $logFile = __DIR__ . '/../logs/email_test_' . date('Y-m-d') . '.txt';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logContent = date('Y-m-d H:i:s') . "\n";
            $logContent .= "To: $to\n";
            $logContent .= "Subject: $subject\n";
            $logContent .= "Test Mode: YES (email NEM lett elküldve)\n";
            $logContent .= "---\n";
            $logContent .= $body . "\n";
            $logContent .= "==========================================\n\n";
            
            file_put_contents($logFile, $logContent, FILE_APPEND);
            return ['success' => true, 'test_mode' => true, 'message' => 'Email teszt módban lett mentve: ' . $logFile];
        }
        
        // Valódi email küldés
        $smtpHost = $this->settings->get('smtp_host');
        
        if (!empty($smtpHost)) {
            // SMTP használata (PHPMailer szükséges)
            return $this->sendWithSMTP($to, $subject, $body, $attachment, $attachmentName);
        } else {
            // Egyszerű mail() függvény használata
            return $this->sendWithMail($to, $subject, $body, $attachment, $attachmentName);
        }
    }
    
    /**
     * Email küldése mail() függvénnyel
     */
    private function sendWithMail($to, $subject, $body, $attachment = null, $attachmentName = null) {
        $senderEmail = $this->settings->get('sender_email', 'noreply@munkalap.app');
        $senderName = $this->settings->get('sender_name', 'Munkalap App');
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $senderName <$senderEmail>\r\n";
        $headers .= "Reply-To: $senderEmail\r\n";
        
        if ($attachment && file_exists($attachment)) {
            $fileContent = file_get_contents($attachment);
            $encoded = chunk_split(base64_encode($fileContent));
            
            $boundary = md5(time());
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
            
            $message = "--$boundary\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $body . "\r\n\r\n";
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: application/pdf; name=\"" . ($attachmentName ?? basename($attachment)) . "\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment\r\n\r\n";
            $message .= $encoded . "\r\n";
            $message .= "--$boundary--";
            
            $body = $message;
        }
        
        $result = mail($to, $subject, $body, $headers);
        
        if ($result) {
            return ['success' => true, 'message' => 'Email sikeresen elküldve'];
        } else {
            return ['success' => false, 'message' => 'Email küldés sikertelen'];
        }
    }
    
    /**
     * Email küldése SMTP-vel (PHPMailer)
     */
    private function sendWithSMTP($to, $subject, $body, $attachment = null, $attachmentName = null) {
        // Ha nincs PHPMailer, használjuk a mail() függvényt
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithMail($to, $subject, $body, $attachment, $attachmentName);
        }
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP beállítások
            $mail->isSMTP();
            $mail->Host = $this->settings->get('smtp_host');
            $mail->SMTPAuth = true;
            $mail->Username = $this->settings->get('smtp_username');
            $mail->Password = $this->settings->get('smtp_password');
            $mail->SMTPSecure = $this->settings->get('smtp_encryption', 'tls');
            $mail->Port = $this->settings->get('smtp_port', '587');
            $mail->CharSet = 'UTF-8';
            
            // Küldő
            $mail->setFrom(
                $this->settings->get('sender_email'),
                $this->settings->get('sender_name')
            );
            
            // Címzett
            $mail->addAddress($to);
            
            // Csatolmány
            if ($attachment && file_exists($attachment)) {
                $mail->addAttachment($attachment, $attachmentName ?? basename($attachment));
            }
            
            // Email tartalom
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            
            return ['success' => true, 'message' => 'Email sikeresen elküldve SMTP-vel'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Email küldés hiba: ' . $e->getMessage()];
        }
    }
    
    /**
     * HTML email template generálása
     */
    public function generateEmailTemplate($contactPerson, $year, $month, $summary) {
        $companyName = $this->settings->get('company_name', 'Euro-Creativity Kft');
        $senderName = $this->settings->get('sender_name', 'Munkalap App');
        
        $monthNames = [
            1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április',
            5 => 'május', 6 => 'június', 7 => 'július', 8 => 'augusztus',
            9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december'
        ];
        
        $monthName = $monthNames[(int)$month] ?? $month;
        
        $html = '<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #667eea; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .summary { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #667eea; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Havi Munkalap Összesítő</h2>
        </div>
        <div class="content">
            <p>Tisztelt ' . htmlspecialchars($contactPerson) . '!</p>
            
            <p>Küldöm a ' . $year . '. ' . $monthName . ' havi munkalapokat.</p>
            
            <div class="summary">
                <h3>Összesítés:</h3>
                <ul>
                    <li><strong>Összes munkaóra:</strong> ' . number_format($summary['total_hours'], 2) . ' óra</li>
                    <li><strong>Átalány:</strong> ' . number_format($summary['lump_hours'], 2) . ' óra</li>
                    <li><strong>Eseti:</strong> ' . number_format($summary['case_hours'], 2) . ' óra</li>
                    <li><strong>Kiszállások:</strong> ' . $summary['transports'] . ' db</li>
                </ul>
            </div>
            
            <p>A részletes munkalapokat csatolmányban küldöm.</p>
        </div>
        <div class="footer">
            <p>Üdvözlettel,<br>
            <strong>' . htmlspecialchars($senderName) . '</strong><br>
            ' . htmlspecialchars($companyName) . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}
?>


