<?php
/**
 * app/models/Answer.php
 * Model odpowiedzi na pytanie quizu w aplikacji Q-ZTWS
 */

class Answer {
    private $db;
    
    // Właściwości modelu
    public $id;
    public $question_id;
    public $answer_text;
    public $is_correct;
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
     * Pobiera odpowiedź po ID
     * 
     * @param int $id ID odpowiedzi
     * @return bool Czy operacja się powiodła
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM answers WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera wszystkie odpowiedzi dla pytania
     * 
     * @param int $questionId ID pytania
     * @return array Tablica odpowiedzi
     */
    public function getByQuestionId($questionId) {
        $this->db->query("SELECT * FROM answers WHERE question_id = :question_id ORDER BY position ASC");
        $this->db->bind(':question_id', $questionId);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera poprawne odpowiedzi dla pytania
     * 
     * @param int $questionId ID pytania
     * @return array Tablica poprawnych odpowiedzi
     */
    public function getCorrectByQuestionId($questionId) {
        $this->db->query("SELECT * FROM answers WHERE question_id = :question_id AND is_correct = TRUE ORDER BY position ASC");
        $this->db->bind(':question_id', $questionId);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Dodaje nową odpowiedź
     * 
     * @return bool Czy operacja się powiodła
     */
    public function create() {
        // Pobieranie najwyższej pozycji
        $this->db->query("SELECT MAX(position) FROM answers WHERE question_id = :question_id");
        $this->db->bind(':question_id', $this->question_id);
        $maxPosition = $this->db->fetchColumn();
        
        $this->position = ($maxPosition !== null) ? $maxPosition + 1 : 0;
        
        // Dodawanie odpowiedzi
        $this->db->query("INSERT INTO answers (question_id, answer_text, is_correct, position) 
                          VALUES (:question_id, :answer_text, :is_correct, :position)");
        
        $this->db->bind(':question_id', $this->question_id);
        $this->db->bind(':answer_text', $this->answer_text);
        $this->db->bind(':is_correct', $this->is_correct ? 1 : 0);
        $this->db->bind(':position', $this->position);
        
        if ($this->db->execute()) {
            $this->id = $this->db->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Aktualizuje odpowiedź
     * 
     * @return bool Czy operacja się powiodła
     */
    public function update() {
        $this->db->query("UPDATE answers SET 
                          answer_text = :answer_text, 
                          is_correct = :is_correct 
                          WHERE id = :id");
        
        $this->db->bind(':answer_text', $this->answer_text);
        $this->db->bind(':is_correct', $this->is_correct ? 1 : 0);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Aktualizuje pozycję odpowiedzi
     * 
     * @param int $position Nowa pozycja
     * @return bool Czy operacja się powiodła
     */
    public function updatePosition($position) {
        $this->position = $position;
        
        $this->db->query("UPDATE answers SET position = :position WHERE id = :id");
        $this->db->bind(':position', $this->position);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Usuwa odpowiedź
     * 
     * @return bool Czy operacja się powiodła
     */
    public function delete() {
        $this->db->query("DELETE FROM answers WHERE id = :id");
        $this->db->bind(':id', $this->id);
        
        if ($this->db->execute()) {
            // Aktualizacja pozycji pozostałych odpowiedzi
            $this->db->query("UPDATE answers SET position = position - 1 
                              WHERE question_id = :question_id AND position > :position");
            $this->db->bind(':question_id', $this->question_id);
            $this->db->bind(':position', $this->position);
            
            $this->db->execute();
            return true;
        }
        
        return false;
    }
    
    /**
     * Usuwa wszystkie odpowiedzi dla pytania
     * 
     * @param int $questionId ID pytania
     * @return bool Czy operacja się powiodła
     */
    public function deleteByQuestionId($questionId) {
        $this->db->query("DELETE FROM answers WHERE question_id = :question_id");
        $this->db->bind(':question_id', $questionId);
        
        return $this->db->execute();
    }
    
    /**
     * Sprawdza czy odpowiedź jest poprawna
     * 
     * @param int $id ID odpowiedzi
     * @return bool Czy odpowiedź jest poprawna
     */
    public function isCorrect($id) {
        $this->db->query("SELECT is_correct FROM answers WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $result = $this->db->fetchColumn();
        
        return $result ? true : false;
    }
    
    /**
     * Przypisuje właściwości z wiersza bazy danych
     * 
     * @param array $row Wiersz z bazy danych
     * @return void
     */
    private function mapProperties($row) {
        $this->id = $row['id'];
        $this->question_id = $row['question_id'];
        $this->answer_text = $row['answer_text'];
        $this->is_correct = (bool) $row['is_correct'];
        $this->position = $row['position'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }
}
