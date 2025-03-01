<?php
/**
 * app/helpers/AuthHelper.php
 * Helper do obsługi autentykacji w aplikacji Q-ZTWS
 */

class AuthHelper {
    /**
     * Sprawdza czy użytkownik jest zalogowany
     * 
     * @return bool Czy użytkownik jest zalogowany
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Sprawdza czy użytkownik ma uprawnienia administratora
     * 
     * @return bool Czy użytkownik jest administratorem
     */
    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Tworzy sesję użytkownika po zalogowaniu
     * 
     * @param User $user Obiekt użytkownika
     * @return void
     */
    public static function createUserSession($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->first_name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['lang'] = $user->language;
    }
    
    /**
     * Wylogowuje użytkownika
     * 
     * @return void
     */
    public static function logout() {
        // Usuń wszystkie zmienne sesyjne
        session_unset();
        
        // Zniszcz sesję
        session_destroy();
        
        // Usuń ciasteczko "zapamiętaj mnie"
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    /**
     * Ustawia ciasteczko "zapamiętaj mnie"
     * 
     * @param User $user Obiekt użytkownika
     * @return bool Czy operacja się powiodła
     */
    public static function setRememberMeCookie($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 dni
        
        // Hashowanie tokenu
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        
        // Zapisanie tokenu w bazie danych
        $db = Database::getInstance();
        $db->query("UPDATE users SET remember_token = :token WHERE id = :id");
        $db->bind(':token', $tokenHash);
        $db->bind(':id', $user->id);
        
        if (!$db->execute()) {
            return false;
        }
        
        // Ustawienie ciasteczka
        return setcookie('remember_token', $token, $expiry, '/', '', true, true);
    }
    
    /**
     * Loguje użytkownika przez token "zapamiętaj mnie"
     * 
     * @return bool Czy operacja się powiodła
     */
    public static function loginByRememberMe() {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        
        // Pobieranie użytkowników z tokenami
        $db = Database::getInstance();
        $db->query("SELECT * FROM users WHERE remember_token IS NOT NULL");
        $users = $db->fetchAll();
        
        foreach ($users as $userData) {
            // Weryfikacja tokenu
            if (password_verify($token, $userData['remember_token'])) {
                // Utworzenie obiektu użytkownika
                $user = new User();
                $user->getById($userData['id']);
                
                // Sprawdzenie statusu konta
                if ($user->status !== 'active') {
                    return false;
                }
                
                // Utworzenie sesji
                self::createUserSession($user);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sprawdza czy użytkownik przekroczył limit prób logowania
     * 
     * @param string $ip Adres IP użytkownika
     * @return array Status limitu prób logowania
     */
    public static function checkLoginAttemptsLimit($ip) {
        $settings = new Setting();
        $maxAttempts = $settings->getLoginAttemptsLimit();
        $lockoutTime = $settings->getLoginLockoutTime();
        
        $attempts = $_SESSION['login_attempts'][$ip] ?? 0;
        
        $status = [
            'exceeded' => false,
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
            'time_left' => 0
        ];
        
        if ($attempts >= $maxAttempts) {
            $lastAttempt = $_SESSION['login_last_attempt'][$ip] ?? 0;
            $timeElapsed = time() - $lastAttempt;
            
            if ($timeElapsed < $lockoutTime * 60) {
                $status['exceeded'] = true;
                $status['time_left'] = ceil(($lockoutTime * 60 - $timeElapsed) / 60);
            }
        }
        
        return $status;
    }
    
    /**
     * Zwiększa licznik prób logowania
     * 
     * @param string $ip Adres IP użytkownika
     * @return void
     */
    public static function incrementLoginAttempts($ip) {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        if (!isset($_SESSION['login_attempts'][$ip])) {
            $_SESSION['login_attempts'][$ip] = 0;
        }
        
        $_SESSION['login_attempts'][$ip]++;
        $_SESSION['login_last_attempt'][$ip] = time();
    }
    
    /**
     * Resetuje licznik prób logowania
     * 
     * @param string $ip Adres IP użytkownika
     * @return void
     */
    public static function resetLoginAttempts($ip) {
        if (isset($_SESSION['login_attempts'][$ip])) {
            unset($_SESSION['login_attempts'][$ip]);
        }
        
        if (isset($_SESSION['login_last_attempt'][$ip])) {
            unset($_SESSION['login_last_attempt'][$ip]);
        }
    }
    
    /**
     * Generuje losowe hasło
     * 
     * @param int $length Długość hasła
     * @return string Wygenerowane hasło
     */
    public static function generateRandomPassword($length = 10) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Sprawdza siłę hasła
     * 
     * @param string $password Hasło do sprawdzenia
     * @return array Wynik sprawdzenia siły hasła
     */
    public static function checkPasswordStrength($password) {
        $strength = 0;
        $feedback = [];
        
        // Długość hasła
        if (strlen($password) < 8) {
            $feedback[] = 'Hasło jest za krótkie. Powinno mieć co najmniej 8 znaków.';
        } else {
            $strength++;
        }
        
        // Małe litery
        if (!preg_match('/[a-z]/', $password)) {
            $feedback[] = 'Hasło powinno zawierać małe litery.';
        } else {
            $strength++;
        }
        
        // Duże litery
        if (!preg_match('/[A-Z]/', $password)) {
            $feedback[] = 'Hasło powinno zawierać duże litery.';
        } else {
            $strength++;
        }
        
        // Cyfry
        if (!preg_match('/[0-9]/', $password)) {
            $feedback[] = 'Hasło powinno zawierać cyfry.';
        } else {
            $strength++;
        }
        
        // Znaki specjalne
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $feedback[] = 'Hasło powinno zawierać znaki specjalne.';
        } else {
            $strength++;
        }
        
        $result = [
            'strength' => $strength,
            'max_strength' => 5,
            'feedback' => $feedback,
            'is_strong' => ($strength >= 4)
        ];
        
        return $result;
    }
    
    /**
     * Sprawdza, czy adres e-mail jest na czarnej liście
     * 
     * @param string $email Adres e-mail do sprawdzenia
     * @return bool Czy adres jest na czarnej liście
     */
    public static function isEmailBlacklisted($email) {
        // Lista domen tymczasowych e-maili
        $temporaryEmailDomains = [
            'tempmail.com', 'temp-mail.org', 'mailinator.com', 'guerrillamail.com',
            'yopmail.com', 'sharklasers.com', 'throwawaymail.com', 'getairmail.com',
            'trashmail.com', '10minutemail.com', 'mailnesia.com', 'dispostable.com'
        ];
        
        $domain = substr(strrchr($email, '@'), 1);
        
        return in_array($domain, $temporaryEmailDomains);
    }
    
    /**
     * Sprawdza, czy IP jest na czarnej liście
     * 
     * @param string $ip Adres IP do sprawdzenia
     * @return bool Czy IP jest na czarnej liście
     */
    public static function isIpBlacklisted($ip) {
        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) FROM blacklisted_ips WHERE ip = :ip");
        $db->bind(':ip', $ip);
        
        return $db->fetchColumn() > 0;
    }
}
