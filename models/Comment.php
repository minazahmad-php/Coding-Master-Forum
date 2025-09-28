<?php
declare(strict_types=1);

/**
 * Modern Forum - Comment Model
 * Handles comment-related database operations
 */

namespace Models;

use Core\Database;
use Core\Logger;

class Comment
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

    // Static methods for finding comments
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM comments WHERE id = ?");
        $stmt->execute([$id]);
        $comment = $stmt->fetch();
        
        return $comment ? new self($comment) : null;
    }

    public static function byPost(int $postId, int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.avatar, u.first_name, u.last_name
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ? AND c.is_approved = 1
            ORDER BY c.created_at ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$postId, $limit, $offset]);
        $comments = $stmt->fetchAll();
        
        return array_map(fn($comment) => new self($comment), $comments);
    }

    public static function byUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, p.title as post_title, p.slug as post_slug
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            WHERE c.user_id = ? AND c.is_approved = 1
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        $comments = $stmt->fetchAll();
        
        return array_map(fn($comment) => new self($comment), $comments);
    }

    public static function pending(int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.avatar, p.title as post_title
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN posts p ON c.post_id = p.id
            WHERE c.is_approved = 0
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $comments = $stmt->fetchAll();
        
        return array_map(fn($comment) => new self($comment), $comments);
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) as count FROM comments WHERE is_approved = 1");
        $result = $stmt->fetch();
        
        return (int) $result['count'];
    }

    public static function countByPost(int $postId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ? AND is_approved = 1");
        $stmt->execute([$postId]);
        $result = $stmt->fetch();
        
        return (int) $result['count'];
    }

    public static function countByUser(int $userId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = ? AND is_approved = 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return (int) $result['count'];
    }

    public static function countPending(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) as count FROM comments WHERE is_approved = 0");
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
            $this->logger->error('Failed to save comment', [
                'comment_id' => $this->id,
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
        
        $sql = "INSERT INTO comments (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
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
        
        $sql = "UPDATE comments SET $setClause WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->exists = false;
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete comment', [
                'comment_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // Comment-specific methods
    public function isApproved(): bool
    {
        return (bool) $this->is_approved;
    }

    public function isPending(): bool
    {
        return !$this->isApproved();
    }

    public function isEdited(): bool
    {
        return !empty($this->edited_at);
    }

    public function getAuthor(): ?User
    {
        return User::find($this->user_id);
    }

    public function getPost(): ?Post
    {
        return Post::find($this->post_id);
    }

    public function getUrl(): string
    {
        $post = $this->getPost();
        if ($post) {
            return $post->getUrl() . '#comment-' . $this->id;
        }
        
        return '#';
    }

    public function getEditUrl(): string
    {
        return '/comment/' . $this->id . '/edit';
    }

    public function getDeleteUrl(): string
    {
        return '/comment/' . $this->id . '/delete';
    }

    public function getApproveUrl(): string
    {
        return '/admin/comments/' . $this->id . '/approve';
    }

    public function getRejectUrl(): string
    {
        return '/admin/comments/' . $this->id . '/reject';
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getExcerpt(int $length = 150): string
    {
        $content = strip_tags($this->content);
        if (strlen($content) <= $length) {
            return $content;
        }
        
        return substr($content, 0, $length) . '...';
    }

    public function incrementLikes(): bool
    {
        return $this->incrementField('likes_count');
    }

    public function decrementLikes(): bool
    {
        return $this->incrementField('likes_count', -1);
    }

    public function incrementReplies(): bool
    {
        return $this->incrementField('replies_count');
    }

    public function decrementReplies(): bool
    {
        return $this->incrementField('replies_count', -1);
    }

    private function incrementField(string $field, int $amount = 1): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE comments SET $field = $field + ? WHERE id = ?");
            $result = $stmt->execute([$amount, $this->id]);
            
            if ($result) {
                $this->attributes[$field] = ($this->attributes[$field] ?? 0) + $amount;
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to increment comment $field", [
                'comment_id' => $this->id,
                'field' => $field,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function approve(): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE comments SET is_approved = 1, approved_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->is_approved = 1;
                $this->approved_at = date('Y-m-d H:i:s');
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to approve comment', [
                'comment_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function reject(): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE comments SET is_approved = 0, rejected_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->is_approved = 0;
                $this->rejected_at = date('Y-m-d H:i:s');
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to reject comment', [
                'comment_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function markAsEdited(): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE comments SET edited_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->edited_at = date('Y-m-d H:i:s');
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark comment as edited', [
                'comment_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function report(string $reason, int $reporterId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO reports (reportable_type, reportable_id, reporter_id, reason, status, created_at)
                VALUES ('comment', ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            
            return $stmt->execute([$this->id, $reporterId, $reason]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to report comment', [
                'comment_id' => $this->id,
                'reporter_id' => $reporterId,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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