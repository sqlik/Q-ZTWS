<?php
/**
 * app/models/Question.php
 * Model pytania w quizie aplikacji Q-ZTWS
 */

class Question {
    private $db;
    
    // Właściwości modelu
    public $id;
    public $quiz_id;
    public $question_text;
    public $question_type;
    public $time_limit;
    public $points;
    public $position;
    public $created_at;
    public $updated_at;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Pobiera pytanie po ID
     * 
     * @param int $id ID pytania
     * @return bool Czy operacja się powiodła
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM questions WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera wszystkie pytania quizu
     * 
     * @param int $quizId ID quizu
     * @return array Tablica pytań
     */
    public function getByQuizId($quizId) {
        $this->db->query("SELECT * FROM questions WHERE quiz_id = :quiz_id ORDER BY position ASC");
        $this->db->bind(':quiz_id', $quizId);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera liczbę pytań w quizie
     * 
     * @param int $quizId ID quizu
     * @return int Liczba pytań
     */
    public function getCountByQuizId($quizId) {
        $this->db->query("SELECT COUNT(*) FROM questions WHERE quiz_id = :quiz_id");
        $this->db->bind(':quiz_id', $quizId);
        
        return $this->db->fetchColumn();
    }
    
    /**
     * Pobiera następne pytanie w quizie
     * 
     * @param int $quizId ID quizu
     * @param int|null $currentQuestionId ID aktualnego pytania
     * @return array|null Następne pytanie lub null
     */
    public function getNextQuestion($quizId, $currentQuestionId = null) {
        if ($currentQuestionId === null) {
            // Jeśli nie ma aktualnego pytania, pobierz pierwsze
            $this->db->query("SELECT * FROM questions WHERE quiz_id = :quiz_id ORDER BY position ASC LIMIT 1");
            $this->db->bind(':quiz_id', $quizId);
        } else {
            // Pobierz aktualną pozycję pytania
            $this->db->query("SELECT position FROM questions WHERE id = :id");
            $this->db->bind(':id', $currentQuestionId);
            $currentPosition = $this->db->fetchColumn();
            
            // Pobierz następne pytanie
            $this->db->query("SELECT * FROM questions WHERE quiz_id = :quiz_id AND position > :position 
                              ORDER BY position ASC LIMIT 1");
            $this->db->bind(':quiz_id', $quizId);
            $this->db->bind(':position', $currentPosition);
        }
        
        return $this->db->fetch();
    }
    
    /**
     * Tworzy nowe pytanie
     * 
     * @return bool Czy operacja się powiodła
     */
    public function create() {
        // Pobieranie najwyższej pozycji
        $this->db->query("SELECT MAX(position) FROM questions WHERE quiz_id = :quiz_id");
        $this->db->bind(':quiz_id', $this->quiz_id);
        $maxPosition = $this->db->fetchColumn();
        
        $this->position = ($maxPosition !== null) ? $maxPosition + 1 : 0;
        
        // Wstawianie pytania
        $this->db->query("INSERT INTO questions (quiz_id, question_text, question_type, time_limit, points, position) 
                          VALUES (:quiz_id, :question_text, :question_type, :time_limit, :points, :position)");
            
        $this->db->bind(':quiz_id', $this->quiz_id);
        $this->db->bind(':question_text', $this->question_text);
        $this->db->bind(':question_type', $this->question_type);
        $this->db->bind(':time_limit', $this->time_limit ?? 30);
        $this->db->bind(':points', $this->points ?? 1);
        $this->db->bind(':position', $this->position);
        
        if ($this->db->execute()) {
            $this->id = $this->db->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Aktualizuje pytanie
     * 
     * @return bool Czy operacja się powiodła
     */
    public function update() {
        $this->db->query("UPDATE questions SET 
                          question_text = :question_text,
                          question_type = :question_type,
                          time_limit = :time_limit,
                          points = :points
                          WHERE id = :id AND quiz_id = :quiz_id");
            
        $this->db->bind(':question_text', $this->question_text);
        $this->db->bind(':question_type', $this->question_type);
        $this->db->bind(':time_limit', $this->time_limit);
        $this->db->bind(':points', $this->points);
        $this->db->bind(':id', $this->id);
        $this->db->bind(':quiz_id', $this->quiz_id);
        
        return $this->db->execute();
    }
    
    /**
     * Aktualizuje pozycję pytania
     * 
     * @param int $position Nowa pozycja
     * @return bool Czy operacja się powiodła
     */
    public function updatePosition($position) {
        $this->position = $position;
        
        $this->db->query("UPDATE questions SET position = :position WHERE id = :id");
        $this->db->bind(':position', $this->position);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Usuwa pytanie
     * 
     * @return bool Czy operacja się powiodła
     */
    public function delete() {
        $this->db->query("DELETE FROM questions WHERE id = :id AND quiz_id = :quiz_id");
        $this->db->bind(':id', $this->id);
        $this->db->bind(':quiz_id', $this->quiz_id);
        
        if ($this->db->execute()) {
            // Aktualizacja pozycji pozostałych pytań
            $this->db->query("UPDATE questions SET position = position - 1 
                              WHERE quiz_id = :quiz_id AND position > :position");
            $this->db->bind(':quiz_id', $this->quiz_id);
            $this->db->bind(':position', $this->position);
            
            $this->db->execute();
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera odpowiedzi do pytania
     * 
     * @return array Tablica odpowiedzi
     */
    public function getAnswers() {
        $answerModel = new Answer();
        return $answerModel->getByQuestionId($this->id);
    }
    
    /**
     * Pobiera poprawne odpowiedzi do pytania
     * 
     * @return array Tablica poprawnych odpowiedzi
     */
    public function getCorrectAnswers() {
        $answerModel = new Answer();
        return $answerModel->getCorrectByQuestionId($this->id);
    }
    
    /**
     * Dodaje odpowiedź do pytania
     * 
     * @param string $answerText Treść odpowiedzi
     * @param bool $isCorrect Czy odpowiedź jest poprawna
     * @return int|bool ID odpowiedzi lub false w przypadku błędu
     */
    public function addAnswer($answerText, $isCorrect = false) {
        $answerModel = new Answer();
        $answerModel->question_id = $this->id;
        $answerModel->answer_text = $answerText;
        $answerModel->is_correct = $isCorrect;
        
        if ($answerModel->create()) {
            return $answerModel->id;
        }
        
        return false;
    }
    
    /**
     * Usuwa wszystkie odpowiedzi do pytania
     * 
     * @return bool Czy operacja się powiodła
     */
    public function deleteAnswers() {
        $answerModel = new Answer();
        return $answerModel->deleteByQuestionId($this->id);
    }
    
    /**
     * Pobiera statystyki odpowiedzi dla pytania w sesji
     * 
     * @param int $sessionId ID sesji
     * @return array Statystyki odpowiedzi
     */
    public function getAnswerStatistics($sessionId) {
        $this->db->query("SELECT a.id, a.answer_text, a.is_correct, 
                         COUNT(pa.id) as answer_count,
                         AVG(pa.response_time) as avg_response_time
                         FROM answers a
                         LEFT JOIN participant_answers pa ON a.id = pa.answer_id AND pa.question_id = a.question_id
                         LEFT JOIN participants p ON pa.participant_id = p.id AND p.session_id = :session_id
                         WHERE a.question_id = :question_id
                         GROUP BY a.id
                         ORDER BY a.position ASC");
                         
        $this->db->bind(':question_id', $this->id);
        $this->db->bind(':session_id', $sessionId);
        
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
        $this->quiz_id = $row['quiz_id'];
        $this->question_text = $row['question_text'];
        $this->question_type = $row['question_type'];
        $this->time_limit = $row['time_limit'];
        $this->points = $row['points'];
        $this->position = $row['position'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }
}