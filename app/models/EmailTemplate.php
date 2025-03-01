<?php
/**
 * app/models/EmailTemplate.php
 * Model szablonu e-mail w aplikacji Q-ZTWS
 */

class EmailTemplate {
    private $db;
    
    // Właściwości modelu
    public $id;
    public $template_key;
    public $subject_pl;
    public $subject_en;
    public $body_pl;
    public $body_en;
    public $updated_at;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Pobiera szablon e-mail po ID
     * 
     * @param int $id ID szablonu
     * @return bool Czy operacja się powiodła
     */
    public function getById($id) {
        $this->db->query("SELECT * FROM email_templates WHERE id = :id");
        $this->db->bind(':id', $id);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera szablon e-mail po kluczu
     * 
     * @param string $key Klucz szablonu
     * @return bool Czy operacja się powiodła
     */
    public function getByKey($key) {
        $this->db->query("SELECT * FROM email_templates WHERE template_key = :key");
        $this->db->bind(':key', $key);
        
        $row = $this->db->fetch();
        
        if ($row) {
            $this->mapProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera wszystkie szablony e-mail
     * 
     * @return array Tablica szablonów e-mail
     */
    public function getAll() {
        $this->db->query("SELECT * FROM email_templates ORDER BY template_key");
        return $this->db->fetchAll();
    }
    
    /**
     * Aktualizuje szablon e-mail
     * 
     * @return bool Czy operacja się powiodła
     */
    public function update() {
        $this->db->query("UPDATE email_templates SET 
                          subject_pl = :subject_pl, 
                          subject_en = :subject_en, 
                          body_pl = :body_pl, 
                          body_en = :body_en, 
                          updated_at = NOW() 
                          WHERE id = :id");
        
        $this->db->bind(':subject_pl', $this->subject_pl);
        $this->db->bind(':subject_en', $this->subject_en);
        $this->db->bind(':body_pl', $this->body_pl);
        $this->db->bind(':body_en', $this->body_en);
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Tworzy nowy szablon e-mail
     * 
     * @return bool Czy operacja się powiodła
     */
    public function create() {
        $this->db->query("INSERT INTO email_templates (template_key, subject_pl, subject_en, body_pl, body_en) 
                          VALUES (:template_key, :subject_pl, :subject_en, :body_pl, :body_en)");
        
        $this->db->bind(':template_key', $this->template_key);
        $this->db->bind(':subject_pl', $this->subject_pl);
        $this->db->bind(':subject_en', $this->subject_en);
        $this->db->bind(':body_pl', $this->body_pl);
        $this->db->bind(':body_en', $this->body_en);
        
        if ($this->db->execute()) {
            $this->id = $this->db->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Usuwa szablon e-mail
     * 
     * @return bool Czy operacja się powiodła
     */
    public function delete() {
        $this->db->query("DELETE FROM email_templates WHERE id = :id");
        $this->db->bind(':id', $this->id);
        
        return $this->db->execute();
    }
    
    /**
     * Sprawdza czy szablon o podanym kluczu istnieje
     * 
     * @param string $key Klucz szablonu
     * @return bool Czy szablon istnieje
     */
    public function keyExists($key) {
        $this->db->query("SELECT COUNT(*) FROM email_templates WHERE template_key = :key");
        $this->db->bind(':key', $key);
        
        return $this->db->fetchColumn() > 0;
    }
    
    /**
     * Pobiera treść szablonu w określonym języku
     * 
     * @param string $key Klucz szablonu
     * @param string $lang Kod języka (pl lub en)
     * @return array|null Tablica z tematem i treścią lub null
     */
    public function getTemplate($key, $lang = 'pl') {
        if (!$this->getByKey($key)) {
            return null;
        }
        
        if ($lang === 'en') {
            return [
                'subject' => $this->subject_en,
                'body' => $this->body_en
            ];
        } else {
            return [
                'subject' => $this->subject_pl,
                'body' => $this->body_pl
            ];
        }
    }
    
    /**
     * Przygotowuje treść e-maila z podstawieniem zmiennych
     * 
     * @param string $key Klucz szablonu
     * @param array $data Dane do podstawienia
     * @param string $lang Kod języka (pl lub en)
     * @return array|null Tablica z tematem i treścią lub null
     */
    public function prepareEmail($key, $data = [], $lang = 'pl') {
        $template = $this->getTemplate($key, $lang);
        
        if (!$template) {
            return null;
        }
        
        $subject = $template['subject'];
        $body = $template['body'];
        
        // Podstawienie zmiennych
        foreach ($data as $varName => $varValue) {
            $subject = str_replace('{{' . $varName . '}}', $varValue, $subject);
            $body = str_replace('{{' . $varName . '}}', $varValue, $body);
        }
        
        return [
            'subject' => $subject,
            'body' => $body
        ];
    }
    
    /**
     * Przypisuje właściwości z wiersza bazy danych
     * 
     * @param array $row Wiersz z bazy danych
     * @return void
     */
    private function mapProperties($row) {
        $this->id = $row['id'];
        $this->template_key = $row['template_key'];
        $this->subject_pl = $row['subject_pl'];
        $this->subject_en = $row['subject_en'];
        $this->body_pl = $row['body_pl'];
        $this->body_en = $row['body_en'];
        $this->updated_at = $row['updated_at'];
    }
}
