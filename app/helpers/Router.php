<?php
/**
 * app/helpers/Router.php
 * Klasa routera dla aplikacji Q-ZTWS
 */

class Router {
    /**
     * Tablica zawierająca wszystkie zdefiniowane ścieżki
     */
    private static $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => []
    ];
    
    /**
     * Dodaje nową ścieżkę GET
     * 
     * @param string $path Ścieżka URL (np. 'login', 'quiz/edit/:id')
     * @param array $target Cel routingu (controller, action)
     * @return void
     */
    public static function get($path, $target) {
        self::addRoute('GET', $path, $target);
    }
    
    /**
     * Dodaje nową ścieżkę POST
     * 
     * @param string $path Ścieżka URL
     * @param array $target Cel routingu (controller, action)
     * @return void
     */
    public static function post($path, $target) {
        self::addRoute('POST', $path, $target);
    }
    
    /**
     * Dodaje nową ścieżkę PUT
     * 
     * @param string $path Ścieżka URL
     * @param array $target Cel routingu (controller, action)
     * @return void
     */
    public static function put($path, $target) {
        self::addRoute('PUT', $path, $target);
    }
    
    /**
     * Dodaje nową ścieżkę DELETE
     * 
     * @param string $path Ścieżka URL
     * @param array $target Cel routingu (controller, action)
     * @return void
     */
    public static function delete($path, $target) {
        self::addRoute('DELETE', $path, $target);
    }
    
    /**
     * Dodaje nową ścieżkę dla dowolnej metody HTTP
     * 
     * @param string $method Metoda HTTP
     * @param string $path Ścieżka URL
     * @param array $target Cel routingu (controller, action)
     * @return void
     */
    private static function addRoute($method, $path, $target) {
        // Przygotuj kontroler i akcję
        $controller = $target[0];
        $action = $target[1];
        
        // Dodaj routę do tablicy
        self::$routes[$method][$path] = [
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    /**
     * Odnajduje i zwraca dopasowaną ścieżkę
     * 
     * @param string $method Metoda HTTP
     * @param string $path Ścieżka URL
     * @return array|null Dopasowana ścieżka lub null jeśli nie znaleziono
     */
    public static function match($method, $path) {
        // Jeśli ścieżka jest pusta, użyj domyślnej
        if (empty($path)) {
            $path = '/';
        }
        
        // Sprawdź, czy istnieje dokładne dopasowanie
        if (isset(self::$routes[$method][$path])) {
            return [
                'target' => self::$routes[$method][$path],
                'params' => []
            ];
        }
        
        // Sprawdź dynamiczne ścieżki z parametrami
        foreach (self::$routes[$method] as $route => $target) {
            $pattern = self::convertRouteToRegex($route);
            if (preg_match($pattern, $path, $matches)) {
                // Usuń pełne dopasowanie
                array_shift($matches);
                
                // Pobierz nazwy parametrów
                $paramNames = self::getParamNames($route);
                
                // Przypisz wartości do nazw parametrów
                $params = [];
                foreach ($paramNames as $index => $name) {
                    $params[$name] = $matches[$index];
                }
                
                return [
                    'target' => $target,
                    'params' => $params
                ];
            }
        }
        
        // Nie znaleziono dopasowania
        return null;
    }
    
    /**
     * Konwertuje ścieżkę na wyrażenie regularne
     * 
     * @param string $route Ścieżka URL
     * @return string Wyrażenie regularne
     */
    private static function convertRouteToRegex($route) {
        // Zamień parametry (:name) na grupy przechwytujące
        $route = preg_replace('/:[a-zA-Z0-9_]+/', '([^/]+)', $route);
        
        // Dodaj ograniczniki i początek/koniec ścieżki
        return '#^' . $route . '$#';
    }
    
    /**
     * Pobiera nazwy parametrów ze ścieżki
     * 
     * @param string $route Ścieżka URL
     * @return array Nazwy parametrów
     */
    private static function getParamNames($route) {
        $paramNames = [];
        preg_match_all('/:([a-zA-Z0-9_]+)/', $route, $matches);
        
        if (isset($matches[1])) {
            $paramNames = $matches[1];
        }
        
        return $paramNames;
    }
    
    /**
     * Inicjalizuje router i definiuje wszystkie ścieżki aplikacji
     * 
     * @return void
     */
    public static function init() {
        // Strona główna
        self::get('/', ['Dashboard', 'index']);
        self::get('/dashboard', ['Dashboard', 'index']);
        
        // Autentykacja
        self::get('/login', ['Auth', 'loginForm']);
        self::post('/login', ['Auth', 'login']);
        self::get('/register', ['Auth', 'registerForm']);
        self::post('/register', ['Auth', 'register']);
        self::get('/forgot-password', ['Auth', 'forgotPasswordForm']);
        self::post('/forgot-password', ['Auth', 'forgotPassword']);
        self::get('/reset-password/:token', ['Auth', 'resetPasswordForm']);
        self::post('/reset-password', ['Auth', 'resetPassword']);
        self::get('/activate/:code', ['Auth', 'activate']);
        self::get('/logout', ['Auth', 'logout']);
        
        // Profil użytkownika
        self::get('/profile', ['User', 'profile']);
        self::post('/profile', ['User', 'updateProfile']);
        self::get('/change-password', ['User', 'changePassword']);
        self::post('/change-password', ['User', 'updatePassword']);
        
        // Quizy
        self::get('/quizzes', ['Quiz', 'index']);
        self::get('/quizzes/create', ['Quiz', 'create']);
        self::post('/quizzes/create', ['Quiz', 'store']);
        self::get('/quizzes/edit/:id', ['Quiz', 'edit']);
        self::post('/quizzes/edit/:id', ['Quiz', 'update']);
        self::post('/quizzes/delete/:id', ['Quiz', 'delete']);
        self::post('/quizzes/:id/regenerate-code', ['Quiz', 'regenerateAccessCode']);
        self::post('/quizzes/:id/add-question', ['Quiz', 'addQuestion']);
        self::post('/quizzes/:id/question/:questionId', ['Quiz', 'updateQuestion']);
        self::post('/quizzes/:id/delete-question/:questionId', ['Quiz', 'deleteQuestion']);
        self::post('/quizzes/:id/reorder-questions', ['Quiz', 'reorderQuestions']);
        self::post('/quizzes/:id/start', ['Quiz', 'start']);
        self::get('/quizzes/:id/export-pdf', ['Quiz', 'exportPdf']);
        self::get('/quizzes/:id/export-xlsx', ['Quiz', 'exportXlsx']);
        
        // Sesje quizów
        self::get('/sessions/host/:id', ['Session', 'host']);
        self::post('/sessions/:id/start', ['Session', 'start']);
        self::post('/sessions/:id/end', ['Session', 'end']);
        self::get('/sessions/results/:id', ['Session', 'results']);
        self::post('/sessions/:id/next-question', ['Session', 'nextQuestion']);
        self::post('/sessions/:id/show-answers', ['Session', 'showAnswers']);
        
        // Dołączanie do quizu
        self::get('/join', ['Session', 'join']);
        self::post('/join', ['Session', 'joinQuiz']);
        self::get('/quiz/:id', ['Session', 'participate']);
        self::post('/quiz/:id/answer', ['Session', 'answer']);
        self::get('/quiz/:id/results', ['Session', 'participantResults']);
        
        // Panel administratora
        self::get('/admin/dashboard', ['Admin', 'dashboard']);
        self::get('/admin/users', ['Admin', 'users']);
        self::get('/admin/users/create', ['Admin', 'createUser']);
        self::post('/admin/users/create', ['Admin', 'storeUser']);
        self::get('/admin/users/edit/:id', ['Admin', 'editUser']);
        self::post('/admin/users/edit/:id', ['Admin', 'updateUser']);
        self::post('/admin/users/delete/:id', ['Admin', 'deleteUser']);
        self::get('/admin/settings', ['Admin', 'settings']);
        self::post('/admin/settings', ['Admin', 'updateSettings']);
        self::get('/admin/email-templates', ['Admin', 'emailTemplates']);
        self::get('/admin/email-templates/edit/:id', ['Admin', 'editEmailTemplate']);
        self::post('/admin/email-templates/edit/:id', ['Admin', 'updateEmailTemplate']);
        self::post('/admin/email-templates/:id/test', ['Admin', 'sendTestEmail']);
        
        // Zmiana języka
        self::get('/language/:lang', ['Dashboard', 'language']);
    }
    
    /**
     * Uruchamia router i przetwarza bieżące żądanie
     * 
     * @return void
     */
    public static function run() {
        // Inicjalizacja routera
        self::init();
        
        // Pobranie metody HTTP i URL
        $method = $_SERVER['REQUEST_METHOD'];
        $url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
        
        // Dopasowanie ścieżki
        $match = self::match($method, $url);
        
        if ($match) {
            // Pobierz kontroler i akcję
            $controllerName = $match['target']['controller'] . 'Controller';
            $action = $match['target']['action'];
            $params = $match['params'];
            
            // Utwórz pełną ścieżkę do pliku kontrolera
            $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
            
            // Sprawdź czy kontroler istnieje
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                
                // Utwórz instancję kontrolera
                $controller = new $controllerName();
                
                // Sprawdź czy akcja istnieje
                if (method_exists($controller, $action)) {
                    // Wywołaj akcję z parametrami
                    call_user_func_array([$controller, $action], $params);
                } else {
                    // Błąd 404 - nie znaleziono akcji
                    self::handleNotFound();
                }
            } else {
                // Błąd 404 - nie znaleziono kontrolera
                self::handleNotFound();
            }
        } else {
            // Sprawdź czy metoda HTTP jest dozwolona dla tej ścieżki
            foreach (['GET', 'POST', 'PUT', 'DELETE'] as $allowedMethod) {
                if ($allowedMethod != $method && self::match($allowedMethod, $url)) {
                    // Błąd 405 - metoda niedozwolona
                    self::handleMethodNotAllowed();
                    return;
                }
            }
            
            // Błąd 404 - nie znaleziono ścieżki
            self::handleNotFound();
        }
    }
    
    /**
     * Obsługuje błąd 404 (nie znaleziono)
     * 
     * @return void
     */
    private static function handleNotFound() {
        header('HTTP/1.0 404 Not Found');
        include APP_PATH . '/views/errors/404.php';
        exit;
    }
    
    /**
     * Obsługuje błąd 405 (metoda niedozwolona)
     * 
     * @return void
     */
    private static function handleMethodNotAllowed() {
        header('HTTP/1.0 405 Method Not Allowed');
        include APP_PATH . '/views/errors/405.php';
        exit;
    }
}