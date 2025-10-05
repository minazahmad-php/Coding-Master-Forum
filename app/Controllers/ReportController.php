<?php

namespace App\Controllers;

/**
 * Report Controller
 * Handles content reporting
 */
class ReportController extends BaseController
{
    /**
     * Create report
     */
    public function create()
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $type = $_POST['type'] ?? '';
        $targetId = (int)($_POST['target_id'] ?? 0);
        $reason = $this->sanitize($_POST['reason'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        
        $errors = $this->validateRequired(['type', 'target_id', 'reason'], $_POST);
        
        if (empty($errors)) {
            $reportData = [
                'reporter_id' => $this->getCurrentUser()['id'],
                'reason' => $reason,
                'description' => $description,
                'status' => 'pending'
            ];
            
            // Set target based on type
            switch ($type) {
                case 'user':
                    $reportData['reported_user_id'] = $targetId;
                    break;
                case 'post':
                    $reportData['reported_post_id'] = $targetId;
                    break;
                case 'thread':
                    $reportData['reported_thread_id'] = $targetId;
                    break;
            }
            
            $result = $this->db->insert('reports', $reportData);
            
            if ($result) {
                $this->success('Report submitted successfully!');
            } else {
                $this->error('Failed to submit report', 500);
            }
        } else {
            $this->error(implode(', ', $errors), 400);
        }
    }

    /**
     * View report (moderator only)
     */
    public function view($id)
    {
        $this->requireModerator();
        
        $sql = "SELECT r.*, u.username as reporter_name 
                FROM reports r 
                LEFT JOIN users u ON r.reporter_id = u.id 
                WHERE r.id = ?";
        
        $report = $this->db->fetch($sql, [$id]);
        
        if (!$report) {
            $this->view->error(404, 'Report not found');
        }
        
        $data = [
            'title' => 'Report #' . $id,
            'report' => $report
        ];
        
        echo $this->view->render('admin/review_report', $data);
    }
}