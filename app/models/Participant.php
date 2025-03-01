<?php
/**
 * app/models/Participant.php
 * Model uczestnika quizu w aplikacji Q-ZTWS
 */

class Participant {
    private $db;
    
    // Właściwości modelu
    public $id;
    public $session_id;
    public $nickname;
    public $device_id;
    public $total_score;
    public $created_at;
    public $updated_at;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Pobiera uczestnika po ID
     * 
     * @param int $id ID uczestnika
     * @return bool Czy operacja się powiodła
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM participants WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera uczestnika po nicku i ID sesji
     * 
     * @param string $nickname Nick uczestnika
     * @param int $sessionId ID sesji
     * @return bool Czy operacja się powiodła
     */
    public function getByNicknameAndSession($nickname, $sessionId) {
        $this->db->query("SELECT * FROM participants WHERE nickname = :nickname AND session_id = :session_id");
        $this->db->bind(':nickname', $nickname);
        $this->db->bind(':session_id', $sessionId);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera wszystkich uczestników sesji
     * 
     * @param int $sessionId ID sesji
     * @return array Tablica uczestników
     */
    public function getBySessionId($sessionId) {
        $this->db->query("SELECT * FROM participants WHERE session_id = :session_id ORDER BY total_score DESC");
        $this->db->bind(':session_id', $sessionId);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera uczestników sesji wraz z wynikami
     * 
     * @param int $sessionId ID sesji
     * @return array Tablica uczestników z wynikami
     */
    public function getBySessionIdWithResults($sessionId) {
        $this->db->query("SELECT p.*, 
                         (SELECT COUNT(*) FROM participant_answers pa WHERE pa.participant_id = p.id) AS answers_count,
                         (SELECT COUNT(*) FROM questions q WHERE q.quiz_id = (SELECT quiz_id FROM sessions WHERE id = :session_id)) AS questions_count,
                         (SELECT AVG(response_time) FROM participant_answers pa WHERE pa.participant_id = p.id) AS average_time
                         FROM participants p 
                         WHERE p.session_id = :session_id 
                         ORDER BY p.total_score DESC");
                         
        $this->db->bind(':session_id', $sessionId);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Sprawdza czy nick jest już zajęty w sesji
     * 
     * @param int $sessionId ID sesji
     * @param string $nickname Nick uczestnika
     * @return bool Czy nick jest zajęty
     */
    public function nicknameExistsInSession($sessionId, $nickname) {
        $this->db->query("SELECT COUNT(*) FROM participants WHERE session_id = :session_id AND nickname = :nickname");
        $this->db->bind(':session_id', $sessionId);
        $this->db->bind(':nickname', $nickname);
        
        return $this->db->fetchColumn() > 0;
    }
    
    /**
     * Pobiera liczbę uczestników sesji
     * 
     * @param int $sessionId ID sesji
     * @return int Liczba uczestników
     */
    public function getCountBySessionId($sessionId) {
        $this->db->query("SELECT COUNT(*) FROM participants WHERE session_id = :session_id");
        $this->db->bind(':session_id', $sessionId);
        
        return $this->db->fetchColumn();
    }
    
    /**
     * Tworzy nowego uczestnika
     * 
     * @return bool Czy operacja się powiodła
     */
    public function create() {
        $this->db->query("INSERT INTO participants (session_id, nickname, device_id, total_score) 
                          VALUES (:session_id, :nickname, :device_id, :total_score)");
        
        $this->db->bind(':session_id', $this->session_id);
        $this->db->bind(':nickname', $this->nickname);
        $this->db->bind(':device_id', $this->device_id);
        $this->db->bind(':total_score', $this->total_score ?? 0);
        
        if ($this->db->execute()) {
            $this->id = $this->db->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Aktualizuje uczestnika
     * 
     * @return bool Czy operacja się powiodła
     */
    public function update() {
        $this->db->query("UPDATE participants SET nickname = :nickname, total_score = :total_score WHERE id = :id");
        
        $this->db->bind(':nickname', $this->nickname);
        $this->db->bind(':total_score', $this->total_score);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Usuwa uczestnika
     * 
     * @return bool Czy operacja się powiodła
     */
    public function delete() {
        $this->db->query("DELETE FROM participants WHERE id = :id");
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Dodaje odpowiedź uczestnika
     * 
     * @param int $participantId ID uczestnika
     * @param int $questionId ID pytania
     * @param int $answerId ID odpowiedzi
     * @param float $responseTime Czas odpowiedzi w sekundach
     * @param int $score Punkty za odpowiedź
     * @return bool Czy operacja się powiodła
     */
    public function addAnswer($participantId, $questionId, $answerId, $responseTime, $score) {
        $this->db->query("INSERT INTO participant_answers (participant_id, question_id, answer_id, response_time, score) 
                          VALUES (:participant_id, :question_id, :answer_id, :response_time, :score)");
        
        $this->db->bind(':participant_id', $participantId);
        $this->db->bind(':question_id', $questionId);
        $this->db->bind(':answer_id', $answerId);
        $this->db->bind(':response_time', $responseTime);
        $this->db->bind(':score', $score);
        
        return $this->db->execute();
    }
    
    /**
     * Sprawdza czy uczestnik już odpowiedział na pytanie
     * 
     * @param int $participantId ID uczestnika
     * @param int $questionId ID pytania
     * @return bool Czy odpowiedział
     */
    public function hasAnsweredQuestion($participantId, $questionId) {
        $this->db->query("SELECT COUNT(*) FROM participant_answers 
                          WHERE participant_id = :participant_id AND question_id = :question_id");
        
        $this->db->bind(':participant_id', $participantId);
        $this->db->bind(':question_id', $questionId);
        
        return $this->db->fetchColumn() > 0;
    }
    
    /**
     * Pobiera odpowiedź uczestnika na pytanie
     * 
     * @param int $participantId ID uczestnika
     * @param int $questionId ID pytania
     * @return array|false Odpowiedź lub false
     */
    public function getAnswerForQuestion($participantId, $questionId) {
        $this->db->query("SELECT pa.*, a.answer_text, a.is_correct
                          FROM participant_answers pa
                          LEFT JOIN answers a ON pa.answer_id = a.id
                          WHERE pa.participant_id = :participant_id AND pa.question_id = :question_id");
        
        $this->db->bind(':participant_id', $participantId);
        $this->db->bind(':question_id', $questionId);
        
        return $this->db->fetch();
    }
    
    /**
     * Aktualizuje sumę punktów uczestnika
     * 
     * @param int $participantId ID uczestnika
     * @return bool Czy operacja się powiodła
     */
    public function updateTotalScore($participantId) {
        $this->db->query("UPDATE participants SET 
                          total_score = (SELECT SUM(score) FROM participant_answers WHERE participant_id = :participant_id) 
                          WHERE id = :participant_id");
        
        $this->db->bind(':participant_id', $participantId);
        
        return $this->db->execute();
    }
    
    /**
     * Pobiera ranking uczestników sesji
     * 
     * @param int $sessionId ID sesji
     * @param int $limit Limit wyników
     * @return array Ranking uczestników
     */
    public function getRankingBySessionId($sessionId, $limit = 10) {
        $this->db->query("SELECT p.id, p.nickname, p.total_score,
                         (SELECT COUNT(*) FROM participant_answers pa WHERE pa.participant_id = p.id) AS answers_count,
                         (SELECT AVG(response_time) FROM participant_answers pa WHERE pa.participant_id = p.id) AS average_time
                         FROM participants p 
                         WHERE p.session_id = :session_id 
                         ORDER BY p.total_score DESC, average_time ASC
                         LIMIT :limit");
                         
        $this->db->bind(':session_id', $sessionId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Przypisuje właściwości z wiersza bazy danych
     * 
     * @param array $row Wiersz z bazy danych
     * @return void
     */
    private function mapProperties($row) {
        $this->id = $row['id'];
        $this->session_id = $row['session_id'];
        $this->nickname = $row['nickname'];
        $this->device_id = $row['device_id'];
        $this->total_score = $row['total_score'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }
}
