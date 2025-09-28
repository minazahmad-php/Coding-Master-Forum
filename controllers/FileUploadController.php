<?php
declare(strict_types=1);

/**
 * Modern Forum - File Upload Controller
 * Handles file upload requests and management
 */

namespace Controllers;

use Core\Controller;
use Core\View;
use Services\FileUploadService;
use Core\Auth;

class FileUploadController extends Controller
{
    private FileUploadService $fileUploadService;
    private Auth $auth;

    public function __construct()
    {
        parent::__construct();
        $this->fileUploadService = new FileUploadService();
        $this->auth = new Auth();
    }

    public function upload(): void
    {
        if (!$this->auth->isLoggedIn()) {
            View::json([
                'success' => false,
                'error' => 'Authentication required'
            ], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json([
                'success' => false,
                'error' => 'Only POST method allowed'
            ], 405);
            return;
        }

        $category = $_POST['category'] ?? 'general';
        $options = [
            'generate_thumbnail' => ($_POST['generate_thumbnail'] ?? 'true') === 'true',
            'resize_image' => ($_POST['resize_image'] ?? 'true') === 'true'
        ];

        if (isset($_FILES['file'])) {
            // Single file upload
            $result = $this->fileUploadService->uploadFile($_FILES['file'], $category, $options);
        } elseif (isset($_FILES['files'])) {
            // Multiple file upload
            $result = $this->fileUploadService->uploadMultipleFiles($_FILES['files'], $category, $options);
        } else {
            View::json([
                'success' => false,
                'error' => 'No files uploaded'
            ], 400);
            return;
        }

        View::json($result);
    }

    public function delete(int $fileId): void
    {
        if (!$this->auth->isLoggedIn()) {
            View::json([
                'success' => false,
                'error' => 'Authentication required'
            ], 401);
            return;
        }

        $success = $this->fileUploadService->deleteFile($fileId);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'File deleted successfully' : 'Failed to delete file'
        ]);
    }

    public function info(int $fileId): void
    {
        $fileInfo = $this->fileUploadService->getFileInfo($fileId);
        
        if (!$fileInfo) {
            View::json([
                'success' => false,
                'error' => 'File not found'
            ], 404);
            return;
        }

        View::json([
            'success' => true,
            'file' => $fileInfo
        ]);
    }

    public function userFiles(): void
    {
        if (!$this->auth->isLoggedIn()) {
            View::json([
                'success' => false,
                'error' => 'Authentication required'
            ], 401);
            return;
        }

        $user = $this->auth->getCurrentUser();
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $files = $this->fileUploadService->getUserFiles($user['id'], $limit, $offset);

        View::json([
            'success' => true,
            'files' => $files,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => count($files) === $limit
            ]
        ]);
    }

    public function uploadForm(): void
    {
        if (!$this->auth->isLoggedIn()) {
            View::redirect('/login');
            return;
        }

        $data = [
            'title' => 'File Upload',
            'categories' => [
                'image' => 'Images (JPG, PNG, GIF, WebP)',
                'document' => 'Documents (PDF, DOC, TXT)',
                'video' => 'Videos (MP4, WebM, OGG)',
                'audio' => 'Audio (MP3, WAV, OGG)',
                'archive' => 'Archives (ZIP, RAR, 7Z)',
                'code' => 'Code Files (JS, PHP, PY, etc.)'
            ]
        ];

        View::render('upload/form', $data);
    }

    public function gallery(): void
    {
        if (!$this->auth->isLoggedIn()) {
            View::redirect('/login');
            return;
        }

        $user = $this->auth->getCurrentUser();
        $category = $_GET['category'] ?? 'image';
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        $files = $this->fileUploadService->getUserFiles($user['id'], $limit, $offset);

        $data = [
            'title' => 'File Gallery',
            'files' => $files,
            'category' => $category,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => count($files) === $limit
            ]
        ];

        View::render('upload/gallery', $data);
    }

    public function serve(string $category, string $filename): void
    {
        // Security: prevent directory traversal
        $filename = basename($filename);
        $category = preg_replace('/[^a-zA-Z0-9_-]/', '', $category);

        $filePath = UPLOAD_PATH . '/' . $category . '/' . $filename;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'File not found';
            return;
        }

        // Get file info
        $mimeType = mime_content_type($filePath);
        $fileSize = filesize($filePath);

        // Set headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

        // Output file
        readfile($filePath);
    }

    public function thumbnail(string $filename): void
    {
        // Security: prevent directory traversal
        $filename = basename($filename);

        $thumbnailPath = TEMP_PATH . '/thumb_' . $filename;

        if (!file_exists($thumbnailPath)) {
            http_response_code(404);
            echo 'Thumbnail not found';
            return;
        }

        $mimeType = mime_content_type($thumbnailPath);
        $fileSize = filesize($thumbnailPath);

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: public, max-age=31536000');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

        readfile($thumbnailPath);
    }

    public function download(int $fileId): void
    {
        $fileInfo = $this->fileUploadService->getFileInfo($fileId);
        
        if (!$fileInfo) {
            http_response_code(404);
            echo 'File not found';
            return;
        }

        // Check permissions
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo 'Authentication required';
            return;
        }

        // For now, allow all logged-in users to download
        // In production, you might want to check specific permissions

        $filePath = UPLOAD_PATH . '/' . $fileInfo['category'] . '/' . $fileInfo['filename'];

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'File not found';
            return;
        }

        // Set download headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileInfo['original_name'] . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        readfile($filePath);
    }

    public function getUploadStats(): void
    {
        if (!$this->auth->isLoggedIn()) {
            View::json([
                'success' => false,
                'error' => 'Authentication required'
            ], 401);
            return;
        }

        $user = $this->auth->getCurrentUser();
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    category,
                    COUNT(*) as count,
                    SUM(file_size) as total_size
                FROM file_uploads 
                WHERE user_id = ? 
                GROUP BY category
            ");
            
            $stmt->execute([$user['id']]);
            $stats = $stmt->fetchAll();

            View::json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            View::json([
                'success' => false,
                'error' => 'Failed to get upload stats'
            ], 500);
        }
    }
}