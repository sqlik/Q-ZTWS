<?php
// app/controllers/SessionController.php
// Kontroler obsługujący sesje quizów (prowadzenie quizu)

class SessionController {
    private $sessionModel;
    private $quizModel;
    private $questionModel;
    private $participantModel;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->sessionModel = new Session();
        $this->quizModel = new Quiz();
        $this->questionModel = new Question();
        $this->participantModel = new Participant();
    }
    
    /**
     * Wyświetla panel prowadzenia quizu
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function host($id) {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('quizzes');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($this->sessionModel->quiz_id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz pytania quizu
        $questions = $this->questionModel->getByQuizId($this->quizModel->id);
        
        // Dla każdego pytania pobierz odpowiedzi
        foreach ($questions as &$question) {
            $this->questionModel->id = $question['id'];
            $question['answers'] = $this->questionModel->getAnswers();
        }
        
        // Pobierz uczestników sesji
        $participants = $this->participantModel->getBySessionId($id);
        
        // Dane dla widoku
        $data = [
            'session' => $this->sessionModel,
            'quiz' => $this->quizModel,
            'questions' => $questions,
            'participants' => $participants
        ];
        
        // Wyświetl widok panelu prowadzenia quizu
        include APP_PATH . '/views/session/host.php';
    }
    
    /**
     * Rozpoczyna sesję quizu
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function start($id) {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sessions/host/' . $id);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('sessions/host/' . $id);
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('quizzes');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($this->sessionModel->quiz_id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Rozpocznij sesję
        if ($this->sessionModel->start()) {
            // Socket.io: Wyślij komunikat o rozpoczęciu sesji
            $this->emitSessionEvent($id, 'session_started', [
                'session_id' => $id,
                'quiz_id' => $this->sessionModel->quiz_id
            ]);
            
            $_SESSION['success'] = __('session_started');
        } else {
            $_SESSION['error'] = __('session_start_error');
        }
        
        redirect('sessions/host/' . $id);
    }
    
    /**
     * Kończy sesję quizu
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function end($id) {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sessions/host/' . $id);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('sessions/host/' . $id);
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('quizzes');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($this->sessionModel->quiz_id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Zakończ sesję
        if ($this->sessionModel->end()) {
            // Socket.io: Wyślij komunikat o zakończeniu sesji
            $this->emitSessionEvent($id, 'session_ended', [
                'session_id' => $id,
                'quiz_id' => $this->sessionModel->quiz_id
            ]);
            
            $_SESSION['success'] = __('session_ended');
        } else {
            $_SESSION['error'] = __('session_end_error');
        }
        
        redirect('sessions/results/' . $id);
    }
    
    /**
     * Wyświetla wyniki sesji quizu
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function results($id) {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('quizzes');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($this->sessionModel->quiz_id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz pytania quizu
        $questions = $this->questionModel->getByQuizId($this->quizModel->id);
        
        // Dla każdego pytania pobierz odpowiedzi
        foreach ($questions as &$question) {
            $this->questionModel->id = $question['id'];
            $question['answers'] = $this->questionModel->getAnswers();
        }
        
        // Pobierz uczestników sesji
        $participants = $this->participantModel->getBySessionIdWithResults($id);
        
        // Dane dla widoku
        $data = [
            'session' => $this->sessionModel,
            'quiz' => $this->quizModel,
            'questions' => $questions,
            'participants' => $participants
        ];
        
        // Wyświetl widok wyników sesji quizu
        include APP_PATH . '/views/session/results.php';
    }
    
    /**
     * Przechodzi do następnego pytania w sesji
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function nextQuestion($id) {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sessions/host/' . $id);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('sessions/host/' . $id);
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('quizzes');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($this->sessionModel->quiz_id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz aktualne pytanie
        $currentQuestionId = $this->sessionModel->current_question_id;
        
        // Pobierz następne pytanie
        $nextQuestion = $this->questionModel->getNextQuestion($this->quizModel->id, $currentQuestionId);
        
        if ($nextQuestion) {
            // Zaktualizuj aktualne pytanie w sesji
            if ($this->sessionModel->setCurrentQuestion($nextQuestion['id'])) {
                // Socket.io: Wyślij komunikat o zmianie pytania
                $this->emitSessionEvent($id, 'question_changed', [
                    'session_id' => $id,
                    'question_id' => $nextQuestion['id'],
                    'question_type' => $nextQuestion['question_type'],
                    'question_text' => $nextQuestion['question_text'],
                    'time_limit' => $nextQuestion['time_limit'],
                    'points' => $nextQuestion['points']
                ]);
                
                $_SESSION['success'] = __('question_changed');
            } else {
                $_SESSION['error'] = __('question_change_error');
            }
        } else {
            // Brak kolejnych pytań, zakończ sesję
            if ($this->sessionModel->end()) {
                // Socket.io: Wyślij komunikat o zakończeniu sesji
                $this->emitSessionEvent($id, 'session_ended', [
                    'session_id' => $id,
                    'quiz_id' => $this->sessionModel->quiz_id
                ]);
                
                $_SESSION['success'] = __('session_ended');
                redirect('sessions/results/' . $id);
            } else {
                $_SESSION['error'] = __('session_end_error');
            }
        }
        
        redirect('sessions/host/' . $id);
    }
    
    /**
     * Pokazuje odpowiedzi na aktualne pytanie
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function showAnswers($id) {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('sessions/host/' . $id);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('sessions/host/' . $id);
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('quizzes');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($this->sessionModel->quiz_id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('quizzes');
        }
        
        // Sprawdź czy quiz należy do zalogowanego użytkownika
        if ($this->quizModel->user_id != $_SESSION['user_id']) {
            $_SESSION['error'] = __('quiz_not_owned');
            redirect('quizzes');
        }
        
        // Pobierz aktualne pytanie
        $currentQuestionId = $this->sessionModel->current_question_id;
        
        if (!$currentQuestionId) {
            $_SESSION['error'] = __('no_current_question');
            redirect('sessions/host/' . $id);
        }
        
        // Pobierz odpowiedzi na pytanie
        $this->questionModel->getById($currentQuestionId);
        $answers = $this->questionModel->getAnswers();
        
        // Socket.io: Wyślij komunikat o pokazaniu odpowiedzi
        $this->emitSessionEvent($id, 'show_answers', [
            'session_id' => $id,
            'question_id' => $currentQuestionId,
            'answers' => $answers
        ]);
        
        $_SESSION['success'] = __('answers_shown');
        redirect('sessions/host/' . $id);
    }
    
    /**
     * Wyświetla stronę dołączania do quizu
     * 
     * @return void
     */
    public function join() {
        // Wyświetl widok dołączania do quizu
        include APP_PATH . '/views/session/join.php';
    }
    
    /**
     * Przetwarza dołączanie do quizu
     * 
     * @return void
     */
    public function joinQuiz() {
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('join');
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('join');
        }
        
        // Pobierz dane z formularza
        $accessCode = sanitize($_POST['access_code'] ?? '');
        $nickname = sanitize($_POST['nickname'] ?? '');
        
        // Walidacja
        $errors = [];
        
        if (empty($accessCode)) {
            $errors['access_code'] = __('join_access_code_required');
        }
        
        if (empty($nickname)) {
            $errors['nickname'] = __('join_nickname_required');
        } elseif (strlen($nickname) < 3 || strlen($nickname) > 20) {
            $errors['nickname'] = __('join_nickname_length');
        }
        
        // Jeśli są błędy, wróć do formularza
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = [
                'access_code' => $accessCode,
                'nickname' => $nickname
            ];
            redirect('join');
        }
        
        // Pobierz quiz po kodzie dostępu
        if (!$this->quizModel->getByAccessCode($accessCode)) {
            $_SESSION['error'] = __('join_invalid_code');
            $_SESSION['form_data'] = [
                'access_code' => $accessCode,
                'nickname' => $nickname
            ];
            redirect('join');
        }
        
        // Pobierz aktywną sesję quizu
        $activeSession = $this->sessionModel->getActiveByQuizId($this->quizModel->id);
        
        if (!$activeSession) {
            $_SESSION['error'] = __('join_no_active_session');
            $_SESSION['form_data'] = [
                'access_code' => $accessCode,
                'nickname' => $nickname
            ];
            redirect('join');
        }
        
        // Sprawdź czy nickname jest już zajęty w tej sesji
        if ($this->participantModel->nicknameExistsInSession($activeSession['id'], $nickname)) {
            $_SESSION['error'] = __('join_nickname_taken');
            $_SESSION['form_data'] = [
                'access_code' => $accessCode
            ];
            redirect('join');
        }
        
        // Utwórz uczestnika
        $this->participantModel->session_id = $activeSession['id'];
        $this->participantModel->nickname = $nickname;
        $this->participantModel->device_id = session_id(); // Używamy ID sesji jako identyfikatora urządzenia
        
        if ($this->participantModel->create()) {
            // Zapisujemy dane uczestnika w sesji
            $_SESSION['participant_id'] = $this->participantModel->id;
            $_SESSION['participant_nickname'] = $nickname;
            $_SESSION['session_id'] = $activeSession['id'];
            
            // Socket.io: Wyślij komunikat o dołączeniu uczestnika
            $this->emitSessionEvent($activeSession['id'], 'participant_joined', [
                'session_id' => $activeSession['id'],
                'participant_id' => $this->participantModel->id,
                'nickname' => $nickname
            ]);
            
            // Przekieruj do panelu uczestnika
            redirect('quiz/' . $activeSession['id']);
        } else {
            // Coś poszło nie tak
            $_SESSION['error'] = __('join_error');
            $_SESSION['form_data'] = [
                'access_code' => $accessCode,
                'nickname' => $nickname
            ];
            redirect('join');
        }
    }
    
    /**
     * Wyświetla panel uczestnika quizu
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function participate($id) {
        // Sprawdź czy uczestnik jest zarejestrowany w sesji
        if (!isset($_SESSION['participant_id']) || !isset($_SESSION['session_id']) || $_SESSION['session_id'] != $id) {
            redirect('join');
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('join');
        }
        
        // Sprawdź czy sesja jest aktywna
        if ($this->sessionModel->status !== 'active') {
            // Jeśli sesja się zakończyła, pokaż wyniki
            if ($this->sessionModel->status === 'completed') {
                redirect('quiz/' . $id . '/results');
            }
            
            // Jeśli sesja nie została jeszcze rozpoczęta
            $_SESSION['error'] = __('session_not_active');
            redirect('join');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($this->sessionModel->quiz_id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('join');
        }
        
        // Pobierz uczestnika
        if (!$this->participantModel->getById($_SESSION['participant_id'])) {
            $_SESSION['error'] = __('participant_not_found');
            redirect('join');
        }
        
        // Pobierz aktualne pytanie
        $currentQuestionId = $this->sessionModel->current_question_id;
        $currentQuestion = null;
        $answers = [];
        
        if ($currentQuestionId) {
            // Pobierz pytanie
            $this->questionModel->getById($currentQuestionId);
            $currentQuestion = $this->questionModel;
            
            // Pobierz odpowiedzi
            $answers = $this->questionModel->getAnswers();
            
            // Sprawdź czy uczestnik już odpowiedział na to pytanie
            $hasAnswered = $this->participantModel->hasAnsweredQuestion($_SESSION['participant_id'], $currentQuestionId);
        }
        
        // Dane dla widoku
        $data = [
            'session' => $this->sessionModel,
            'quiz' => $this->quizModel,
            'participant' => $this->participantModel,
            'currentQuestion' => $currentQuestion,
            'answers' => $answers,
            'hasAnswered' => $hasAnswered ?? false
        ];
        
        // Wyświetl widok panelu uczestnika
        include APP_PATH . '/views/session/participate.php';
    }
    
    /**
     * Przetwarza odpowiedź uczestnika
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function answer($id) {
        // Sprawdź czy uczestnik jest zarejestrowany w sesji
        if (!isset($_SESSION['participant_id']) || !isset($_SESSION['session_id']) || $_SESSION['session_id'] != $id) {
            redirect('join');
        }
        
        // Sprawdź czy to żądanie POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('quiz/' . $id);
        }
        
        // Sprawdź token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = __('csrf_error');
            redirect('quiz/' . $id);
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('join');
        }
        
        // Sprawdź czy sesja jest aktywna
        if ($this->sessionModel->status !== 'active') {
            $_SESSION['error'] = __('session_not_active');
            redirect('join');
        }
        
        // Pobierz aktualne pytanie
        $currentQuestionId = $this->sessionModel->current_question_id;
        
        if (!$currentQuestionId) {
            $_SESSION['error'] = __('no_current_question');
            redirect('quiz/' . $id);
        }
        
        // Pobierz pytanie
        if (!$this->questionModel->getById($currentQuestionId)) {
            $_SESSION['error'] = __('question_not_found');
            redirect('quiz/' . $id);
        }
        
        // Sprawdź czy uczestnik już odpowiedział na to pytanie
        if ($this->participantModel->hasAnsweredQuestion($_SESSION['participant_id'], $currentQuestionId)) {
            $_SESSION['error'] = __('already_answered');
            redirect('quiz/' . $id);
        }
        
        // Pobierz odpowiedzi z formularza
        $answerIds = isset($_POST['answers']) ? (array) $_POST['answers'] : [];
        $responseTime = (float) ($_POST['response_time'] ?? 0);
        
        // Walidacja
        if (empty($answerIds)) {
            $_SESSION['error'] = __('answer_required');
            redirect('quiz/' . $id);
        }
        
        // Jeśli pytanie jest typu "single", sprawdź czy wybrano tylko jedną odpowiedź
        if ($this->questionModel->question_type === 'single' && count($answerIds) > 1) {
            $_SESSION['error'] = __('single_choice_only');
            redirect('quiz/' . $id);
        }
        
        // Sprawdź czy odpowiedzi są poprawne
        $answers = $this->questionModel->getAnswers();
        $correctAnswerIds = [];
        
        foreach ($answers as $answer) {
            if ($answer['is_correct']) {
                $correctAnswerIds[] = $answer['id'];
            }
        }
        
        // Oblicz punkty
        $points = 0;
        $maxPoints = $this->questionModel->points;
        
        // Dla pytań z jedną poprawną odpowiedzią
        if ($this->questionModel->question_type === 'single' || $this->questionModel->question_type === 'boolean') {
            if (count(array_intersect($answerIds, $correctAnswerIds)) === 1) {
                // Punkty zależą od czasu odpowiedzi
                $timeLimit = $this->questionModel->time_limit;
                $timePercentage = max(0, min(1, 1 - ($responseTime / $timeLimit)));
                $points = $maxPoints * $timePercentage;
            }
        }
        // Dla pytań z wieloma poprawnymi odpowiedziami
        else if ($this->questionModel->question_type === 'multiple') {
            // Liczba poprawnych odpowiedzi
            $correctCount = count($correctAnswerIds);
            
            // Liczba poprawnych wybranych odpowiedzi
            $correctSelected = count(array_intersect($answerIds, $correctAnswerIds));
            
            // Liczba niepoprawnych wybranych odpowiedzi
            $incorrectSelected = count(array_diff($answerIds, $correctAnswerIds));
            
            // Procent poprawności
            $correctPercentage = $correctCount > 0 ? $correctSelected / $correctCount : 0;
            
            // Kara za niepoprawne odpowiedzi
            $penalty = $incorrectSelected > 0 ? ($incorrectSelected / count($answers)) : 0;
            
            // Punkty
            $points = $maxPoints * max(0, $correctPercentage - $penalty);
            
            // Punkty zależą od czasu odpowiedzi
            $timeLimit = $this->questionModel->time_limit;
            $timePercentage = max(0, min(1, 1 - ($responseTime / $timeLimit)));
            $points *= $timePercentage;
        }
        
        // Zapisz odpowiedź uczestnika
        $result = $this->participantModel->addAnswer(
            $_SESSION['participant_id'],
            $currentQuestionId,
            $answerIds[0], // Dla pytań typu "single" i "boolean"
            $responseTime,
            round($points)
        );
        
        if ($result) {
            // Dodaj pozostałe odpowiedzi dla pytań typu "multiple"
            if ($this->questionModel->question_type === 'multiple' && count($answerIds) > 1) {
                for ($i = 1; $i < count($answerIds); $i++) {
                    $this->participantModel->addAnswer(
                        $_SESSION['participant_id'],
                        $currentQuestionId,
                        $answerIds[$i],
                        $responseTime,
                        0 // Punkty przyznajemy tylko dla pierwszej odpowiedzi
                    );
                }
            }
            
            // Zaktualizuj sumę punktów uczestnika
            $this->participantModel->updateTotalScore($_SESSION['participant_id']);
            
            // Socket.io: Wyślij komunikat o odpowiedzi uczestnika
            $this->emitSessionEvent($id, 'participant_answered', [
                'session_id' => $id,
                'participant_id' => $_SESSION['participant_id'],
                'nickname' => $_SESSION['participant_nickname'],
                'question_id' => $currentQuestionId,
                'answer_ids' => $answerIds,
                'response_time' => $responseTime,
                'score' => round($points)
            ]);
            
            $_SESSION['success'] = __('answer_recorded');
        } else {
            $_SESSION['error'] = __('answer_error');
        }
        
        redirect('quiz/' . $id);
    }
    
    /**
     * Wyświetla wyniki quizu dla uczestnika
     * 
     * @param int $id ID sesji
     * @return void
     */
    public function participantResults($id) {
        // Sprawdź czy uczestnik jest zarejestrowany w sesji
        if (!isset($_SESSION['participant_id']) || !isset($_SESSION['session_id']) || $_SESSION['session_id'] != $id) {
            redirect('join');
        }
        
        // Pobierz sesję
        if (!$this->sessionModel->getById($id)) {
            $_SESSION['error'] = __('session_not_found');
            redirect('join');
        }
        
        // Pobierz quiz
        if (!$this->quizModel->getById($this->sessionModel->quiz_id)) {
            $_SESSION['error'] = __('quiz_not_found');
            redirect('join');
        }
        
        // Pobierz uczestnika
        if (!$this->participantModel->getById($_SESSION['participant_id'])) {
            $_SESSION['error'] = __('participant_not_found');
            redirect('join');
        }
        
        // Pobierz pytania quizu
        $questions = $this->questionModel->getByQuizId($this->quizModel->id);
        
        // Dla każdego pytania pobierz odpowiedzi i odpowiedź uczestnika
        foreach ($questions as &$question) {
            $this->questionModel->id = $question['id'];
            $question['answers'] = $this->questionModel->getAnswers();
            $question['participant_answer'] = $this->participantModel->getAnswerForQuestion(
                $_SESSION['participant_id'],
                $question['id']
            );
        }
        
        // Pobierz ranking uczestników
        $ranking = $this->participantModel->getRankingBySessionId($id);
        
        // Znajdź pozycję uczestnika w rankingu
        $participantRank = 0;
        
        foreach ($ranking as $index => $participant) {
            if ($participant['id'] == $_SESSION['participant_id']) {
                $participantRank = $index + 1;
                break;
            }
        }
        
        // Dane dla widoku
        $data = [
            'session' => $this->sessionModel,
            'quiz' => $this->quizModel,
            'participant' => $this->participantModel,
            'questions' => $questions,
            'ranking' => $ranking,
            'participantRank' => $participantRank
        ];
        
        // Wyświetl widok wyników quizu dla uczestnika
        include APP_PATH . '/views/session/participant_results.php';
    }
    
    /**
     * Emituje zdarzenie sesji (Socket.io)
     * 
     * @param int $sessionId ID sesji
     * @param string $event Nazwa zdarzenia
     * @param array $data Dane zdarzenia
     * @return void
     */
    private function emitSessionEvent($sessionId, $event, $data) {
        // W pełnej implementacji należy zintegrować Socket.io
        // Tutaj tylko przykładowa implementacja
        
        // Zapisz zdarzenie do logów
        error_log("Session event: {$event} for session {$sessionId}");
        error_log(json_encode($data));
    }
}