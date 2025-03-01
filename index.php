<?php
/**
 * index.php
 * Główny plik wejściowy aplikacji Q-ZTWS
 */

// Czas rozpoczęcia dla obliczenia czasu wykonania skryptu
$startTime = microtime(true);

// Definiowanie ścieżki do głównego katalogu aplikacji
define('ROOT_DIR', __DIR__);

// Ładowanie konfiguracji
require_once 'app/config/config.php';

// Obsługa błędów
if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// UWAGA: Usuwamy session_start(), ponieważ jest już w config.php

// Ładowanie pliku routera
require_once APP_PATH . '/helpers/Router.php';

// UWAGA: Usuwamy sprawdzanie instalacji, ponieważ nie mamy InstallController

// Obsługa żądań AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: application/json');
    
    // Pobieranie kontrolera i akcji z parametrów
    $controller = isset($_GET['controller']) ? $_GET['controller'] : '';
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if (empty($controller) || empty($action)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing controller or action']);
        exit;
    }
    
    // Tworzenie nazwy klasy kontrolera
    $controllerName = ucfirst($controller) . 'Controller';
    $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
    
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        $controllerObj = new $controllerName();
        
        if (method_exists($controllerObj, $action)) {
            call_user_func([$controllerObj, $action]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Action not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Controller not found']);
    }
    
    exit;
}

// Ładowanie funkcji pomocniczych
$helperFiles = glob(APP_PATH . '/helpers/*.php');
if ($helperFiles) {
    foreach ($helperFiles as $file) {
        if (basename($file) !== 'Router.php') {
            require_once $file;
        }
    }
}

// Uruchomienie routera dla normalnych żądań
try {
    Router::run();
} catch (Exception $e) {
    if (DEBUG) {
        // Wyświetl szczegóły błędu w trybie debugowania
        echo '<h1>Wystąpił błąd</h1>';
        echo '<p><strong>Wiadomość:</strong> ' . $e->getMessage() . '</p>';
        echo '<p><strong>Plik:</strong> ' . $e->getFile() . '</p>';
        echo '<p><strong>Linia:</strong> ' . $e->getLine() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        // W trybie produkcyjnym przekieruj do strony błędu
        header('HTTP/1.1 500 Internal Server Error');
        if (file_exists(APP_PATH . '/views/errors/500.php')) {
            include APP_PATH . '/views/errors/500.php';
        } else {
            echo '<h1>500 - Internal Server Error</h1>';
        }
    }
    
    // Logowanie błędu
    error_log('Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
}

// Obliczenie i zapisanie czasu wykonania skryptu (dla celów debugowania)
if (DEBUG) {
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // w milisekundach
    error_log('Execution time: ' . round($executionTime, 2) . 'ms');
}