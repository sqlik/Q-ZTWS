<?php
/**
 * app/models/Quiz.php
 * Model quizu w aplikacji Q-ZTWS
 */

class Quiz {
    private $db;
    
    // Właściwości modelu
    public $id;
    public $user_id;
    public $title;
    public $description;
    public $access_code;
    public $qr_code_path;
    public $status;
    public $created_at;
    public $updated_at;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Pobiera quiz po ID
     * 
     * @param int $id ID quizu
     * @return bool Czy operacja się powiodła
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM quizzes WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera quiz po kodzie dostępu
     * 
     * @param string $code Kod dostępu
     * @return bool Czy operacja się powiodła
     */
    public function getByAccessCode($code) {
        $this->db->query("SELECT * FROM quizzes WHERE access_code = :code AND status = 'active'");
        $this->db->bind(':code', $code);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera wszystkie quizy użytkownika
     * 
     * @param int $userId ID użytkownika
     * @param int $limit Limit wyników
     * @param int $offset Przesunięcie wyników
     * @return array Tablica quizów
     */
    public function getByUserId($userId, $limit = 100, $offset = 0) {
        $this->db->query("SELECT q.*, 
                         (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS questions_count
                         FROM quizzes q 
                         WHERE q.user_id = :user_id 
                         ORDER BY q.created_at DESC 
                         LIMIT :limit OFFSET :offset");
                         
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera liczbę quizów użytkownika
     * 
     * @param int $userId ID użytkownika
     * @return int Liczba quizów
     */
    public function getCountByUserId($userId) {
        $this->db->query("SELECT COUNT(*) FROM quizzes WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        
        return $this->db->fetchColumn();
    }
    
    /**
     * Wyszukuje quizy użytkownika po frazie
     * 
     * @param int $userId ID użytkownika
     * @param string $term Fraza do wyszukania
     * @param int $limit Limit wyników
     * @param int $offset Przesunięcie wyników
     * @return array Tablica quizów
     */
    public function searchByUserId($userId, $term, $limit = 100, $offset = 0) {
        $term = '%' . $term . '%';
        
        $this->db->query("SELECT q.*, 
                         (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS questions_count
                         FROM quizzes q
                         WHERE q.user_id = :user_id AND (
                             q.title LIKE :term OR 
                             q.description LIKE :term
                         ) 
                         ORDER BY q.created_at DESC 
                         LIMIT :limit OFFSET :offset");
            
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':term', $term);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera liczbę quizów użytkownika pasujących do frazy
     * 
     * @param int $userId ID użytkownika
     * @param string $term Fraza do wyszukania
     * @return int Liczba quizów
     */
    public function searchCountByUserId($userId, $term) {
        $term = '%' . $term . '%';
        
        $this->db->query("SELECT COUNT(*) FROM quizzes 
                         WHERE user_id = :user_id AND (
                             title LIKE :term OR 
                             description LIKE :term
                         )");
            
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':term', $term);
        
        return $this->db->fetchColumn();
    }
    
    /**
     * Tworzy nowy quiz
     * 
     * @return bool Czy operacja się powiodła
     */
    public function create() {
        // Generowanie kodu dostępu
        $this->access_code = $this->generateAccessCode();
        
        // Wstawianie quizu
        $this->db->query("INSERT INTO quizzes (user_id, title, description, access_code, status) 
                          VALUES (:user_id, :title, :description, :access_code, :status)");
            
        $this->db->bind(':user_id', $this->user_id);
        $this->db->bind(':title', $this->title);
        $this->db->bind(':description', $this->description ?? '');
        $this->db->bind(':access_code', $this->access_code);
        $this->db->bind(':status', $this->status ?? 'draft');
        
        $this->db->execute();
        $this->id = $this->db->lastInsertId();
        
        // Generowanie kodu QR
        $this->generateQRCode();
        
        return $this->id ? true : false;
    }
    
    /**
     * Aktualizuje quiz
     * 
     * @return bool Czy operacja się powiodła
     */
    public function update() {
        $this->db->query("UPDATE quizzes SET 
                          title = :title,
                          description = :description,
                          status = :status
                          WHERE id = :id AND user_id = :user_id");
            
        $this->db->bind(':title', $this->title);
        $this->db->bind(':description', $this->description);
        $this->db->bind(':status', $this->status);
        $this->db->bind(':id', $this->id);
        $this->db->bind(':user_id', $this->user_id);
        
        return $this->db->execute();
    }
    
    /**
     * Regeneruje kod dostępu do quizu
     * 
     * @return bool Czy operacja się powiodła
     */
    public function regenerateAccessCode() {
        $this->access_code = $this->generateAccessCode();
        
        $this->db->query("UPDATE quizzes SET access_code = :access_code WHERE id = :id");
        $this->db->bind(':access_code', $this->access_code);
        $this->db->bind(':id', $this->id);
        
        if ($this->db->execute()) {
            // Regenerowanie kodu QR
            $this->generateQRCode();
            return true;
        }
        
        return false;
    }
    
    /**
     * Usuwa quiz
     * 
     * @return bool Czy operacja się powiodła
     */
    public function delete() {
        // Usuwanie pliku z kodem QR, jeśli istnieje
        if ($this->qr_code_path && file_exists(ROOT_PATH . $this->qr_code_path)) {
            unlink(ROOT_PATH . $this->qr_code_path);
        }
        
        $this->db->query("DELETE FROM quizzes WHERE id = :id AND user_id = :user_id");
        $this->db->bind(':id', $this->id);
        $this->db->bind(':user_id', $this->user_id);
        
        return $this->db->execute();
    }
    
    /**
     * Pobiera pytania związane z quizem
     * 
     * @return array Tablica pytań
     */
    public function getQuestions() {
        $questionModel = new Question();
        return $questionModel->getByQuizId($this->id);
    }
    
    /**
     * Pobiera liczbę pytań w quizie
     * 
     * @return int Liczba pytań
     */
    public function getQuestionsCount() {
        $questionModel = new Question();
        return $questionModel->getCountByQuizId($this->id);
    }
    
    /**
     * Pobiera sesje związane z quizem
     * 
     * @param int $limit Limit wyników
     * @param int $offset Przesunięcie wyników
     * @return array Tablica sesji
     */
    public function getSessions($limit = 100, $offset = 0) {
        $this->db->query("SELECT s.*, 
                         (SELECT COUNT(*) FROM participants WHERE session_id = s.id) AS participants_count
                         FROM sessions s 
                         WHERE s.quiz_id = :quiz_id 
                         ORDER BY s.created_at DESC 
                         LIMIT :limit OFFSET :offset");
                         
        $this->db->bind(':quiz_id', $this->id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera liczbę sesji quizu
     * 
     * @return int Liczba sesji
     */
    public function getSessionsCount() {
        $this->db->query("SELECT COUNT(*) FROM sessions WHERE quiz_id = :quiz_id");
        $this->db->bind(':quiz_id', $this->id);
        
        return $this->db->fetchColumn();
    }
    
    /**
     * Tworzy nową sesję quizu
     * 
     * @return int|bool ID sesji lub false w przypadku błędu
     */
    public function createSession() {
        $this->db->query("INSERT INTO sessions (quiz_id, status) VALUES (:quiz_id, 'pending')");
        $this->db->bind(':quiz_id', $this->id);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Generuje unikalny kod dostępu
     * 
     * @return string Unikalny kod dostępu
     */
    private function generateAccessCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $length = 6;
        
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            // Sprawdzenie czy kod jest unikalny
            $this->db->query("SELECT id FROM quizzes WHERE access_code = :code");
            $this->db->bind(':code', $code);
            
        } while ($this->db->rowCount() > 0);
        
        return $code;
    }
    
    /**
     * Generuje kod QR dla quizu
     * 
     * @return bool Czy operacja się powiodła
     */
    private function generateQRCode() {
        if (!$this->id || !$this->access_code) {
            return false;
        }
        
        // Sprawdź czy biblioteki są dostępne
        if (!class_exists('Endroid\QrCode\QrCode')) {
            if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
                error_log("Błąd generowania kodu QR: Brak wymaganych bibliotek. Uruchom 'composer install'.");
                return false;
            }
            require_once ROOT_PATH . '/vendor/autoload.php';
        }
        
        // Ścieżka do zapisu kodu QR
        $qrDir = '/uploads/qr_codes/';
        $qrPath = $qrDir . 'quiz_' . $this->id . '_' . time() . '.png';
        $fullPath = ROOT_PATH . $qrPath;
        
        // Tworzenie katalogu, jeśli nie istnieje
        if (!file_exists(ROOT_PATH . $qrDir)) {
            mkdir(ROOT_PATH . $qrDir, 0755, true);
        }
        
        // Tworzenie kodu QR
        $url = APP_URL . '/join/' . $this->access_code;
        
        try {
            $qrCode = new \Endroid\QrCode\QrCode($url);
            $qrCode->setSize(300);
            $qrCode->setMargin(10);
            
            // Zapisanie do pliku
            $qrCode->writeFile($fullPath);
            
            // Aktualizacja ścieżki w bazie danych
            $this->qr_code_path = $qrPath;
            
            $this->db->query("UPDATE quizzes SET qr_code_path = :qr_code_path WHERE id = :id");
            $this->db->bind(':qr_code_path', $this->qr_code_path);
            $this->db->bind(':id', $this->id);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Błąd generowania kodu QR: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Przypisuje właściwości z wiersza bazy danych
     * 
     * @param array $row Wiersz z bazy danych
     * @return void
     */
    private function mapProperties($row) {
        $this->id = $row['id'];
        $this->user_id = $row['user_id'];
        $this->title = $row['title'];
        $this->description = $row['description'];
        $this->access_code = $row['access_code'];
        $this->qr_code_path = $row['qr_code_path'];
        $this->status = $row['status'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }
}
