<?php
/**
 * app/config/database.php
 * Konfiguracja połączenia z bazą danych dla aplikacji Q-ZTWS
 */

// Parametry połączenia z bazą danych - DOSTOSUJ DO SWOJEGO ŚRODOWISKA!
define('DB_HOST', 'localhost');         // Adres serwera bazy danych
define('DB_NAME', '');          // Nazwa bazy danych
define('DB_USER', '');        // Nazwa użytkownika bazy danych
define('DB_PASS', ''); // Hasło do bazy danych - ZMIEŃ NA SILNE HASŁO!
define('DB_CHARSET', 'utf8mb4');        // Kodowanie znaków

/**
 * Klasa Database do obsługi połączenia z bazą danych
 * Wykorzystuje wzorzec Singleton
 */
class Database {
    private static $instance = null;
    private $connection;
    private $statement;
    
    /**
     * Konstruktor - prywatny, zgodnie z wzorcem Singleton
     */
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Zapisz błąd do logu zamiast wyświetlania, jeśli nie jesteśmy w trybie debugowania
            if (DEBUG) {
                throw new Exception("Błąd połączenia z bazą danych: " . $e->getMessage());
            } else {
                error_log("Błąd połączenia z bazą danych: " . $e->getMessage());
                throw new Exception("Wystąpił problem z połączeniem z bazą danych. Spróbuj ponownie później.");
            }
        }
    }
    
    /**
     * Pobiera instancję klasy Database (Singleton)
     * 
     * @return Database Instancja klasy Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Przygotowuje zapytanie SQL
     * 
     * @param string $sql Zapytanie SQL
     * @return Database Instancja klasy Database
     */
    public function query($sql) {
        $this->statement = $this->connection->prepare($sql);
        return $this;
    }
    
    /**
     * Binduje parametry do zapytania
     * 
     * @param mixed $param Parametr lub tablica parametrów
     * @param mixed $value Wartość parametru (jeśli $param jest stringiem)
     * @param int $type Typ parametru PDO (opcjonalny)
     * @return Database Instancja klasy Database
     */
    public function bind($param, $value = null, $type = null) {
        if (is_array($param)) {
            foreach ($param as $key => $val) {
                $this->bind($key, $val);
            }
            return $this;
        }
        
        if ($type === null) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->statement->bindValue($param, $value, $type);
        return $this;
    }
    
    /**
     * Wykonuje przygotowane zapytanie
     * 
     * @return bool Czy zapytanie zostało wykonane
     */
    public function execute() {
        return $this->statement->execute();
    }
    
    /**
     * Pobiera wszystkie wiersze
     * 
     * @return array Tablica wyników
     */
    public function fetchAll() {
        $this->execute();
        return $this->statement->fetchAll();
    }
    
    /**
     * Pobiera pojedynczy wiersz
     * 
     * @return array|bool Wiersz wyników lub false jeśli brak wyników
     */
    public function fetch() {
        $this->execute();
        return $this->statement->fetch();
    }
    
    /**
     * Pobiera pojedynczą wartość
     * 
     * @param int $column Indeks kolumny (domyślnie 0)
     * @return mixed Wartość
     */
    public function fetchColumn($column = 0) {
        $this->execute();
        return $this->statement->fetchColumn($column);
    }
    
    /**
     * Pobiera liczbę zmodyfikowanych wierszy
     * 
     * @return int Liczba wierszy
     */
    public function rowCount() {
        return $this->statement->rowCount();
    }
    
    /**
     * Pobiera ostatni wstawiony ID
     * 
     * @return string Ostatni ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Rozpoczyna transakcję
     * 
     * @return bool Czy transakcja została rozpoczęta
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Zatwierdza transakcję
     * 
     * @return bool Czy transakcja została zatwierdzona
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Wycofuje transakcję
     * 
     * @return bool Czy transakcja została wycofana
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    /**
     * Weryfikuje połączenie z bazą danych
     * 
     * @return bool Czy połączenie z bazą danych jest aktywne
     */
    public function isConnected() {
        return $this->connection !== null;
    }
    
    /**
     * Zwraca informacje o bazie danych
     * 
     * @return array Informacje o bazie danych
     */
    public function getDatabaseInfo() {
        return [
            'server_version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
            'client_version' => $this->connection->getAttribute(PDO::ATTR_CLIENT_VERSION),
            'connection_status' => $this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS),
            'driver_name' => $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)
        ];
    }
}
