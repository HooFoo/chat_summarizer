<?php

require_once 'config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            log_message("Database connection failed: " . $e->getMessage());
            die("Database connection failed.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function saveMessage($data) {
        $sql = "INSERT INTO messages (chat_id, message_id, user_id, username, first_name, last_name, text, reply_to_message_id, media_type, timestamp) 
                VALUES (:chat_id, :message_id, :user_id, :username, :first_name, :last_name, :text, :reply_to_message_id, :media_type, :timestamp)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function getMessagesForPeriod($chat_id, $start_timestamp, $end_timestamp) {
        $sql = "SELECT * FROM messages 
                WHERE chat_id = :chat_id 
                AND timestamp BETWEEN :start_timestamp AND :end_timestamp 
                ORDER BY timestamp ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'chat_id' => $chat_id,
            'start_timestamp' => $start_timestamp,
            'end_timestamp' => $end_timestamp
        ]);
        return $stmt->fetchAll();
    }

    public function updateChat($chat_id, $title) {
        $sql = "INSERT INTO chats (chat_id, title, last_active) 
                VALUES (:chat_id, :title, :last_active) 
                ON DUPLICATE KEY UPDATE title = :title, last_active = :last_active";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'chat_id' => $chat_id,
            'title' => $title,
            'last_active' => time()
        ]);
    }
}
