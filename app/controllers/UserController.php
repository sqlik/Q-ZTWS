<?php
// app/controllers/UserController.php
// Kontroler obsługujący zarządzanie profilem użytkownika

class UserController {
    private $userModel;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        $this->userModel = new User();
    }
    
    /**
     * Wyświetla profil użytkownika
     * 
     * @return void
     */
    public function profile() {
        // Pobierz dane użytkownika
        if (!$this->userModel->getById($_SESSION['user_id'])) {
            $_SESSION['error'] = __('user_not_found');
            redirect('dashboard');
        }
        
        // Ustaw tytuł strony
        $pageTitle = __('profile');
        
        // Wyświetl widok profilu
        include APP_PATH . '/views/user/profile.php';
    }
    
    /**
     * Aktualizuje dane profilowe użytkownika
     * 
     * @return void
     */
    public function updateProfile() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('profile');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('profile');
        }
        
        // Pobierz dane użytkownika
        if (!$this->userModel->getById($_SESSION['user_id'])) {
            $_SESSION['error'] = __('user_not_found');
            redirect('dashboard');
        }
        
        // Pobierz dane z formularza
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $language = sanitize($_POST['language'] ?? 'pl');
        
        // Walidacja
        $errors = [];
        
        if (empty($firstName)) {
            $errors['first_name'] = __('user_first_name_required');
        }
        
        if (empty($lastName)) {
            $errors['last_name'] = __('user_last_name_required');
        }
        
        if (!in_array($language, ['pl', 'en'])) {
            $errors['language'] = __('user_language_invalid');
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('profile');
        }
        
        // Aktualizuj dane użytkownika
        $this->userModel->first_name = $firstName;
        $this->userModel->last_name = $lastName;
        $this->userModel->language = $language;
        
        if ($this->userModel->updateProfile()) {
            // Aktualizuj dane w sesji
            $_SESSION['user_name'] = $firstName;
            $_SESSION['lang'] = $language;
            
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('profile_updated');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('profile_update_error');
        }
        
        redirect('profile');
    }
    
    /**
     * Wyświetla formularz zmiany hasła
     * 
     * @return void
     */
    public function changePassword() {
        // Pobierz dane użytkownika
        if (!$this->userModel->getById($_SESSION['user_id'])) {
            $_SESSION['error'] = __('user_not_found');
            redirect('dashboard');
        }
        
        // Ustaw tytuł strony
        $pageTitle = __('change_password');
        
        // Wyświetl widok zmiany hasła
        include APP_PATH . '/views/user/change_password.php';
    }
    
    /**
     * Przetwarza formularz zmiany hasła
     * 
     * @return void
     */
    public function updatePassword() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('change-password');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('change-password');
        }
        
        // Pobierz dane użytkownika
        if (!$this->userModel->getById($_SESSION['user_id'])) {
            $_SESSION['error'] = __('user_not_found');
            redirect('dashboard');
        }
        
        // Pobierz dane z formularza
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Walidacja
        $errors = [];
        
        if (empty($currentPassword)) {
            $errors['current_password'] = __('current_password_required');
        } else if (!$this->userModel->verifyPassword($currentPassword)) {
            $errors['current_password'] = __('current_password_invalid');
        }
        
        if (empty($newPassword)) {
            $errors['new_password'] = __('new_password_required');
        } else if (strlen($newPassword) < 8) {
            $errors['new_password'] = __('user_password_too_short');
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = __('user_passwords_not_match');
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('change-password');
        }
        
        // Zmień hasło
        if ($this->userModel->updatePassword($newPassword)) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('password_changed');
            redirect('profile');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('password_change_error');
            redirect('change-password');
        }
    }
}