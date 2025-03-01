<?php
// app/controllers/QuizController.php
// Kontroler obsługujący zarządzanie quizami

class QuizController {
    private $quizModel;
    private $questionModel;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        $this->quizModel = new Quiz();
        $this->questionModel = new Question();
    }
    
    /**
     * Wyświetla listę quizów użytkownika
     * 
     * @return void
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Wyszukiwanie
        $searchTerm = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        
        if (!empty($searchTerm)) {
            $quizzes = $this->quizModel->searchByUserId($userId, $searchTerm, $limit, $offset);
            $total = $this->quizModel->searchCountByUserId($userId, $searchTerm);
        } else {
            $quizzes = $this->quizModel->getByUserId($userId, $limit, $offset);
            $total = $this->quizModel->getCountByUserId($userId);
        }
        
        $totalPages = ceil($total / $limit);
        
        // Dane dla widoku
        $data = [
            'quizzes' => $quizzes,
            'page' => $page,
            'totalPages' => $totalPages,
            'searchTerm' => $searchTerm
        ];
        
        // Wyświetl widok listy quizów
        include APP_PATH . '/views/quiz/index.php';
    }
    
    /**
     * Wyświetla formularz tworzenia nowego quizu
     * 
     * @return void
     */
    public function create() {
        // Wyświetl widok formularza tworzenia quizu
        include APP_PATH . '/views/quiz/create.php';
    }
    
    /**
     * Przetwarza formularz tworzenia nowego quizu
     * 
     * @return void
     */
    public function store() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quizzes');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quizzes/create');
        }
        
        // Pobierz dane z formularza
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        // Walidacja
        $errors = [];
        
        if (empty($title)) {
            $errors['title'] = __('quiz_title_required');
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'title' => $title,
                'description' => $description
            ];
            redirect('quizzes/create');
        }
        
        // Utwórz nowy quiz
        $this->quizModel->user_id = $_SESSION['user_id'];
        $this->quizModel->title = $title;
        $this->quizModel->description = $description;
        $this->quizModel->status = 'draft';
        
        if ($this->quizModel->create()) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('quiz_created');
            
            // Przekieruj do edycji quizu
            redirect('quizzes/edit/' . $this->quizModel->id);
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('quiz_create_error');
            $_SESSION['form_data'] = [
                'title' => $title,
                'description' => $description
            ];
            redirect('quizzes/create');
        }
    }
    
    /**
     * Wyświetla formularz edycji quizu
     * 
     * @param int $id ID quizu
     * @return void
     */
    public function edit($id) {
        // Pobierz quiz
        if (!$this->quizModel->getById($id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz pytania quizu
        $questions = $this->questionModel->getByQuizId($id);
        
        // Dla każdego pytania pobierz odpowiedzi
        foreach ($questions as &$question) {
            $this->questionModel->id = $question['id'];
            $question['answers'] = $this->questionModel->getAnswers();
        }
        
        // Dane dla widoku
        $data = [
            'quiz' => $this->quizModel,
            'questions' => $questions
        ];
        
        // Wyświetl widok formularza edycji quizu
        include APP_PATH . '/views/quiz/edit.php';
    }
    
    /**
     * Przetwarza formularz edycji quizu
     * 
     * @param int $id ID quizu
     * @return void
     */
    public function update($id) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quizzes');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quizzes/edit/' . $id);
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz dane z formularza
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $status = sanitize($_POST['status'] ?? 'draft');
        
        // Walidacja
        $errors = [];
        
        if (empty($title)) {
            $errors['title'] = __('quiz_title_required');
        }
        
        if (!in_array($status, ['draft', 'active', 'archived'])) {
            $status = 'draft';
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('quizzes/edit/' . $id);
        }
        
        // Aktualizuj quiz
        $this->quizModel->title = $title;
        $this->quizModel->description = $description;
        $this->quizModel->status = $status;
        
        if ($this->quizModel->update()) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('quiz_updated');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('quiz_update_error');
        }
        
        redirect('quizzes/edit/' . $id);
    }
    
    /**
     * Usuwa quiz
     * 
     * @param int $id ID quizu
     * @return void
     */
    public function delete($id) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quizzes');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quizzes');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        if ($this->quizModel->delete()) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('quiz_deleted');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('quiz_delete_error');
        }
        
        redirect('quizzes');
    }
    
    /**
     * Regeneruje kod dostępu do quizu
     * 
     * @param int $id ID quizu
     * @return void
     */
    public function regenerateAccessCode($id) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quizzes');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quizzes/edit/' . $id);
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        if ($this->quizModel->regenerateAccessCode()) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('quiz_code_regenerated');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('quiz_code_regenerate_error');
        }
        
        redirect('quizzes/edit/' . $id);
    }
    
    /**
     * Dodaje nowe pytanie do quizu
     * 
     * @param int $id ID quizu
     * @return void
     */
    public function addQuestion($id) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quizzes/edit/' . $id);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quizzes/edit/' . $id);
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz dane z formularza
        $questionText = sanitize($_POST['question_text'] ?? '');
        $questionType = sanitize($_POST['question_type'] ?? '');
        $timeLimit = (int) ($_POST['time_limit'] ?? 30);
        $points = (int) ($_POST['points'] ?? 1);
        $answers = $_POST['answers'] ?? [];
        $correctAnswers = $_POST['correct_answers'] ?? [];
        
        // Walidacja
        $errors = [];
        
        if (empty($questionText)) {
            $errors['question_text'] = __('question_text_required');
        }
        
        if (!in_array($questionType, ['single', 'multiple', 'boolean'])) {
            $errors['question_type'] = __('question_type_invalid');
        }
        
        if ($timeLimit < 5 || $timeLimit > 300) {
            $errors['time_limit'] = __('question_time_limit_invalid');
        }
        
        if ($points < 1 || $points > 1000) {
            $errors['points'] = __('question_points_invalid');
        }
        
        if ($questionType === 'boolean') {
            // Dla pytań typu tak/nie automatycznie ustawiamy odpowiedzi
            $answers = [__('answer_yes'), __('answer_no')];
            if (empty($correctAnswers)) {
                $correctAnswers = [0]; // Domyślnie "Tak" jest poprawne
            }
        } else {
            // Dla innych typów pytań sprawdzamy odpowiedzi
            if (count($answers) < 2) {
                $errors['answers'] = __('question_answers_min');
            }
            
            if (empty($correctAnswers)) {
                $errors['correct_answers'] = __('question_correct_answer_required');
            }
            
            if ($questionType === 'single' && count($correctAnswers) > 1) {
                $errors['correct_answers'] = __('question_single_multiple_correct');
            }
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'question_text' => $questionText,
                'question_type' => $questionType,
                'time_limit' => $timeLimit,
                'points' => $points,
                'answers' => $answers,
                'correct_answers' => $correctAnswers
            ];
            redirect('quizzes/edit/' . $id);
        }
        
        // Utwórz nowe pytanie
        $this->questionModel->quiz_id = $id;
        $this->questionModel->question_text = $questionText;
        $this->questionModel->question_type = $questionType;
        $this->questionModel->time_limit = $timeLimit;
        $this->questionModel->points = $points;
        
        if ($this->questionModel->create()) {
            // Dodaj odpowiedzi
            foreach ($answers as $index => $answerText) {
                if (!empty($answerText)) {
                    $isCorrect = in_array($index, $correctAnswers);
                    $this->questionModel->addAnswer($answerText, $isCorrect);
                }
            }
            
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('question_added');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('question_add_error');
        }
        
        redirect('quizzes/edit/' . $id);
    }
    
    /**
     * Aktualizuje pytanie quizu
     * 
     * @param int $quizId ID quizu
     * @param int $questionId ID pytania
     * @return void
     */
    public function updateQuestion($quizId, $questionId) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($quizId)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz pytanie
        if (!$this->questionModel->getById($questionId)) {
            $_SESSION['error'] = __('question_not_found');
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Sprawdź czy pytanie należy do tego quizu
        if ($this->questionModel->quiz_id != $quizId) {
            $_SESSION['error'] = __('question_not_in_quiz');
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Pobierz dane z formularza
        $questionText = sanitize($_POST['question_text'] ?? '');
        $questionType = $this->questionModel->question_type; // Nie pozwalamy na zmianę typu pytania
        $timeLimit = (int) ($_POST['time_limit'] ?? 30);
        $points = (int) ($_POST['points'] ?? 1);
        $answers = $_POST['answers'] ?? [];
        $correctAnswers = $_POST['correct_answers'] ?? [];
        $answerIds = $_POST['answer_ids'] ?? [];
        
        // Walidacja
        $errors = [];
        
        if (empty($questionText)) {
            $errors['question_text'] = __('question_text_required');
        }
        
        if ($timeLimit < 5 || $timeLimit > 300) {
            $errors['time_limit'] = __('question_time_limit_invalid');
        }
        
        if ($points < 1 || $points > 1000) {
            $errors['points'] = __('question_points_invalid');
        }
        
        if ($questionType === 'boolean') {
            // Dla pytań typu tak/nie sprawdzamy tylko poprawną odpowiedź
            if (empty($correctAnswers)) {
                $errors['correct_answers'] = __('question_correct_answer_required');
            }
        } else {
            // Dla innych typów pytań sprawdzamy odpowiedzi
            if (count($answers) < 2) {
                $errors['answers'] = __('question_answers_min');
            }
            
            if (empty($correctAnswers)) {
                $errors['correct_answers'] = __('question_correct_answer_required');
            }
            
            if ($questionType === 'single' && count($correctAnswers) > 1) {
                $errors['correct_answers'] = __('question_single_multiple_correct');
            }
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Aktualizuj pytanie
        $this->questionModel->question_text = $questionText;
        $this->questionModel->time_limit = $timeLimit;
        $this->questionModel->points = $points;
        
        if ($this->questionModel->update()) {
            // Usuń stare odpowiedzi
            $this->questionModel->deleteAnswers();
            
            // Dodaj nowe odpowiedzi
            foreach ($answers as $index => $answerText) {
                if (!empty($answerText)) {
                    $isCorrect = in_array($index, $correctAnswers);
                    $this->questionModel->addAnswer($answerText, $isCorrect);
                }
            }
            
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('question_updated');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('question_update_error');
        }
        
        redirect('quizzes/edit/' . $quizId);
    }
    
    /**
     * Usuwa pytanie quizu
     * 
     * @param int $quizId ID quizu
     * @param int $questionId ID pytania
     * @return void
     */
    public function deleteQuestion($quizId, $questionId) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($quizId)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz pytanie
        if (!$this->questionModel->getById($questionId)) {
            $_SESSION['error'] = __('question_not_found');
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Sprawdź czy pytanie należy do tego quizu
        if ($this->questionModel->quiz_id != $quizId) {
            $_SESSION['error'] = __('question_not_in_quiz');
            redirect('quizzes/edit/' . $quizId);
        }
        
        if ($this->questionModel->delete()) {
            // Ustaw komunikat sukcesu
            $_SESSION['success'] = __('question_deleted');
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('question_delete_error');
        }
        
        redirect('quizzes/edit/' . $quizId);
    }
    
    /**
     * Zmienia kolejność pytań
     * 
     * @param int $quizId ID quizu
     * @return void
     */
    public function reorderQuestions($quizId) {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($quizId)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz nową kolejność pytań
        $questionIds = $_POST['question_ids'] ?? [];
        
        if (empty($questionIds)) {
            $_SESSION['error'] = __('question_reorder_error');
            redirect('quizzes/edit/' . $quizId);
        }
        
        // Aktualizuj pozycje pytań
        $success = true;
        
        foreach ($questionIds as $position => $questionId) {
            if ($this->questionModel->getById($questionId)) {
                // Sprawdź czy pytanie należy do tego quizu
                if ($this->questionModel->quiz_id == $quizId) {
                    if (!$this->questionModel->updatePosition($position)) {
                        $success = false;
                    }
                }
            }
        }
        
        if ($success) {
            $_SESSION['success'] = __('question_reordered');
        } else {
            $_SESSION['error'] = __('question_reorder_error');
        }
        
        redirect('quizzes/edit/' . $quizId);
    }
    
    /**
     * Uruchamia quiz (tworzy nową sesję)
     * 
     * @param int $id ID quizu
     * @return void
     */
    public function start($id) {
        // Pobierz quiz
        if (!$this->quizModel->getById($id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz ma pytania
        $questionsCount = $this->quizModel->getQuestionsCount();
        
        if ($questionsCount == 0) {
            $_SESSION['error'] = __('quiz_no_questions');
            redirect('quizzes/edit/' . $id);
        }
        
        // Utwórz nową sesję quizu
        $sessionId = $this->quizModel->createSession();
        
        if ($sessionId) {
            // Ustaw quiz jako aktywny
            $this->quizModel->status = 'active';
            $this->quizModel->update();
            
            // Przekieruj do panelu prowadzenia quizu
            redirect('sessions/host/' . $sessionId);
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('quiz_start_error');
            redirect('quizzes/edit/' . $id);
        }
    }
    
    /**
     * Eksportuje wyniki quizu do PDF
     * 
     * @param int $id ID quizu
     * @return void
     */
    public function exportPdf($id) {
        // Pobierz quiz
        if (!$this->quizModel->getById($id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz sesje quizu
        $sessions = $this->quizModel->getSessions();
        
        if (empty($sessions)) {
            $_SESSION['error'] = __('quiz_no_sessions');
            redirect('quizzes');
        }
        
        // Utwórz eksport PDF
        require_once ROOT_PATH . '/app/helpers/ExportHelper.php';
        
        $export = new ExportHelper();
        $export->exportQuizToPdf($this->quizModel, $sessions);
    }
    
    /**
     * Eksportuje wyniki quizu do XLSX
     * 
     * @param int $id ID quizu
     * @return void
     */
    public function exportXlsx($id) {
        // Pobierz quiz
        if (!$this->quizModel->getById($id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz sesje quizu
        $sessions = $this->quizModel->getSessions();
        
        if (empty($sessions)) {
            $_SESSION['error'] = __('quiz_no_sessions');
            redirect('quizzes');
        }
        
        // Utwórz eksport XLSX
        require_once ROOT_PATH . '/app/helpers/ExportHelper.php';
        
        $export = new ExportHelper();
        $export->exportQuizToXlsx($this->quizModel, $sessions);
    }
}