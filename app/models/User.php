<?php
/**
 * app/models/User.php
 * Model użytkownika w aplikacji Q-ZTWS
 */

class User {
    private $db;
    
    // Właściwości modelu
    public $id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $role;
    public $status;
    public $language;
    public $activation_code;
    public $reset_token;
    public $reset_expiry;
    public $quota;
    public $created_at;
    public $updated_at;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Pobiera użytkownika po ID
     * 
     * @param int $id ID użytkownika
     * @return bool Czy operacja się powiodła
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera użytkownika po adresie e-mail
     * 
     * @param string $email Adres e-mail
     * @return bool Czy operacja się powiodła
     */
    public function getByEmail($email) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera użytkownika po kodzie aktywacyjnym
     * 
     * @param string $code Kod aktywacyjny
     * @return bool Czy operacja się powiodła
     */
    public function getByActivationCode($code) {
        $this->db->query("SELECT * FROM users WHERE activation_code = :code");
        $this->db->bind(':code', $code);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera użytkownika po tokenie resetowania hasła
     * 
     * @param string $token Token resetowania hasła
     * @return bool Czy operacja się powiodła
     */
    public function getByResetToken($token) {
        $this->db->query("SELECT * FROM users WHERE reset_token = :token AND reset_expiry > NOW()");
        $this->db->bind(':token', $token);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera wszystkich użytkowników
     * 
     * @param int $limit Limit wyników
     * @param int $offset Przesunięcie wyników
     * @return array Tablica użytkowników
     */
    public function getAll($limit = 100, $offset = 0) {
        $this->db->query("SELECT * FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera liczbę wszystkich użytkowników
     * 
     * @return int Liczba użytkowników
     */
    public function getCount() {
        $this->db->query("SELECT COUNT(*) FROM users");
        return $this->db->fetchColumn();
    }
    
    /**
     * Pobiera liczbę użytkowników o określonym statusie
     * 
     * @param string $status Status użytkownika (active, inactive, pending)
     * @return int Liczba użytkowników
     */
    public function getCountByStatus($status) {
        $this->db->query("SELECT COUNT(*) FROM users WHERE status = :status");
        $this->db->bind(':status', $status);
        
        return $this->db->fetchColumn();
    }
    
    /**
     * Wyszukuje użytkowników po frazie
     * 
     * @param string $term Fraza do wyszukania
     * @param int $limit Limit wyników
     * @param int $offset Przesunięcie wyników
     * @return array Tablica użytkowników
     */
    public function search($term, $limit = 100, $offset = 0) {
        $term = '%' . $term . '%';
        
        $this->db->query("SELECT * FROM users WHERE 
                          email LIKE :term OR 
                          first_name LIKE :term OR 
                          last_name LIKE :term 
                          ORDER BY id DESC LIMIT :limit OFFSET :offset");
            
        $this->db->bind(':term', $term);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera liczbę użytkowników pasujących do frazy
     * 
     * @param string $term Fraza do wyszukania
     * @return int Liczba użytkowników
     */
    public function searchCount($term) {
        $term = '%' . $term . '%';
        
        $this->db->query("SELECT COUNT(*) FROM users WHERE 
                          email LIKE :term OR 
                          first_name LIKE :term OR 
                          last_name LIKE :term");
            
        $this->db->bind(':term', $term);
        
        return $this->db->fetchColumn();
    }
    
    /**
     * Rejestruje nowego użytkownika
     * 
     * @return bool Czy operacja się powiodła
     */
    public function register() {
        // Sprawdzanie czy e-mail istnieje
        $this->db->query("SELECT id FROM users WHERE email = :email");
        $this->db->bind(':email', $this->email);
        
        if ($this->db->rowCount() > 0) {
            return false;
        }
        
        // Generowanie kodu aktywacyjnego
        $this->activation_code = bin2hex(random_bytes(16));
        
        // Hashowanie hasła
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Wstawianie użytkownika
        $this->db->query("INSERT INTO users (email, password, first_name, last_name, role, status, language, activation_code, quota) 
                          VALUES (:email, :password, :first_name, :last_name, :role, :status, :language, :activation_code, :quota)");
            
        $this->db->bind(':email', $this->email);
        $this->db->bind(':password', $this->password);
        $this->db->bind(':first_name', $this->first_name);
        $this->db->bind(':last_name', $this->last_name);
        $this->db->bind(':role', $this->role ?? 'user');
        $this->db->bind(':status', $this->status ?? 'pending');
        $this->db->bind(':language', $this->language ?? 'pl');
        $this->db->bind(':activation_code', $this->activation_code);
        $this->db->bind(':quota', $this->quota ?? 100);
        
        $this->db->execute();
        $this->id = $this->db->lastInsertId();
        
        return true;
    }
    
    /**
     * Aktualizuje użytkownika
     * 
     * @return bool Czy operacja się powiodła
     */
    public function update() {
        $this->db->query("UPDATE users SET 
                          first_name = :first_name,
                          last_name = :last_name,
                          role = :role,
                          status = :status,
                          language = :language,
                          quota = :quota
                          WHERE id = :id");
            
        $this->db->bind(':first_name', $this->first_name);
        $this->db->bind(':last_name', $this->last_name);
        $this->db->bind(':role', $this->role);
        $this->db->bind(':status', $this->status);
        $this->db->bind(':language', $this->language);
        $this->db->bind(':quota', $this->quota);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Aktualizuje hasło użytkownika
     * 
     * @param string $password Nowe hasło
     * @return bool Czy operacja się powiodła
     */
    public function updatePassword($password) {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        
        $this->db->query("UPDATE users SET password = :password WHERE id = :id");
        $this->db->bind(':password', $this->password);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Aktualizuje język użytkownika
     * 
     * @param string $language Kod języka (pl lub en)
     * @return bool Czy operacja się powiodła
     */
    public function updateLanguage($language) {
        if (!in_array($language, ['pl', 'en'])) {
            return false;
        }
        
        $this->language = $language;
        
        $this->db->query("UPDATE users SET language = :language WHERE id = :id");
        $this->db->bind(':language', $this->language);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Aktywuje konto użytkownika
     * 
     * @return bool Czy operacja się powiodła
     */
    public function activate() {
        $this->db->query("UPDATE users SET status = 'active', activation_code = NULL WHERE id = :id");
        $this->db->bind(':id', $this->id);
        
        if ($this->db->execute()) {
            $this->status = 'active';
            $this->activation_code = null;
            return true;
        }
        
        return false;
    }
    
    /**
     * Generuje token resetowania hasła
     * 
     * @return bool Czy operacja się powiodła
     */
    public function generateResetToken() {
        $this->reset_token = bin2hex(random_bytes(16));
        $this->reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->db->query("UPDATE users SET reset_token = :token, reset_expiry = :expiry WHERE id = :id");
        $this->db->bind(':token', $this->reset_token);
        $this->db->bind(':expiry', $this->reset_expiry);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Resetuje token resetowania hasła
     * 
     * @return bool Czy operacja się powiodła
     */
    public function clearResetToken() {
        $this->db->query("UPDATE users SET reset_token = NULL, reset_expiry = NULL WHERE id = :id");
        $this->db->bind(':id', $this->id);
        
        if ($this->db->execute()) {
            $this->reset_token = null;
            $this->reset_expiry = null;
            return true;
        }
        
        return false;
    }
    
    /**
     * Usuwa użytkownika
     * 
     * @return bool Czy operacja się powiodła
     */
    public function delete() {
        $this->db->query("DELETE FROM users WHERE id = :id");
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Weryfikuje hasło użytkownika
     * 
     * @param string $password Hasło do weryfikacji
     * @return bool Czy hasło jest poprawne
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
    
    /**
     * Pobiera quizy użytkownika
     * 
     * @param int $limit Limit wyników
     * @param int $offset Przesunięcie wyników
     * @return array Tablica quizów
     */
    public function getQuizzes($limit = 100, $offset = 0) {
        $quizModel = new Quiz();
        return $quizModel->getByUserId($this->id, $limit, $offset);
    }
    
    /**
     * Pobiera liczbę quizów użytkownika
     * 
     * @return int Liczba quizów
     */
    public function getQuizzesCount() {
        $quizModel = new Quiz();
        return $quizModel->getCountByUserId($this->id);
    }
    
    /**
     * Sprawdza czy użytkownik jest administratorem
     * 
     * @return bool Czy jest administratorem
     */
    public function isAdmin() {
        return $this->role === 'admin';
    }
    
    /**
     * Przypisuje właściwości z wiersza bazy danych
     * 
     * @param array $row Wiersz z bazy danych
     * @return void
     */
    private function mapProperties($row) {
        $this->id = $row['id'];
        $this->email = $row['email'];
        $this->password = $row['password'];
        $this->first_name = $row['first_name'];
        $this->last_name = $row['last_name'];
        $this->role = $row['role'];
        $this->status = $row['status'];
        $this->language = $row['language'];
        $this->activation_code = $row['activation_code'];
        $this->reset_token = $row['reset_token'];
        $this->reset_expiry = $row['reset_expiry'];
        $this->quota = $row['quota'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }
}
