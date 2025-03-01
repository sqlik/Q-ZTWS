<?php
// app/controllers/ParticipantController.php
// Kontroler obsługujący uczestników quizów

class ParticipantController {
    private $participantModel;
    private $sessionModel;
    private $quizModel;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->participantModel = new Participant();
        $this->sessionModel = new Session();
        $this->quizModel = new Quiz();
    }
    
    /**
     * Obsługuje żądania AJAX od uczestników
     * 
     * @return void
     */
    public function ajaxHandler() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit;
        }
        
        // Sprawdź czy uczestnik jest zarejestrowany
        if (!isset($_SESSION['participant_id']) || !isset($_SESSION['session_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
            exit;
        }
        
        // Pobierz dane z żądania
        $action = isset($_POST['action']) ? sanitize($_POST['action']) : '';
        
        // Obsłuż różne akcje
        switch ($action) {
            case 'check_status':
                $this->checkSessionStatus();
                break;
            
            case 'submit_answer':
                $this->submitAnswer();
                break;
            
            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
                break;
        }
        
        exit;
    }
    
    /**
     * Sprawdza status sesji quizu
     * 
     * @return void
     */
    private function checkSessionStatus() {
        $sessionId = $_SESSION['session_id'];
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($sessionId)) {
            echo json_encode(['status' => 'error', 'message' => 'Session not found']);
            exit;
        }
        
        // Sprawdź status sesji
        $response = [
            'status' => 'success',
            'session_status' => $this->sessionModel->status,
            'current_question' => null
        ];
        
        // Jeśli sesja jest aktywna i ma aktualne pytanie, zwróć dane o pytaniu
        if ($this->sessionModel->status === 'active' && $this->sessionModel->current_question_id) {
            $questionModel = new Question();
            if ($questionModel->getById($this->sessionModel->current_question_id)) {
                $response['current_question'] = [
                    'id' => $questionModel->id,
                    'question_text' => $questionModel->question_text,
                    'question_type' => $questionModel->question_type,
                    'time_limit' => $questionModel->time_limit
                ];
            }
        }
        
        echo json_encode($response);
    }
    
    /**
     * Zapisuje odpowiedź uczestnika
     * 
     * @return void
     */
    private function submitAnswer() {
        $sessionId = $_SESSION['session_id'];
        $participantId = $_SESSION['participant_id'];
        $questionId = isset($_POST['question_id']) ? (int) $_POST['question_id'] : 0;
        $answerId = isset($_POST['answer_id']) ? (int) $_POST['answer_id'] : 0;
        $responseTime = isset($_POST['response_time']) ? (float) $_POST['response_time'] : 0;
        
        // Sprawdź czy sesja istnieje i jest aktywna
        if (!$this->sessionModel->getById($sessionId) || $this->sessionModel->status !== 'active') {
            echo json_encode(['status' => 'error', 'message' => 'Session not active']);
            exit;
        }
        
        // Sprawdź czy pytanie jest aktualne
        if ($this->sessionModel->current_question_id != $questionId) {
            echo json_encode(['status' => 'error', 'message' => 'Question not current']);
            exit;
        }
        
        // Sprawdź czy uczestnik już odpowiedział
        if ($this->participantModel->hasAnsweredQuestion($participantId, $questionId)) {
            echo json_encode(['status' => 'error', 'message' => 'Already answered']);
            exit;
        }
        
        // Zapisz odpowiedź
        if ($this->participantModel->addAnswer($participantId, $questionId, $answerId, $responseTime, 0)) {
            // Aktualizuj sumę punktów
            $this->participantModel->updateTotalScore($participantId);
            
            echo json_encode(['status' => 'success', 'message' => 'Answer recorded']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error recording answer']);
        }
    }
}