<?php
/**
 * app/helpers/EmailHelper.php
 * Helper do obsługi e-maili w aplikacji Q-ZTWS
 */

class EmailHelper {
    /**
     * Wysyła e-mail aktywacyjny do użytkownika
     * 
     * @param User $user Obiekt użytkownika
     * @return bool Czy e-mail został wysłany
     */
    public static function sendActivationEmail($user) {
        $activationUrl = APP_URL . '/activate/' . $user->activation_code;
        
        $data = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'activation_url' => $activationUrl,
            'activation_code' => $user->activation_code,
            'app_name' => APP_NAME,
            'app_url' => APP_URL
        ];
        
        return EmailService::sendTemplate($user->email, 'activation', $data, $user->language);
    }
    
    /**
     * Wysyła e-mail resetowania hasła do użytkownika
     * 
     * @param User $user Obiekt użytkownika
     * @return bool Czy e-mail został wysłany
     */
    public static function sendPasswordResetEmail($user) {
        $resetUrl = APP_URL . '/reset-password/' . $user->reset_token;
        
        $data = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'reset_url' => $resetUrl,
            'reset_token' => $user->reset_token,
            'app_name' => APP_NAME,
            'app_url' => APP_URL
        ];
        
        return EmailService::sendTemplate($user->email, 'password_reset', $data, $user->language);
    }
    
    /**
     * Wysyła testowy e-mail
     * 
     * @param string $email Adres e-mail odbiorcy
     * @param string $templateKey Klucz szablonu
     * @param string $lang Kod języka (pl lub en)
     * @return bool Czy e-mail został wysłany
     */
    public static function sendTestEmail($email, $templateKey, $lang = 'pl') {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'Użytkownik',
            'email' => $email,
            'activation_url' => APP_URL . '/activate/test-code',
            'activation_code' => 'test-code',
            'reset_url' => APP_URL . '/reset-password/test-token',
            'reset_token' => 'test-token',
            'app_name' => APP_NAME,
            'app_url' => APP_URL
        ];
        
        return EmailService::sendTemplate($email, $templateKey, $data, $lang);
    }
    
    /**
     * Waliduje adres e-mail
     * 
     * @param string $email Adres e-mail do walidacji
     * @return bool Czy adres e-mail jest poprawny
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sprawdza, czy domena e-mail istnieje i ma prawidłową konfigurację DNS
     * 
     * @param string $email Adres e-mail do sprawdzenia
     * @return bool Czy domena jest poprawna
     */
    public static function validateEmailDomain($email) {
        $domain = substr(strrchr($email, '@'), 1);
        
        if (!$domain) {
            return false;
        }
        
        // Sprawdź rekordy MX domeny
        return checkdnsrr($domain, 'MX');
    }
    
    /**
     * Sanityzuje adres e-mail
     * 
     * @param string $email Adres e-mail do sanityzacji
     * @return string Sanityzowany adres e-mail
     */
    public static function sanitizeEmail($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sprawdza status SMTP
     * 
     * @return array Status SMTP
     */
    public static function checkSmtpStatus() {
        $settings = new Setting();
        $smtp = $settings->getSmtpSettings();
        
        $status = [
            'configured' => false,
            'message' => '',
            'details' => []
        ];
        
        // Sprawdź, czy ustawienia SMTP są skonfigurowane
        if (empty($smtp['host']) || empty($smtp['username']) || empty($smtp['password'])) {
            $status['message'] = 'Serwer SMTP nie jest w pełni skonfigurowany.';
            return $status;
        }
        
        $status['configured'] = true;
        
        // Testuj połączenie z serwerem SMTP
        $testResult = EmailService::testSmtpConnection();
        
        if ($testResult['success']) {
            $status['message'] = 'Połączenie z serwerem SMTP działa poprawnie.';
        } else {
            $status['message'] = 'Problem z połączeniem z serwerem SMTP: ' . $testResult['message'];
        }
        
        $status['details'] = [
            'host' => $smtp['host'],
            'port' => $smtp['port'],
            'encryption' => $smtp['encryption'],
            'username' => $smtp['username'],
            'from_email' => $smtp['from_email'],
            'from_name' => $smtp['from_name'],
            'connection_status' => $testResult['success'] ? 'OK' : 'Błąd'
        ];
        
        return $status;
    }
    
    /**
     * Przygotowuje treść HTML do wiadomości e-mail
     * 
     * @param string $content Treść wiadomości
     * @param array $data Dane do podstawienia
     * @return string Przygotowana treść
     */
    public static function prepareHtmlContent($content, $data = []) {
        // Podstawienie zmiennych
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // Dodanie podstawowego stylu, jeśli treść nie zawiera znacznika <html>
        if (strpos($content, '<html') === false) {
            $content = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #3498db; color: white; padding: 10px 20px; text-align: center; }
                    .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; 
                             text-decoration: none; border-radius: 4px; margin: 15px 0; }
                    a { color: #3498db; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>' . APP_NAME . '</h1>
                    </div>
                    ' . $content . '
                    <div class="footer">
                        <p>© ' . date('Y') . ' ' . APP_NAME . ' | Wszelkie prawa zastrzeżone</p>
                        <p>Ta wiadomość została wygenerowana automatycznie, prosimy na nią nie odpowiadać.</p>
                    </div>
                </div>
            </body>
            </html>';
        }
        
        return $content;
    }
}