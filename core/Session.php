<?php
declare(strict_types=1);

namespace Core;

use Core\Database;

class Session
{
    private static ?Session $instance = null;
    private Database $db;
    private bool $started = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->start();
    }

    public static function getInstance(): Session
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        // Configure session settings
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Strict');

        // Set session name
        session_name('FORUM_SESSION');

        // Start session
        session_start();
        $this->started = true;

        // Regenerate session ID periodically for security
        $this->regenerateIdIfNeeded();
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
        $this->updateSessionInDatabase();
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
        $this->updateSessionInDatabase();
    }

    public function destroy(): void
    {
        $this->deleteSessionFromDatabase();
        session_destroy();
        $this->started = false;
    }

    public function regenerateId(): void
    {
        session_regenerate_id(true);
        $this->updateSessionInDatabase();
    }

    public function getId(): string
    {
        return session_id();
    }

    public function getUserId(): ?int
    {
        return $this->get('user_id');
    }

    public function setUserId(int $userId): void
    {
        $this->set('user_id', $userId);
    }

    public function isLoggedIn(): bool
    {
        return $this->has('user_id') && $this->get('user_id') !== null;
    }

    public function logout(): void
    {
        $this->remove('user_id');
        $this->remove('username');
        $this->remove('email');
        $this->remove('role');
        $this->regenerateId();
    }

    public function flash(string $key, $value = null)
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
        } else {
            $flashValue = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $flashValue;
        }
    }

    public function getFlash(string $key, $default = null)
    {
        return $_SESSION['_flash'][$key] ?? $default;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function clearFlash(): void
    {
        unset($_SESSION['_flash']);
    }

    private function regenerateIdIfNeeded(): void
    {
        $lastRegeneration = $this->get('_last_regeneration', 0);
        $now = time();
        
        // Regenerate every 30 minutes
        if ($now - $lastRegeneration > 1800) {
            $this->regenerateId();
            $this->set('_last_regeneration', $now);
        }
    }

    private function updateSessionInDatabase(): void
    {
        try {
            $sessionId = $this->getId();
            $userId = $this->getUserId();
            $payload = json_encode($_SESSION);
            $lastActivity = time();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity, created_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");

            $stmt->execute([
                $sessionId,
                $userId,
                $ipAddress,
                $userAgent,
                $payload,
                $lastActivity
            ]);
        } catch (\Exception $e) {
            error_log("Failed to update session in database: " . $e->getMessage());
        }
    }

    private function deleteSessionFromDatabase(): void
    {
        try {
            $sessionId = $this->getId();
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
        } catch (\Exception $e) {
            error_log("Failed to delete session from database: " . $e->getMessage());
        }
    }

    public function cleanupExpiredSessions(): void
    {
        try {
            $expiredTime = time() - (24 * 60 * 60); // 24 hours
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE last_activity < ?");
            $stmt->execute([$expiredTime]);
        } catch (\Exception $e) {
            error_log("Failed to cleanup expired sessions: " . $e->getMessage());
        }
    }

    public function getSessionData(): array
    {
        return $_SESSION;
    }

    public function setSessionData(array $data): void
    {
        $_SESSION = array_merge($_SESSION, $data);
        $this->updateSessionInDatabase();
    }

    public function isValid(): bool
    {
        if (!$this->started) {
            return false;
        }

        $sessionId = $this->getId();
        if (empty($sessionId)) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("SELECT id FROM sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            return $stmt->fetch() !== false;
        } catch (\Exception $e) {
            error_log("Failed to validate session: " . $e->getMessage());
            return false;
        }
    }

    public function getSessionInfo(): array
    {
        try {
            $sessionId = $this->getId();
            $stmt = $this->db->prepare("
                SELECT s.*, u.username, u.email 
                FROM sessions s 
                LEFT JOIN users u ON s.user_id = u.id 
                WHERE s.id = ?
            ");
            $stmt->execute([$sessionId]);
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            error_log("Failed to get session info: " . $e->getMessage());
            return [];
        }
    }
}