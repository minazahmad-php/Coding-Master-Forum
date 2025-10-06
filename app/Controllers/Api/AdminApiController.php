<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\User;
use App\Models\Forum;
use App\Models\Thread;
use App\Models\Post;

/**
 * Admin API Controller
 */
class AdminApiController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        try {
            $userModel = new User();
            $forumModel = new Forum();
            $threadModel = new Thread();
            $postModel = new Post();

            $stats = [
                'total_users' => $userModel->count(),
                'total_forums' => $forumModel->count(),
                'total_threads' => $threadModel->count(),
                'total_posts' => $postModel->count(),
                'active_users' => $userModel->getCountByStatus('active'),
                'new_users_today' => $userModel->getCountByDateRange(date('Y-m-d'), date('Y-m-d')),
                'new_threads_today' => $threadModel->getCountByDateRange(date('Y-m-d'), date('Y-m-d')),
                'new_posts_today' => $postModel->getCountByDateRange(date('Y-m-d'), date('Y-m-d'))
            ];

            $this->json($stats);
        } catch (\Exception $e) {
            $this->error('Failed to fetch dashboard statistics', 500);
        }
    }

    /**
     * Get users list
     */
    public function users()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';

            $userModel = new User();
            $users = $userModel->getByCriteria([
                'search' => $search,
                'role' => $role,
                'status' => $status
            ], $page, $perPage);

            $total = $userModel->getCountByCriteria([
                'search' => $search,
                'role' => $role,
                'status' => $status
            ]);

            $this->json([
                'users' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch users', 500);
        }
    }

    /**
     * Update user
     */
    public function updateUser($id)
    {
        try {
            $this->validateCsrf();
            
            $userModel = new User();
            $user = $userModel->find($id);
            
            if (!$user) {
                $this->error('User not found', 404);
                return;
            }

            $data = $this->sanitize($_POST);
            $allowedFields = ['username', 'email', 'display_name', 'role', 'status'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                $this->error('No valid fields to update', 400);
                return;
            }

            $userModel->update($id, $updateData);
            $this->success('User updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update user', 500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        try {
            $this->validateCsrf();
            
            $userModel = new User();
            $user = $userModel->find($id);
            
            if (!$user) {
                $this->error('User not found', 404);
                return;
            }

            $userModel->delete($id);
            $this->success('User deleted successfully');
        } catch (\Exception $e) {
            $this->error('Failed to delete user', 500);
        }
    }

    /**
     * Get forums list
     */
    public function forums()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';

            $forumModel = new Forum();
            $forums = $forumModel->getByCriteria([
                'search' => $search,
                'status' => $status
            ], $page, $perPage);

            $total = $forumModel->getCountByCriteria([
                'search' => $search,
                'status' => $status
            ]);

            $this->json([
                'forums' => $forums,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch forums', 500);
        }
    }

    /**
     * Create forum
     */
    public function createForum()
    {
        try {
            $this->validateCsrf();
            
            $data = $this->sanitize($_POST);
            $required = ['name', 'description'];
            $errors = $this->validateRequired($required, $data);
            
            if (!empty($errors)) {
                $this->error('Validation failed: ' . implode(', ', $errors), 422);
                return;
            }

            $forumModel = new Forum();
            $forumId = $forumModel->create($data);
            
            $this->success('Forum created successfully', ['forum_id' => $forumId]);
        } catch (\Exception $e) {
            $this->error('Failed to create forum', 500);
        }
    }

    /**
     * Update forum
     */
    public function updateForum($id)
    {
        try {
            $this->validateCsrf();
            
            $forumModel = new Forum();
            $forum = $forumModel->find($id);
            
            if (!$forum) {
                $this->error('Forum not found', 404);
                return;
            }

            $data = $this->sanitize($_POST);
            $allowedFields = ['name', 'description', 'status'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                $this->error('No valid fields to update', 400);
                return;
            }

            $forumModel->update($id, $updateData);
            $this->success('Forum updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update forum', 500);
        }
    }

    /**
     * Delete forum
     */
    public function deleteForum($id)
    {
        try {
            $this->validateCsrf();
            
            $forumModel = new Forum();
            $forum = $forumModel->find($id);
            
            if (!$forum) {
                $this->error('Forum not found', 404);
                return;
            }

            $forumModel->delete($id);
            $this->success('Forum deleted successfully');
        } catch (\Exception $e) {
            $this->error('Failed to delete forum', 500);
        }
    }

    /**
     * Get system settings
     */
    public function settings()
    {
        try {
            $settings = [
                'site_name' => config('app.name'),
                'site_url' => config('app.url'),
                'debug_mode' => config('app.debug'),
                'maintenance_mode' => config('app.maintenance', false),
                'registration_enabled' => config('app.registration_enabled', true),
                'email_verification' => config('app.email_verification', true)
            ];

            $this->json($settings);
        } catch (\Exception $e) {
            $this->error('Failed to fetch settings', 500);
        }
    }

    /**
     * Update system settings
     */
    public function updateSettings()
    {
        try {
            $this->validateCsrf();
            
            $data = $this->sanitize($_POST);
            $allowedSettings = [
                'site_name', 'site_url', 'debug_mode', 'maintenance_mode',
                'registration_enabled', 'email_verification'
            ];

            $updateData = array_intersect_key($data, array_flip($allowedSettings));
            
            // Update configuration
            foreach ($updateData as $key => $value) {
                // This would update the actual config files
                $this->logger->info("Setting updated: {$key} = {$value}");
            }

            $this->success('Settings updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update settings', 500);
        }
    }

    /**
     * Get analytics data
     */
    public function analytics()
    {
        try {
            $analyticsService = new \App\Services\AnalyticsService();
            $data = $analyticsService->getDashboardAnalytics();
            
            $this->json($data);
        } catch (\Exception $e) {
            $this->error('Failed to fetch analytics', 500);
        }
    }

    /**
     * Get reports
     */
    public function reports()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $status = $_GET['status'] ?? '';

            $sql = "SELECT r.*, u.username, u.display_name, 
                           CASE 
                               WHEN r.reportable_type = 'thread' THEN t.title
                               WHEN r.reportable_type = 'post' THEN SUBSTRING(p.content, 1, 100)
                               ELSE 'Unknown'
                           END as content_preview
                    FROM reports r
                    LEFT JOIN users u ON r.reported_by = u.id
                    LEFT JOIN threads t ON r.reportable_type = 'thread' AND r.reportable_id = t.id
                    LEFT JOIN posts p ON r.reportable_type = 'post' AND r.reportable_id = p.id
                    WHERE 1=1";
            
            $params = [];
            if ($status) {
                $sql .= " AND r.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = ($page - 1) * $perPage;

            $reports = $this->db->fetchAll($sql, $params);
            
            $totalSql = "SELECT COUNT(*) as total FROM reports";
            if ($status) {
                $totalSql .= " WHERE status = ?";
            }
            $total = $this->db->fetch($totalSql, $status ? [$status] : [])['total'];

            $this->json([
                'reports' => $reports,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch reports', 500);
        }
    }

    /**
     * Handle report
     */
    public function handleReport($id)
    {
        try {
            $this->validateCsrf();
            
            $action = $_POST['action'] ?? '';
            $reason = $_POST['reason'] ?? '';

            if (!in_array($action, ['dismiss', 'warn', 'ban', 'delete'])) {
                $this->error('Invalid action', 400);
                return;
            }

            $sql = "UPDATE reports SET status = ?, handled_by = ?, handled_at = NOW(), action_taken = ?, action_reason = ? WHERE id = ?";
            $this->db->query($sql, [$action, $this->getCurrentUser()['id'], $action, $reason, $id]);

            $this->success('Report handled successfully');
        } catch (\Exception $e) {
            $this->error('Failed to handle report', 500);
        }
    }
}