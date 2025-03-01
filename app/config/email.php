<?php
/**
 * app/config/email.php
 * Konfiguracja obsługi poczty e-mail dla aplikacji Q-ZTWS
 */

/**
 * Klasa EmailService do obsługi wysyłki e-maili
 */
class EmailService {
    /**
     * Pobiera ustawienia serwera SMTP z bazy danych
     * 
     * @return array Tablica z ustawieniami SMTP
     */
    public static function getSmtpSettings() {
        $db = Database::getInstance();
        $settings = [];
        
        $keys = [
            'smtp_host', 'smtp_port', 'smtp_encryption',
            'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'
        ];
        
        foreach ($keys as $key) {
            $db->query("SELECT setting_value FROM settings WHERE setting_key = :key");
            $db->bind(':key', $key);
            $value = $db->fetchColumn();
            $settings[$key] = $value !== false ? $value : '';
        }
        
        return $settings;
    }
    
    /**
     * Wysyła e-mail
     * 
     * @param string $to Adres e-mail odbiorcy
     * @param string $subject Temat wiadomości
     * @param string $body Treść wiadomości
     * @param array $attachments Załączniki (opcjonalne)
     * @return bool Czy e-mail został wysłany
     */
    public static function send($to, $subject, $body, $attachments = []) {
        // Sprawdź czy biblioteka PHPMailer jest dostępna
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
                error_log("Błąd wysyłki e-mail: Brak wymaganych bibliotek. Uruchom 'composer install'.");
                return false;
            }
            require_once ROOT_PATH . '/vendor/autoload.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $settings = self::getSmtpSettings();
        
        try {
            // Ustawienia serwera
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_username'];
            $mail->Password = $settings['smtp_password'];
            $mail->SMTPSecure = $settings['smtp_encryption'] ?: 'tls';
            $mail->Port = $settings['smtp_port'] ?: 587;
            $mail->CharSet = 'UTF-8';
            
            // Nadawca
            $mail->setFrom($settings['smtp_from_email'], $settings['smtp_from_name']);
            
            // Odbiorca
            $mail->addAddress($to);
            
            // Załączniki
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            // Treść
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
            
            // Wysyłka
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Błąd wysyłki e-mail: " . $mail->ErrorInfo);
            if (DEBUG) {
                echo "Błąd wysyłki e-mail: " . $mail->ErrorInfo;
            }
            return false;
        }
    }
    
    /**
     * Wysyła e-mail na podstawie szablonu
     * 
     * @param string $to Adres e-mail odbiorcy
     * @param string $templateKey Klucz szablonu
     * @param array $data Dane do podstawienia w szablonie
     * @param string $lang Język (domyślnie z sesji)
     * @return bool Czy e-mail został wysłany
     */
    public static function sendTemplate($to, $templateKey, $data = [], $lang = null) {
        global $lang;
$currentLang = $lang;
        
        if ($lang === null) {
            $lang = $currentLang;
        }
        
        $db = Database::getInstance();
        $db->query("SELECT * FROM email_templates WHERE template_key = :key");
        $db->bind(':key', $templateKey);
        $template = $db->fetch();
        
        if (!$template) {
            error_log("Błąd wysyłki e-mail: Nie znaleziono szablonu o kluczu '{$templateKey}'");
            return false;
        }
        
        $subjectField = 'subject_' . $lang;
        $bodyField = 'body_' . $lang;
        
        // Jeśli nie ma tłumaczenia w wybranym języku, użyj domyślnego (polskiego)
        if (empty($template[$subjectField]) || empty($template[$bodyField])) {
            $subjectField = 'subject_pl';
            $bodyField = 'body_pl';
        }
        
        $subject = $template[$subjectField];
        $body = $template[$bodyField];
        
        // Podstawianie danych w szablonie
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
                $body = str_replace('{{' . $key . '}}', $value, $body);
            }
        }
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Testuje połączenie z serwerem SMTP
     * 
     * @return array Status połączenia i ewentualny komunikat błędu
     */
    public static function testSmtpConnection() {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
                return [
                    'success' => false,
                    'message' => "Brak wymaganych bibliotek. Uruchom 'composer install'."
                ];
            }
            require_once ROOT_PATH . '/vendor/autoload.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $settings = self::getSmtpSettings();
        
        try {
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_username'];
            $mail->Password = $settings['smtp_password'];
            $mail->SMTPSecure = $settings['smtp_encryption'] ?: 'tls';
            $mail->Port = $settings['smtp_port'] ?: 587;
            
            // Ustaw timeout na 10 sekund
            $mail->Timeout = 10;
            
            // Testuj połączenie
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                return [
                    'success' => true,
                    'message' => 'Połączenie z serwerem SMTP nawiązane poprawnie.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Nie można nawiązać połączenia z serwerem SMTP.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Błąd połączenia z serwerem SMTP: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Zapisuje nowe ustawienia SMTP do bazy danych
     * 
     * @param array $settings Ustawienia SMTP
     * @return bool Czy zapis się powiódł
     */
    public static function saveSmtpSettings($settings) {
        $db = Database::getInstance();
        $success = true;
        
        foreach ($settings as $key => $value) {
            $db->query("SELECT id FROM settings WHERE setting_key = :key");
            $db->bind(':key', $key);
            
            if ($db->rowCount() > 0) {
                // Aktualizuj istniejące ustawienie
                $db->query("UPDATE settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = :key");
            } else {
                // Dodaj nowe ustawienie
                $db->query("INSERT INTO settings (setting_key, setting_value, description, updated_at) 
                            VALUES (:key, :value, :description, NOW())");
                $db->bind(':description', 'SMTP Setting');
            }
            
            $db->bind(':key', $key);
            $db->bind(':value', $value);
            
            if (!$db->execute()) {
                $success = false;
                error_log("Błąd zapisywania ustawienia SMTP: {$key}");
            }
        }
        
        return $success;
    }
}
