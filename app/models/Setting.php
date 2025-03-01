<?php
/**
 * app/models/Setting.php
 * Model ustawień systemu w aplikacji Q-ZTWS
 */

class Setting {
    private $db;
    
    // Właściwości modelu
    public $id;
    public $setting_key;
    public $setting_value;
    public $description;
    public $updated_at;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Pobiera ustawienie po ID
     * 
     * @param int $id ID ustawienia
     * @return bool Czy operacja się powiodła
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM settings WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera ustawienie po kluczu
     * 
     * @param string $key Klucz ustawienia
     * @return bool Czy operacja się powiodła
     */
    public function getByKey($key) {
        $this->db->query("SELECT * FROM settings WHERE setting_key = :key");
        $this->db->bind(':key', $key);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera wartość ustawienia po kluczu
     * 
     * @param string $key Klucz ustawienia
     * @param mixed $default Domyślna wartość
     * @return mixed Wartość ustawienia lub domyślna wartość
     */
    public function getValue($key, $default = null) {
        $this->db->query("SELECT setting_value FROM settings WHERE setting_key = :key");
        $this->db->bind(':key', $key);
        
        $value = $this->db->fetchColumn();
        
        return $value !== false ? $value : $default;
    }
    
    /**
     * Pobiera wszystkie ustawienia
     * 
     * @return array Tablica ustawień
     */
    public function getAll() {
        $this->db->query("SELECT * FROM settings ORDER BY setting_key");
        return $this->db->fetchAll();
    }
    
    /**
     * Pobiera ustawienia według wzorca klucza
     * 
     * @param string $pattern Wzorzec klucza (np. 'smtp_%')
     * @return array Tablica ustawień
     */
    public function getByKeyPattern($pattern) {
        $this->db->query("SELECT * FROM settings WHERE setting_key LIKE :pattern ORDER BY setting_key");
        $this->db->bind(':pattern', $pattern);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Aktualizuje wartość ustawienia
     * 
     * @param string $key Klucz ustawienia
     * @param mixed $value Nowa wartość
     * @return bool Czy operacja się powiodła
     */
    public function update($key, $value) {
        // Sprawdź czy ustawienie istnieje
        $this->db->query("SELECT id FROM settings WHERE setting_key = :key");
        $this->db->bind(':key', $key);
        
        if ($this->db->rowCount() > 0) {
            // Aktualizuj istniejące ustawienie
            $this->db->query("UPDATE settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = :key");
        } else {
            // Dodaj nowe ustawienie
            $this->db->query("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (:key, :value, NOW())");
        }
        
        $this->db->bind(':key', $key);
        $this->db->bind(':value', $value);
        
        return $this->db->execute();
    }
    
    /**
     * Aktualizuje opis ustawienia
     * 
     * @param string $key Klucz ustawienia
     * @param string $description Nowy opis
     * @return bool Czy operacja się powiodła
     */
    public function updateDescription($key, $description) {
        $this->db->query("UPDATE settings SET description = :description WHERE setting_key = :key");
        $this->db->bind(':key', $key);
        $this->db->bind(':description', $description);
        
        return $this->db->execute();
    }
    
    /**
     * Usuwa ustawienie
     * 
     * @param string $key Klucz ustawienia
     * @return bool Czy operacja się powiodła
     */
    public function delete($key) {
        $this->db->query("DELETE FROM settings WHERE setting_key = :key");
        $this->db->bind(':key', $key);
        
        return $this->db->execute();
    }
    
    /**
     * Pobiera limit prób logowania
     * 
     * @return int Limit prób logowania
     */
    public function getLoginAttemptsLimit() {
        $value = $this->getValue('max_login_attempts', 5);
        return (int) $value;
    }
    
    /**
     * Pobiera czas blokady logowania (w minutach)
     * 
     * @return int Czas blokady logowania
     */
    public function getLoginLockoutTime() {
        $value = $this->getValue('login_lockout_time', 15);
        return (int) $value;
    }
    
    /**
     * Pobiera ustawienia SMTP
     * 
     * @return array Ustawienia SMTP
     */
    public function getSmtpSettings() {
        $settings = $this->getByKeyPattern('smtp_%');
        $smtp = [];
        
        foreach ($settings as $setting) {
            $key = str_replace('smtp_', '', $setting['setting_key']);
            $smtp[$key] = $setting['setting_value'];
        }
        
        return $smtp;
    }
    
    /**
     * Aktualizuje ustawienia SMTP
     * 
     * @param array $settings Ustawienia SMTP
     * @return bool Czy operacja się powiodła
     */
    public function updateSmtpSettings($settings) {
        $success = true;
        
        foreach ($settings as $key => $value) {
            $fullKey = 'smtp_' . $key;
            if (!$this->update($fullKey, $value)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Przypisuje właściwości z wiersza bazy danych
     * 
     * @param array $row Wiersz z bazy danych
     * @return void
     */
    private function mapProperties($row) {
        $this->id = $row['id'];
        $this->setting_key = $row['setting_key'];
        $this->setting_value = $row['setting_value'];
        $this->description = $row['description'];
        $this->updated_at = $row['updated_at'];
    }
}
