<?php
declare(strict_types=1);

/**
 * Modern Forum - Category Model
 * Handles category-related database operations
 */

namespace Models;

use Core\Database;
use Core\Logger;

class Category
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

    // Static methods for finding categories
    public static function find(int $id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        
        return $category ? new self($category) : null;
    }

    public static function findBySlug(string $slug): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $category = $stmt->fetch();
        
        return $category ? new self($category) : null;
    }

    public static function all(int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, 
                   COUNT(p.id) as posts_count,
                   COUNT(CASE WHEN p.created_at > datetime('now', '-7 days') THEN 1 END) as recent_posts_count
            FROM categories c
            LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $categories = $stmt->fetchAll();
        
        return array_map(fn($category) => new self($category), $categories);
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
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
            $this->logger->error('Failed to save category', [
                'category_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function create(): bool
    {
        $this->attributes['created_at'] = date('Y-m-d H:i:s');
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug($this->name);
        }
        
        $fields = array_keys($this->attributes);
        $placeholders = array_fill(0, count($fields), '?');
        $values = array_values($this->attributes);
        
        $sql = "INSERT INTO categories (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
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
        
        $sql = "UPDATE categories SET $setClause WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        try {
            // Check if category has posts
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ?");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                throw new \Exception('Cannot delete category with posts');
            }
            
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->exists = false;
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete category', [
                'category_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // Category-specific methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getUrl(): string
    {
        return '/forum/' . $this->slug;
    }

    public function getEditUrl(): string
    {
        return '/admin/categories/' . $this->id . '/edit';
    }

    public function getPostsCount(): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ? AND status = 'published'");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch();
            
            return (int) $result['count'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get posts count', [
                'category_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function getRecentPostsCount(): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM posts 
                WHERE category_id = ? AND status = 'published' AND created_at > datetime('now', '-7 days')
            ");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch();
            
            return (int) $result['count'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get recent posts count', [
                'category_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function getLatestPost(): ?Post
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM posts 
                WHERE category_id = ? AND status = 'published' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$this->id]);
            $post = $stmt->fetch();
            
            return $post ? new Post($post) : null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get latest post', [
                'category_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getPosts(int $limit = 20, int $offset = 0): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM posts 
                WHERE category_id = ? AND status = 'published' 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$this->id, $limit, $offset]);
            $posts = $stmt->fetchAll();
            
            return array_map(fn($post) => new Post($post), $posts);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get posts', [
                'category_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function incrementPostsCount(): bool
    {
        return $this->incrementField('posts_count');
    }

    public function decrementPostsCount(): bool
    {
        return $this->incrementField('posts_count', -1);
    }

    private function incrementField(string $field, int $amount = 1): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE categories SET $field = $field + ? WHERE id = ?");
            $result = $stmt->execute([$amount, $this->id]);
            
            if ($result) {
                $this->attributes[$field] = ($this->attributes[$field] ?? 0) + $amount;
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to increment category $field", [
                'category_id' => $this->id,
                'field' => $field,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch() !== false;
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