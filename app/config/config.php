<?php
// Podstawowe ustawienia aplikacji
define('APP_NAME', 'Q-ZTWS');
define('APP_URL', 'https://quiz.ztws.pl'); 
define('ROOT_PATH', dirname(__DIR__, 2)); // Ścieżka do katalogu głównego
define('APP_PATH', ROOT_PATH . '/app');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('EXPORTS_PATH', ROOT_PATH . '/exports');
define('LANG_PATH', ROOT_PATH . '/lang');

// Ustawienia środowiska
define('ENV', 'development'); // dla testów używamy 'development'
define('DEBUG', true); // włącz debugowanie

// Konfiguracja strefy czasowej
date_default_timezone_set('Europe/Warsaw');

// Ustawienia sesji
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_name('qztws_session');

// Obsługa błędów
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
}


// Automatyczne ładowanie klas
spl_autoload_register(function($className) {
    $prefix = ''; // Ewentualnie możesz tu dodać prefix namespace'u
    $baseDir = APP_PATH . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $className, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($className, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Ładowanie konfiguracji bazy danych i e-mail
require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/config/email.php';

/**
 * Funkcje pomocnicze
 */

/**
 * Bezpieczne przekierowanie
 * 
 * @param string $url URL do przekierowania
 * @return void
 */
function redirect($url = '') {
    if (empty($url)) {
        $url = APP_URL;
    } elseif (strpos($url, 'http') !== 0) {
        $url = APP_URL . '/' . ltrim($url, '/');
    }
    
    header("Location: {$url}");
    exit;
}

/**
 * Pobieranie tłumaczenia
 * 
 * @param string $key Klucz tłumaczenia
 * @param array $params Parametry do podstawienia
 * @return string Przetłumaczony tekst
 */
function __($key, $params = []) {
    global $lang, $translations;
    
    if (!isset($translations[$key])) {
        return $key;
    }
    
    $text = $translations[$key];
    
    if (!empty($params)) {
        foreach ($params as $param => $value) {
            $text = str_replace('{{' . $param . '}}', $value, $text);
        }
    }
    
    return $text;
}

/**
 * Tworzenie tokena CSRF
 * 
 * @return string Token CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Sprawdzanie tokena CSRF
 * 
 * @param string $token Token CSRF z formularza
 * @return bool Czy token jest prawidłowy
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanityzacja danych wejściowych
 * 
 * @param mixed $data Dane do sanityzacji
 * @return mixed Sanityzowane dane
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    
    if (is_string($data)) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Generowanie losowego ciągu znaków
 * 
 * @param int $length Długość ciągu
 * @return string Losowy ciąg znaków
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// Inicjalizacja sesji
session_start();

// Ustawienie języka
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'pl'; // Domyślny język
}
$lang = $_SESSION['lang'];

// Ładowanie pliku z tłumaczeniami
$translations = [];
$langFile = LANG_PATH . "/{$lang}.php";

if (file_exists($langFile)) {
    $translations = require_once $langFile;
}
