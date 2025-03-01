<?php
// app/controllers/AuthController.php
// Kontroler obsługujący uwierzytelnianie użytkowników

class AuthController {
    private $userModel;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Wyświetla formularz logowania
     * 
     * @return void
     */
    public function loginForm() {
        // Jeśli użytkownik jest już zalogowany, przekieruj na dashboard
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }
        
        // Wyświetl widok formularza logowania
        include APP_PATH . '/views/auth/login.php';
    }
    
    /**
     * Przetwarza formularz logowania
     * 
     * @return void
     */
    public function login() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('login');
        }
        
        // Pobierz dane z formularza
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        // Sprawdź czy formularz jest kompletny
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = __('login_empty_fields');
            $_SESSION['form_data'] = ['email' => $email];
            redirect('login');
        }
        
        // Sprawdź limity prób logowania
        $this->checkLoginAttempts();
        
        // Sprawdź poprawność danych logowania
        if (!$this->userModel->getByEmail($email)) {
            $this->incrementLoginAttempts();
            $_SESSION['error'] = __('login_invalid');
            $_SESSION['form_data'] = ['email' => $email];
            redirect('login');
        }
        
        // Sprawdź czy konto jest aktywne
        if ($this->userModel->status !== 'active') {
            $this->incrementLoginAttempts();
            
            if ($this->userModel->status === 'pending') {
                $_SESSION['error'] = __('login_account_not_activated');
            } else {
                $_SESSION['error'] = __('login_account_inactive');
            }
            
            $_SESSION['form_data'] = ['email' => $email];
            redirect('login');
        }
        
        // Sprawdź hasło
        if (!$this->userModel->verifyPassword($password)) {
            $this->incrementLoginAttempts();
            $_SESSION['error'] = __('login_invalid');
            $_SESSION['form_data'] = ['email' => $email];
            redirect('login');
        }
        
        // Zaloguj użytkownika
        $this->createUserSession($this->userModel);
        
        // Ustaw ciasteczko "zapamiętaj mnie", jeśli wybrano
        if ($remember_me) {
            $this->setRememberMeCookie();
        }
        
        // Resetuj licznik prób logowania
        $this->resetLoginAttempts();
        
        // Przekieruj na dashboard
        redirect('dashboard');
    }
    
    /**
     * Wylogowuje użytkownika
     * 
     * @return void
     */
    public function logout() {
        // Usuń wszystkie zmienne sesyjne
        session_unset();
        
        // Zniszcz sesję
        session_destroy();
        
        // Usuń ciasteczko "zapamiętaj mnie"
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Przekieruj na stronę logowania
        redirect('login');
    }
    
    /**
     * Wyświetla formularz rejestracji
     * 
     * @return void
     */
    public function registerForm() {
        // Jeśli użytkownik jest już zalogowany, przekieruj na dashboard
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }
        
        // Wyświetl widok formularza rejestracji
        include APP_PATH . '/views/auth/register.php';
    }
    
    /**
     * Przetwarza formularz rejestracji
     * 
     * @return void
     */
    public function register() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('register');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('register');
        }
        
        // Pobierz dane z formularza
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);
        
        // Walidacja
        $errors = [];
        
        if (empty($firstName)) {
            $errors['first_name'] = __('register_first_name_required');
        }
        
        if (empty($lastName)) {
            $errors['last_name'] = __('register_last_name_required');
        }
        
        if (empty($email)) {
            $errors['email'] = __('register_email_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __('register_email_invalid');
        }
        
        if (empty($password)) {
            $errors['password'] = __('register_password_required');
        } elseif (strlen($password) < 8) {
            $errors['password'] = __('register_password_too_short');
        }
        
        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = __('register_passwords_not_match');
        }
        
        if (!$terms) {
            $errors['terms'] = __('register_terms_required');
        }
        
        // Sprawdź czy adres e-mail jest już zajęty
        if (empty($errors['email']) && $this->userModel->getByEmail($email)) {
            $errors['email'] = __('register_email_exists');
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email
            ];
            redirect('register');
        }
        
        // Utwórz nowego użytkownika
        $this->userModel->first_name = $firstName;
        $this->userModel->last_name = $lastName;
        $this->userModel->email = $email;
        $this->userModel->password = $password;
        $this->userModel->status = 'pending';
        $this->userModel->role = 'user';
        
        if ($this->userModel->register()) {
            // Wyślij e-mail aktywacyjny
            $this->sendActivationEmail($this->userModel);
            
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('register_success');
            
            // Przekieruj na stronę logowania
            redirect('login');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('register_error');
            $_SESSION['form_data'] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email
            ];
            redirect('register');
        }
    }
    
    /**
     * Aktywuje konto użytkownika
     * 
     * @param string $code Kod aktywacyjny
     * @return void
     */
    public function activate($code) {
        // Sprawdź kod aktywacyjny
        if (empty($code) || !$this->userModel->getByActivationCode($code)) {
            $_SESSION['error'] = __('activation_invalid');
            redirect('login');
        }
        
        // Aktywuj konto
        if ($this->userModel->activate()) {
            $_SESSION['success'] = __('activation_success');
        } else {
            $_SESSION['error'] = __('activation_error');
        }
        
        redirect('login');
    }
    
    /**
     * Wyświetla formularz przypomnienia hasła
     * 
     * @return void
     */
    public function forgotPasswordForm() {
        // Jeśli użytkownik jest już zalogowany, przekieruj na dashboard
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }
        
        // Wyświetl widok formularza przypomnienia hasła
        include APP_PATH . '/views/auth/forgot_password.php';
    }
    
    /**
     * Przetwarza formularz przypomnienia hasła
     * 
     * @return void
     */
    public function forgotPassword() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('forgot-password');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('forgot-password');
        }
        
        // Pobierz dane z formularza
        $email = sanitize($_POST['email'] ?? '');
        
        // Walidacja
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = __('forgot_password_email_invalid');
            $_SESSION['form_data'] = ['email' => $email];
            redirect('forgot-password');
        }
        
        // Sprawdź czy istnieje konto o podanym adresie e-mail
        if (!$this->userModel->getByEmail($email)) {
            // Nie informuj, że adres e-mail nie istnieje (bezpieczeństwo)
            $_SESSION['success'] = __('forgot_password_email_sent');
            redirect('login');
        }
        
        // Sprawdź czy konto jest aktywne
        if ($this->userModel->status !== 'active') {
            if ($this->userModel->status === 'pending') {
                $_SESSION['error'] = __('forgot_password_account_not_activated');
            } else {
                $_SESSION['error'] = __('forgot_password_account_inactive');
            }
            redirect('forgot-password');
        }
        
        // Generuj token resetowania hasła
        if ($this->userModel->generateResetToken()) {
            // Wyślij e-mail z linkiem do resetowania hasła
            $this->sendResetPasswordEmail($this->userModel);
            
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('forgot_password_email_sent');
        } else {
            $_SESSION['error'] = __('forgot_password_error');
        }
        
        redirect('login');
    }
    
    /**
     * Wyświetla formularz resetowania hasła
     * 
     * @param string $token Token resetowania hasła
     * @return void
     */
    public function resetPasswordForm($token) {
        // Jeśli użytkownik jest już zalogowany, przekieruj na dashboard
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }
        
        // Sprawdź token resetowania hasła
        if (empty($token) || !$this->userModel->getByResetToken($token)) {
            $_SESSION['error'] = __('reset_password_token_invalid');
            redirect('login');
        }
        
        // Wyświetl widok formularza resetowania hasła
        include APP_PATH . '/views/auth/reset_password.php';
    }
    
    /**
     * Przetwarza formularz resetowania hasła
     * 
     * @return void
     */
    public function resetPassword() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('login');
        }
        
        // Pobierz dane z formularza
        $token = sanitize($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Sprawdź token resetowania hasła
        if (empty($token) || !$this->userModel->getByResetToken($token)) {
            $_SESSION['error'] = __('reset_password_token_invalid');
            redirect('login');
        }
        
        // Walidacja
        $errors = [];
        
        if (empty($password)) {
            $errors['password'] = __('reset_password_password_required');
        } elseif (strlen($password) < 8) {
            $errors['password'] = __('reset_password_password_too_short');
        }
        
        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = __('reset_password_passwords_not_match');
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('reset-password/' . $token);
        }
        
        // Aktualizuj hasło
        if ($this->userModel->updatePassword($password)) {
            // Wyczyść token resetowania hasła
            $this->userModel->clearResetToken();
            
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('reset_password_success');
        } else {
            $_SESSION['error'] = __('reset_password_error');
        }
        
        redirect('login');
    }
    
    /**
     * Tworzy sesję użytkownika
     * 
     * @param User $user Obiekt użytkownika
     * @return void
     */
    private function createUserSession($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->first_name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['lang'] = $user->language;
    }
    
    /**
     * Ustawia ciasteczko "zapamiętaj mnie"
     * 
     * @return void
     */
    private function setRememberMeCookie() {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 dni
        
        // Hashowanie tokenu
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        
        // Zapisanie tokenu w bazie danych
        $this->db->query("UPDATE users SET remember_token = :token WHERE id = :id");
        $this->db->bind(':token', $tokenHash);
        $this->db->bind(':id', $this->userModel->id);
        $this->db->execute();
        
        // Ustawienie ciasteczka
        setcookie('remember_token', $token, $expiry, '/', '', true, true);
    }
    
    /**
     * Sprawdza limit prób logowania
     * 
     * @return void
     */
    private function checkLoginAttempts() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $attempts = $_SESSION['login_attempts'][$ip] ?? 0;
        $maxAttempts = $this->getLoginAttemptsLimit();
        
        if ($attempts >= $maxAttempts) {
            $lockoutTime = $this->getLoginLockoutTime();
            $lastAttempt = $_SESSION['login_last_attempt'][$ip] ?? 0;
            $timeElapsed = time() - $lastAttempt;
            
            if ($timeElapsed < $lockoutTime * 60) {
                $timeLeft = ceil(($lockoutTime * 60 - $timeElapsed) / 60);
                $_SESSION['error'] = sprintf(__('login_too_many_attempts'), $timeLeft);
                redirect('login');
            } else {
                // Resetuj licznik po upływie czasu blokady
                $this->resetLoginAttempts();
            }
        }
    }
    
    /**
     * Zwiększa licznik prób logowania
     * 
     * @return void
     */
    private function incrementLoginAttempts() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
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
     * @return void
     */
    private function resetLoginAttempts() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (isset($_SESSION['login_attempts'][$ip])) {
            unset($_SESSION['login_attempts'][$ip]);
        }
        
        if (isset($_SESSION['login_last_attempt'][$ip])) {
            unset($_SESSION['login_last_attempt'][$ip]);
        }
    }
    
    /**
     * Pobiera limit prób logowania
     * 
     * @return int Limit prób logowania
     */
    private function getLoginAttemptsLimit() {
        $db = Database::getInstance();
        $db->query("SELECT setting_value FROM settings WHERE setting_key = 'max_login_attempts'");
        $value = $db->fetchColumn();
        
        return $value ? (int) $value : 5;
    }
    
    /**
     * Pobiera czas blokady logowania
     * 
     * @return int Czas blokady logowania (w minutach)
     */
    private function getLoginLockoutTime() {
        $db = Database::getInstance();
        $db->query("SELECT setting_value FROM settings WHERE setting_key = 'login_lockout_time'");
        $value = $db->fetchColumn();
        
        return $value ? (int) $value : 15;
    }
    
    /**
     * Wysyła e-mail z linkiem aktywacyjnym
     * 
     * @param User $user Obiekt użytkownika
     * @return bool Czy e-mail został wysłany
     */
    private function sendActivationEmail($user) {
        $activationUrl = APP_URL . '/activate/' . $user->activation_code;
        
        $data = [
            'first_name' => $user->first_name,
            'activation_url' => $activationUrl
        ];
        
        return EmailService::sendTemplate($user->email, 'activation', $data);
    }
    
    /**
     * Wysyła e-mail z linkiem do resetowania hasła
     * 
     * @param User $user Obiekt użytkownika
     * @return bool Czy e-mail został wysłany
     */
    private function sendResetPasswordEmail($user) {
        $resetUrl = APP_URL . '/reset-password/' . $user->reset_token;
        
        $data = [
            'first_name' => $user->first_name,
            'reset_url' => $resetUrl
        ];
        
        return EmailService::sendTemplate($user->email, 'password_reset', $data);
    }
}
        