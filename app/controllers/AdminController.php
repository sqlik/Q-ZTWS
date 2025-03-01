<?php
// app/controllers/AdminController.php
// Kontroler obsługujący zarządzanie aplikacją przez administratora

class AdminController {
    private $userModel;
    private $settingsModel;
    private $emailTemplateModel;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        // Sprawdź czy użytkownik jest administratorem
        if ($_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = __('access_denied');
            redirect('dashboard');
        }
        
        $this->userModel = new User();
        $this->settingsModel = new Setting();
        $this->emailTemplateModel = new EmailTemplate();
    }
    
    /**
     * Wyświetla dashboard administratora
     * 
     * @return void
     */
    public function dashboard() {
        // Pobierz statystyki
        $usersCount = $this->userModel->getCount();
        $activeUsersCount = $this->userModel->getCountByStatus('active');
        $pendingUsersCount = $this->userModel->getCountByStatus('pending');
        
        // Dane dla widoku
        $data = [
            'usersCount' => $usersCount,
            'activeUsersCount' => $activeUsersCount,
            'pendingUsersCount' => $pendingUsersCount
        ];
        
        // Wyświetl widok dashboardu administratora
        include APP_PATH . '/views/admin/dashboard.php';
    }
    
    /**
     * Wyświetla listę użytkowników
     * 
     * @return void
     */
    public function users() {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Wyszukiwanie
        $searchTerm = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        
        if (!empty($searchTerm)) {
            $users = $this->userModel->search($searchTerm, $limit, $offset);
            $total = $this->userModel->searchCount($searchTerm);
        } else {
            $users = $this->userModel->getAll($limit, $offset);
            $total = $this->userModel->getCount();
        }
        
        $totalPages = ceil($total / $limit);
        
        // Dane dla widoku
        $data = [
            'users' => $users,
            'page' => $page,
            'totalPages' => $totalPages,
            'searchTerm' => $searchTerm
        ];
        
        // Wyświetl widok listy użytkowników
        include APP_PATH . '/views/admin/users/index.php';
    }
    
    /**
     * Wyświetla formularz tworzenia nowego użytkownika
     * 
     * @return void
     */
    public function createUser() {
        // Wyświetl widok formularza tworzenia użytkownika
        include APP_PATH . '/views/admin/users/create.php';
    }
    
    /**
     * Przetwarza formularz tworzenia nowego użytkownika
     * 
     * @return void
     */
    public function storeUser() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/users');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('admin/users/create');
        }
        
        // Pobierz dane z formularza
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = sanitize($_POST['role'] ?? 'user');
        $status = sanitize($_POST['status'] ?? 'pending');
        $language = sanitize($_POST['language'] ?? 'pl');
        $quota = (int) ($_POST['quota'] ?? 100);
        
        // Walidacja
        $errors = [];
        
        if (empty($firstName)) {
            $errors['first_name'] = __('user_first_name_required');
        }
        
        if (empty($lastName)) {
            $errors['last_name'] = __('user_last_name_required');
        }
        
        if (empty($email)) {
            $errors['email'] = __('user_email_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __('user_email_invalid');
        }
        
        if (empty($password)) {
            $errors['password'] = __('user_password_required');
        } elseif (strlen($password) < 8) {
            $errors['password'] = __('user_password_too_short');
        }
        
        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = __('user_passwords_not_match');
        }
        
        if (!in_array($role, ['admin', 'user'])) {
            $errors['role'] = __('user_role_invalid');
        }
        
        if (!in_array($status, ['active', 'inactive', 'pending'])) {
            $errors['status'] = __('user_status_invalid');
        }
        
        if (!in_array($language, ['pl', 'en'])) {
            $errors['language'] = __('user_language_invalid');
        }
        
        if ($quota < 1 || $quota > 10000) {
            $errors['quota'] = __('user_quota_invalid');
        }
        
        // Sprawdź czy adres e-mail jest już zajęty
        if (empty($errors['email']) && $this->userModel->getByEmail($email)) {
            $errors['email'] = __('user_email_exists');
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'role' => $role,
                'status' => $status,
                'language' => $language,
                'quota' => $quota
            ];
            redirect('admin/users/create');
        }
        
        // Utwórz nowego użytkownika
        $this->userModel->first_name = $firstName;
        $this->userModel->last_name = $lastName;
        $this->userModel->email = $email;
        $this->userModel->password = $password;
        $this->userModel->role = $role;
        $this->userModel->status = $status;
        $this->userModel->language = $language;
        $this->userModel->quota = $quota;
        
        if ($this->userModel->register()) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('user_created');
            
            // Jeśli status jest "pending", wyślij e-mail aktywacyjny
            if ($status === 'pending') {
                $this->sendActivationEmail($this->userModel);
            }
            
            // Przekieruj na listę użytkowników
            redirect('admin/users');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('user_create_error');
            $_SESSION['form_data'] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'role' => $role,
                'status' => $status,
                'language' => $language,
                'quota' => $quota
            ];
            redirect('admin/users/create');
        }
    }
    
    /**
     * Wyświetla formularz edycji użytkownika
     * 
     * @param int $id ID użytkownika
     * @return void
     */
    public function editUser($id) {
        // Pobierz użytkownika
        if (!$this->userModel->getById($id)) {
            $_SESSION['error'] = __('user_not_found');
            redirect('admin/users');
        }
        
        // Wyświetl widok formularza edycji użytkownika
        include APP_PATH . '/views/admin/users/edit.php';
    }
    
    /**
     * Przetwarza formularz edycji użytkownika
     * 
     * @param int $id ID użytkownika
     * @return void
     */
    public function updateUser($id) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/users');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('admin/users/edit/' . $id);
        }
        
        // Pobierz użytkownika
        if (!$this->userModel->getById($id)) {
            $_SESSION['error'] = __('user_not_found');
            redirect('admin/users');
        }
        
        // Pobierz dane z formularza
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $role = sanitize($_POST['role'] ?? 'user');
        $status = sanitize($_POST['status'] ?? 'pending');
        $language = sanitize($_POST['language'] ?? 'pl');
        $quota = (int) ($_POST['quota'] ?? 100);
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Walidacja
        $errors = [];
        
        if (empty($firstName)) {
            $errors['first_name'] = __('user_first_name_required');
        }
        
        if (empty($lastName)) {
            $errors['last_name'] = __('user_last_name_required');
        }
        
        if (!in_array($role, ['admin', 'user'])) {
            $errors['role'] = __('user_role_invalid');
        }
        
        if (!in_array($status, ['active', 'inactive', 'pending'])) {
            $errors['status'] = __('user_status_invalid');
        }
        
        if (!in_array($language, ['pl', 'en'])) {
            $errors['language'] = __('user_language_invalid');
        }
        
        if ($quota < 1 || $quota > 10000) {
            $errors['quota'] = __('user_quota_invalid');
        }
        
        // Jeśli podano nowe hasło, sprawdź je
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                $errors['new_password'] = __('user_password_too_short');
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = __('user_passwords_not_match');
            }
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('admin/users/edit/' . $id);
        }
        
        // Aktualizuj użytkownika
        $this->userModel->first_name = $firstName;
        $this->userModel->last_name = $lastName;
        $this->userModel->role = $role;
        $this->userModel->status = $status;
        $this->userModel->language = $language;
        $this->userModel->quota = $quota;
        
        $updated = $this->userModel->update();
        
        // Jeśli podano nowe hasło, zmień je
        if (!empty($newPassword) && $updated) {
            $this->userModel->updatePassword($newPassword);
        }
        
        if ($updated) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('user_updated');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('user_update_error');
        }
        
        redirect('admin/users/edit/' . $id);
    }
    
    /**
     * Usuwa użytkownika
     * 
     * @param int $id ID użytkownika
     * @return void
     */
    public function deleteUser($id) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/users');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('admin/users');
        }
        
        // Pobierz użytkownika
        if (!$this->userModel->getById($id)) {
            $_SESSION['error'] = __('user_not_found');
            redirect('admin/users');
        }
        
        // Nie pozwól na usunięcie własnego konta
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = __('user_delete_self');
            redirect('admin/users');
        }
        
        if ($this->userModel->delete()) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('user_deleted');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('user_delete_error');
        }
        
        redirect('admin/users');
    }
    
    /**
     * Wyświetla ustawienia aplikacji
     * 
     * @return void
     */
    public function settings() {
        // Pobierz wszystkie ustawienia
        $settings = $this->settingsModel->getAll();
        
        // Wyświetl widok ustawień
        include APP_PATH . '/views/admin/settings/index.php';
    }
    
    /**
     * Aktualizuje ustawienia aplikacji
     * 
     * @return void
     */
    public function updateSettings() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/settings');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('admin/settings');
        }
        
        // Pobierz wszystkie ustawienia
        $settings = $_POST['settings'] ?? [];
        
        if (empty($settings)) {
            $_SESSION['error'] = __('settings_update_error');
            redirect('admin/settings');
        }
        
        // Aktualizuj ustawienia
        $success = true;
        
        foreach ($settings as $key => $value) {
            if (!$this->settingsModel->update($key, $value)) {
                $success = false;
            }
        }
        
        if ($success) {
            $_SESSION['success'] = __('settings_updated');
        } else {
            $_SESSION['error'] = __('settings_update_error');
        }
        
        redirect('admin/settings');
    }
    
    /**
     * Wyświetla szablony e-mail
     * 
     * @return void
     */
    public function emailTemplates() {
        // Pobierz wszystkie szablony e-mail
        $templates = $this->emailTemplateModel->getAll();
        
        // Wyświetl widok szablonów e-mail
        include APP_PATH . '/views/admin/email-templates/index.php';
    }
    
    /**
     * Wyświetla formularz edycji szablonu e-mail
     * 
     * @param int $id ID szablonu
     * @return void
     */
    public function editEmailTemplate($id) {
        // Pobierz szablon e-mail
        if (!$this->emailTemplateModel->getById($id)) {
            $_SESSION['error'] = __('email_template_not_found');
            redirect('admin/email-templates');
        }
        
        // Wyświetl widok formularza edycji szablonu
        include APP_PATH . '/views/admin/email-templates/edit.php';
    }
    
    /**
     * Aktualizuje szablon e-mail
     * 
     * @param int $id ID szablonu
     * @return void
     */
    public function updateEmailTemplate($id) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/email-templates');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('admin/email-templates/edit/' . $id);
        }
        
        // Pobierz szablon e-mail
        if (!$this->emailTemplateModel->getById($id)) {
            $_SESSION['error'] = __('email_template_not_found');
            redirect('admin/email-templates');
        }
        
        // Pobierz dane z formularza
        $subjectPl = sanitize($_POST['subject_pl'] ?? '');
        $subjectEn = sanitize($_POST['subject_en'] ?? '');
        $bodyPl = $_POST['body_pl'] ?? '';
        $bodyEn = $_POST['body_en'] ?? '';
        
        // Walidacja
        $errors = [];
        
        if (empty($subjectPl)) {
            $errors['subject_pl'] = __('email_template_subject_pl_required');
        }
        
        if (empty($subjectEn)) {
            $errors['subject_en'] = __('email_template_subject_en_required');
        }
        
        if (empty($bodyPl)) {
            $errors['body_pl'] = __('email_template_body_pl_required');
        }
        
        if (empty($bodyEn)) {
            $errors['body_en'] = __('email_template_body_en_required');
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('admin/email-templates/edit/' . $id);
        }
        
        // Aktualizuj szablon e-mail
        $this->emailTemplateModel->subject_pl = $subjectPl;
        $this->emailTemplateModel->subject_en = $subjectEn;
        $this->emailTemplateModel->body_pl = $bodyPl;
        $this->emailTemplateModel->body_en = $bodyEn;
        
        if ($this->emailTemplateModel->update()) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('email_template_updated');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('email_template_update_error');
        }
        
        redirect('admin/email-templates/edit/' . $id);
    }
    
    /**
     * Wysyła testowy e-mail
     * 
     * @param int $id ID szablonu
     * @return void
     */
    public function sendTestEmail($id) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/email-templates');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('admin/email-templates/edit/' . $id);
        }
        
        // Pobierz szablon e-mail
        if (!$this->emailTemplateModel->getById($id)) {
            $_SESSION['error'] = __('email_template_not_found');
            redirect('admin/email-templates');
        }
        
        // Pobierz adres e-mail do testu
        $email = sanitize($_POST['test_email'] ?? '');
        
        // Walidacja
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = __('email_invalid');
            redirect('admin/email-templates/edit/' . $id);
        }
        
        // Przygotuj dane testowe
        $data = [
            'first_name' => 'Test',
            'activation_url' => APP_URL . '/activate/test-code',
            'reset_url' => APP_URL . '/reset-password/test-token'
        ];
        
        // Wybierz język
        $lang = $_POST['test_language'] ?? 'pl';
        
        if (!in_array($lang, ['pl', 'en'])) {
            $lang = 'pl';
        }
        
        // Wyślij testowy e-mail
        if (EmailService::sendTemplate($email, $this->emailTemplateModel->template_key, $data, $lang)) {
            $_SESSION['success'] = __('test_email_sent');
        } else {
            $_SESSION['error'] = __('test_email_error');
        }
        
        redirect('admin/email-templates/edit/' . $id);
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
        
        return EmailService::sendTemplate($user->email, 'activation', $data, $user->language);
    }
}
