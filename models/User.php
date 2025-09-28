<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use Core\Logger;

class User
{
    private Database $db;
    private Logger $logger;
    private array $attributes = [];
    private bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->attributes = $attributes;
        $this->exists = !empty($attributes['id']);
    }

    // Magic methods for attribute access
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    // Static methods for finding users
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        return $user ? new self($user) : null;
    }

    public static function findByEmail(string $email): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        return $user ? new self($user) : null;
    }

    public static function findByUsername(string $username): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        return $user ? new self($user) : null;
    }

    public static function all(int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $users = $stmt->fetchAll();
        
        return array_map(fn($user) => new self($user), $users);
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        
        return (int) $result['count'];
    }

    // Instance methods
    public function save(): bool
    {
        try {
            if ($this->exists) {
                return $this->update();
            } else {
                return $this->create();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to save user', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function create(): bool
    {
        $this->attributes['created_at'] = date('Y-m-d H:i:s');
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        $fields = array_keys($this->attributes);
        $placeholders = array_fill(0, count($fields), '?');
        $values = array_values($this->attributes);
        
        $sql = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            $this->id = $this->db->lastInsertId();
            $this->exists = true;
        }
        
        return $result;
    }

    private function update(): bool
    {
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        $fields = array_keys($this->attributes);
        $fields = array_filter($fields, fn($field) => $field !== 'id');
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $values = array_values(array_filter($this->attributes, fn($key) => $key !== 'id', ARRAY_FILTER_USE_KEY));
        $values[] = $this->id;
        
        $sql = "UPDATE users SET $setClause WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->exists = false;
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete user', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // User-specific methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isVerified(): bool
    {
        return !empty($this->email_verified_at);
    }

    public function isPremium(): bool
    {
        return (bool) $this->is_premium;
    }

    public function hasTwoFactor(): bool
    {
        return (bool) $this->two_factor_enabled;
    }

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return UPLOADS_PATH . '/avatars/' . $this->avatar;
        }
        
        return '/assets/images/default-avatar.png';
    }

    public function incrementPostCount(): bool
    {
        return $this->incrementField('posts_count');
    }

    public function incrementCommentCount(): bool
    {
        return $this->incrementField('comments_count');
    }

    public function incrementReputation(int $amount = 1): bool
    {
        return $this->incrementField('reputation', $amount);
    }

    private function incrementField(string $field, int $amount = 1): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET $field = $field + ? WHERE id = ?");
            $result = $stmt->execute([$amount, $this->id]);
            
            if ($result) {
                $this->attributes[$field] = ($this->attributes[$field] ?? 0) + $amount;
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to increment user $field", [
                'user_id' => $this->id,
                'field' => $field,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function updateLastLogin(): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$this->id]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update last login', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function verifyEmail(): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET email_verified_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->email_verified_at = date('Y-m-d H:i:s');
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to verify email', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function ban(string $reason = ''): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->status = 'banned';
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to ban user', [
                'user_id' => $this->id,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function unban(): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->status = 'active';
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to unban user', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // Relationship methods
    public function posts(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM posts 
            WHERE user_id = ? AND status = 'published' 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$this->id, $limit, $offset]);
        $posts = $stmt->fetchAll();
        
        return array_map(fn($post) => new Post($post), $posts);
    }

    public function comments(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM comments 
            WHERE user_id = ? AND is_approved = 1 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$this->id, $limit, $offset]);
        $comments = $stmt->fetchAll();
        
        return array_map(fn($comment) => new Comment($comment), $comments);
    }

    // Convert to array
    public function toArray(): array
    {
        return $this->attributes;
    }

    // Convert to JSON
    public function toJson(): string
    {
        return json_encode($this->attributes);
    }
}