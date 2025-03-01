<?php
// app/controllers/DashboardController.php
// Kontroler obsługujący dashboard użytkownika

class DashboardController {
    private $quizModel;
    private $sessionModel;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Sprawdź czy użytkownik jest zalogowany
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }
        
        $this->quizModel = new Quiz();
        $this->sessionModel = new Session();
    }
    
    /**
     * Wyświetla dashboard użytkownika
     * 
     * @return void
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        
        // Pobierz ostatnie quizy użytkownika
        $recentQuizzes = $this->quizModel->getRecentByUserId($userId, 5);
        $totalQuizzes = $this->quizModel->getCountByUserId($userId);
        
        // Pobierz ostatnie sesje użytkownika
        $recentSessions = $this->sessionModel->getRecentByUserId($userId, 5);
        $totalSessions = $this->sessionModel->getCountByUserId($userId);
        
        // Dane dla widoku
        $data = [
            'recentQuizzes' => $recentQuizzes,
            'totalQuizzes' => $totalQuizzes,
            'recentSessions' => $recentSessions,
            'totalSessions' => $totalSessions
        ];
        
        // Ustawienie tytułu strony
        $pageTitle = 'dashboard';
        
        // Wyświetl widok dashboardu
        include APP_PATH . '/views/dashboard/index.php';
    }
    
    /**
     * Zmienia język użytkownika
     * 
     * @param string $lang Kod języka (pl lub en)
     * @return void
     */
    public function language($lang) {
        // Sprawdź czy język jest dostępny
        if (!in_array($lang, ['pl', 'en'])) {
            $lang = 'pl'; // Domyślny język
        }
        
        // Ustaw język w sesji
        $_SESSION['lang'] = $lang;
        
        // Jeśli użytkownik jest zalogowany, zapisz jego preferencję w bazie
        if (isset($_SESSION['user_id'])) {
            $userModel = new User();
            if ($userModel->getById($_SESSION['user_id'])) {
                $userModel->language = $lang;
                $userModel->updateLanguage();
            }
        }
        
        // Przekieruj z powrotem na stronę, z której przyszedł
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : APP_URL;
        header("Location: {$referer}");
        exit;
    }
}