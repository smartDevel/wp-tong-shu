<?php
/**
 * SQLite License Database
 */

class WPTS_DB {
    private $pdo;

    public function __construct($db_path) {
        $this->pdo = new PDO('sqlite:' . $db_path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->init_tables();
    }

    private function init_tables() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS licenses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                license_key TEXT UNIQUE NOT NULL,
                type TEXT NOT NULL DEFAULT 'single',
                status TEXT NOT NULL DEFAULT 'active',
                max_sites INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME,
                email TEXT,
                customer_name TEXT,
                notes TEXT
            );
            CREATE TABLE IF NOT EXISTS activations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                license_key TEXT NOT NULL,
                site_url TEXT NOT NULL,
                activated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(license_key, site_url)
            );
        ");
    }

    public function create_license($key, $type = 'single', $max_sites = 1, $email = '', $name = '', $expires = null) {
        $stmt = $this->pdo->prepare("INSERT INTO licenses (license_key, type, max_sites, email, customer_name, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$key, $type, $max_sites, $email, $name, $expires]);
    }

    public function get_license($key) {
        $stmt = $this->pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function get_all_licenses() {
        $stmt = $this->pdo->query("SELECT l.*, COUNT(a.id) as active_sites FROM licenses l LEFT JOIN activations a ON l.license_key = a.license_key GROUP BY l.id ORDER BY l.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activate($key, $site_url) {
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO activations (license_key, site_url) VALUES (?, ?)");
        return $stmt->execute([$key, $site_url]);
    }

    public function deactivate($key, $site_url) {
        $stmt = $this->pdo->prepare("DELETE FROM activations WHERE license_key = ? AND site_url = ?");
        return $stmt->execute([$key, $site_url]);
    }

    public function get_activations($key) {
        $stmt = $this->pdo->prepare("SELECT * FROM activations WHERE license_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update_status($key, $status) {
        $stmt = $this->pdo->prepare("UPDATE licenses SET status = ? WHERE license_key = ?");
        return $stmt->execute([$status, $key]);
    }

    public function delete_license($key) {
        $this->pdo->prepare("DELETE FROM activations WHERE license_key = ?")->execute([$key]);
        $this->pdo->prepare("DELETE FROM licenses WHERE license_key = ?")->execute([$key]);
    }

    public function generate_key() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $key = '';
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $key .= $chars[random_int(0, strlen($chars) - 1)];
            }
            if ($i < 3) $key .= '-';
        }
        return $key;
    }
}
