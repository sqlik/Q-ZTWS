<?php
/**
 * app/models/Session.php
 * Model sesji quizu w aplikacji Q-ZTWS
 */

class Session {
    private $db;
    
    // Właściwości modelu
    public $id;
    public $quiz_id;
    public $start_time;
    public $end_time;
    public $status;
    public $current_question_id;
    public $created_at;
    public $updated_at;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Pobiera sesję po ID
     * 
     * @param int $id ID sesji
     * @return bool Czy operacja się powiodła
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM sessions WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera aktywną sesję dla quizu
     * 
     * @param int $quizId ID quizu
     * @return array|false Sesja lub false
     */
    public function getActiveByQuizId($quizId) {
        $this->db->query("SELECT * FROM sessions WHERE quiz_id = :quiz_id AND status = 'active' ORDER BY start_time DESC LIMIT 1");
        $this->db->bind(':quiz_id', $quizId);
        
        return $this->db->fetch();
    }
    
    /**
     * Pobiera wszystkie sesje dla quizu
     * 
     * @param int $quizId ID quizu
     * @param int $limit Limit wyników
     * @param int $offset Przesunięcie wyników
     * @return array Tablica sesji
     */
    public function getByQuizId($quizId, $limit = 100, $offset = 0) {
        $this->db->query("SELECT s.*, 
                         (SELECT COUNT(*) FROM participants WHERE session_id = s.id) AS participants_count
                         FROM sessions s
                         WHERE s.quiz_id = :quiz_id 
                         ORDER BY s.created_at DESC 
                         LIMIT :limit OFFSET :offset");
                         
        $this->db->bind(':quiz_id', $quizId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera liczbę sesji dla quizu
     * 
     * @param int $quizId ID quizu
     * @return int Liczba sesji
     */
    public function getCountByQuizId($quizId) {
        $this->db->query("SELECT COUNT(*) FROM sessions WHERE quiz_id = :quiz_id");
        $this->db->bind(':quiz_id', $quizId);
        
        return $this->db->fetchColumn();
    }
    
    /**
     * Rozpoczyna sesję
     * 
     * @return bool Czy operacja się powiodła
     */
    public function start() {
        // Najpierw pobierz pierwsze pytanie
        $this->db->query("SELECT id FROM questions WHERE quiz_id = :quiz_id ORDER BY position ASC LIMIT 1");
        $this->db->bind(':quiz_id', $this->quiz_id);
        
        $firstQuestionId = $this->db->fetchColumn();
        
        if (!$firstQuestionId) {
            return false; // Brak pytań w quizie
        }
        
        $this->db->query("UPDATE sessions SET 
                          status = 'active', 
                          start_time = NOW(), 
                          current_question_id = :question_id 
                          WHERE id = :id");
                          
        $this->db->bind(':question_id', $firstQuestionId);
        $this->db->bind(':id', $this->id);
        
        if ($this->db->execute()) {
            $this->status = 'active';
            $this->start_time = date('Y-m-d H:i:s');
            $this->current_question_id = $firstQuestionId;
            return true;
        }
        
        return false;
    }
    
    /**
     * Kończy sesję
     * 
     * @return bool Czy operacja się powiodła
     */
    public function end() {
        $this->db->query("UPDATE sessions SET 
                          status = 'completed', 
                          end_time = NOW(), 
                          current_question_id = NULL 
                          WHERE id = :id");
                          
        $this->db->bind(':id', $this->id);
        
        if ($this->db->execute()) {
            $this->status = 'completed';
            $this->end_time = date('Y-m-d H:i:s');
            $this->current_question_id = null;
            return true;
        }
        
        return false;
    }
    
    /**
     * Ustawia aktualne pytanie w sesji
     * 
     * @param int $questionId ID pytania
     * @return bool Czy operacja się powiodła
     */
    public function setCurrentQuestion($questionId) {
        $this->db->query("UPDATE sessions SET current_question_id = :question_id WHERE id = :id");
        $this->db->bind(':question_id', $questionId);
        $this->db->bind(':id', $this->id);
        
        if ($this->db->execute()) {
            $this->current_question_id = $questionId;
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera aktualne pytanie w sesji
     * 
     * @return array|false Pytanie lub false
     */
    public function getCurrentQuestion() {
        if (!$this->current_question_id) {
            return false;
        }
        
        $questionModel = new Question();
        if ($questionModel->getById($this->current_question_id)) {
            return $questionModel;
        }
        
        return false;
    }
    
    /**
     * Pobiera uczestników sesji
     * 
     * @return array Tablica uczestników
     */
    public function getParticipants() {
        $participantModel = new Participant();
        return $participantModel->getBySessionId($this->id);
    }
    
    /**
     * Pobiera uczestników sesji wraz z wynikami
     * 
     * @return array Tablica uczestników z wynikami
     */
    public function getParticipantsWithResults() {
        $participantModel = new Participant();
        return $participantModel->getBySessionIdWithResults($this->id);
    }
    
    /**
     * Pobiera liczbę uczestników sesji
     * 
     * @return int Liczba uczestników
     */
    public function getParticipantsCount() {
        $participantModel = new Participant();
        return $participantModel->getCountBySessionId($this->id);
    }
    
    /**
     * Sprawdza czy sesja ma uczestników
     * 
     * @return bool Czy sesja ma uczestników
     */
    public function hasParticipants() {
        return $this->getParticipantsCount() > 0;
    }
    
    /**
     * Pobiera ranking uczestników sesji
     * 
     * @param int $limit Limit wyników
     * @return array Ranking uczestników
     */
    public function getParticipantsRanking($limit = 10) {
        $participantModel = new Participant();
        return $participantModel->getRankingBySessionId($this->id, $limit);
    }
    
    /**
     * Pobiera statystyki sesji
     * 
     * @return array Statystyki sesji
     */
    public function getStatistics() {
        // Podstawowe statystyki
        $participantsCount = $this->getParticipantsCount();
        
        // Całkowita liczba pytań
        $this->db->query("SELECT COUNT(*) FROM questions WHERE quiz_id = :quiz_id");
        $this->db->bind(':quiz_id', $this->quiz_id);
        $questionsCount = $this->db->fetchColumn();
        
        // Średni wynik
        $this->db->query("SELECT AVG(total_score) FROM participants WHERE session_id = :session_id");
        $this->db->bind(':session_id', $this->id);
        $averageScore = $this->db->fetchColumn();
        
        // Średni czas odpowiedzi
        $this->db->query("SELECT AVG(response_time) FROM participant_answers pa 
                         JOIN participants p ON pa.participant_id = p.id 
                         WHERE p.session_id = :session_id");
        $this->db->bind(':session_id', $this->id);
        $averageResponseTime = $this->db->fetchColumn();
        
        // Procent poprawnych odpowiedzi
        $this->db->query("SELECT 
                         (SELECT COUNT(*) FROM participant_answers pa 
                          JOIN participants p ON pa.participant_id = p.id 
                          JOIN answers a ON pa.answer_id = a.id 
                          WHERE p.session_id = :session_id AND a.is_correct = 1) 
                         / 
                         (SELECT COUNT(*) FROM participant_answers pa 
                          JOIN participants p ON pa.participant_id = p.id 
                          WHERE p.session_id = :session_id) 
                         * 100");
        $this->db->bind(':session_id', $this->id);
        $correctAnswersPercentage = $this->db->fetchColumn();
        
        return [
            'participants_count' => $participantsCount,
            'questions_count' => $questionsCount,
            'average_score' => $averageScore,
            'average_response_time' => $averageResponseTime,
            'correct_answers_percentage' => $correctAnswersPercentage
        ];
    }
    
    /**
     * Przypisuje właściwości z wiersza bazy danych
     * 
     * @param array $row Wiersz z bazy danych
     * @return void
     */
    private function mapProperties($row) {
        $this->id = $row['id'];
        $this->quiz_id = $row['quiz_id'];
        $this->start_time = $row['start_time'];
        $this->end_time = $row['end_time'];
        $this->status = $row['status'];
        $this->current_question_id = $row['current_question_id'] ?? null;
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }
}