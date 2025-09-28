<?php
declare(strict_types=1);

/**
 * Modern Forum - File Upload Service
 * Handles file uploads with security and validation
 */

namespace Services;

use Core\Database;
use Core\Logger;
use Core\Auth;

class FileUploadService
{
    private Database $db;
    private Logger $logger;
    private Auth $auth;
    private array $allowedTypes;
    private array $allowedExtensions;
    private int $maxFileSize;
    private string $uploadPath;
    private string $tempPath;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->auth = new Auth();
        
        $this->loadConfiguration();
        $this->createDirectories();
    }

    private function loadConfiguration(): void
    {
        $this->allowedTypes = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'],
            'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/avi'],
            'audio' => ['audio/mp3', 'audio/wav', 'audio/ogg', 'audio/mpeg'],
            'archive' => ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'],
            'code' => ['text/plain', 'application/json', 'application/xml', 'text/css', 'text/javascript', 'application/javascript']
        ];

        $this->allowedExtensions = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
            'video' => ['mp4', 'webm', 'ogg', 'avi', 'mov'],
            'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'code' => ['txt', 'json', 'xml', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c', 'html', 'htm']
        ];

        $this->maxFileSize = FILE_UPLOAD_MAX_SIZE ?? (10 * 1024 * 1024); // 10MB default
        $this->uploadPath = UPLOAD_PATH;
        $this->tempPath = TEMP_PATH;
    }

    private function createDirectories(): void
    {
        $directories = [
            $this->uploadPath,
            $this->uploadPath . '/images',
            $this->uploadPath . '/documents',
            $this->uploadPath . '/videos',
            $this->uploadPath . '/audio',
            $this->uploadPath . '/archives',
            $this->uploadPath . '/code',
            $this->uploadPath . '/avatars',
            $this->uploadPath . '/attachments',
            $this->tempPath
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function uploadFile(array $file, string $category = 'general', array $options = []): array
    {
        try {
            // Validate file
            $validation = $this->validateFile($file, $category);
            if (!$validation['valid']) {
                return $validation;
            }

            // Check user permissions
            if (!$this->checkUploadPermissions($category)) {
                return [
                    'success' => false,
                    'error' => 'You do not have permission to upload this type of file'
                ];
            }

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file['name']);
            $filePath = $this->getUploadPath($category) . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new \Exception('Failed to move uploaded file');
            }

            // Set proper permissions
            chmod($filePath, 0644);

            // Get file info
            $fileInfo = $this->getFileInfo($filePath, $file);

            // Process file based on type
            $processedFile = $this->processFile($filePath, $fileInfo, $options);

            // Save to database
            $fileId = $this->saveFileRecord($processedFile, $category);

            // Log upload
            $this->logFileUpload($fileId, $fileInfo);

            return [
                'success' => true,
                'file_id' => $fileId,
                'filename' => $filename,
                'original_name' => $file['name'],
                'size' => $fileInfo['size'],
                'type' => $fileInfo['type'],
                'category' => $category,
                'url' => $this->getFileUrl($filename, $category),
                'thumbnail_url' => $processedFile['thumbnail_url'] ?? null,
                'metadata' => $processedFile['metadata'] ?? []
            ];

        } catch (\Exception $e) {
            $this->logger->error('File upload error', [
                'error' => $e->getMessage(),
                'file' => $file['name'] ?? 'unknown',
                'category' => $category
            ]);

            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    public function uploadMultipleFiles(array $files, string $category = 'general', array $options = []): array
    {
        $results = [];
        $uploadedFiles = [];
        $errors = [];

        foreach ($files as $index => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $result = $this->uploadFile($file, $category, $options);
                
                if ($result['success']) {
                    $uploadedFiles[] = $result;
                } else {
                    $errors[] = "File {$file['name']}: {$result['error']}";
                }
            } else {
                $errors[] = "File {$file['name']}: " . $this->getUploadErrorMessage($file['error']);
            }
        }

        return [
            'success' => count($uploadedFiles) > 0,
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors,
            'total_uploaded' => count($uploadedFiles),
            'total_errors' => count($errors)
        ];
    }

    private function validateFile(array $file, string $category): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->maxFileSize)
            ];
        }

        // Check file type
        $fileType = $file['type'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!isset($this->allowedTypes[$category])) {
            return [
                'valid' => false,
                'error' => "Invalid upload category: $category"
            ];
        }

        if (!in_array($fileType, $this->allowedTypes[$category]) && 
            !in_array($fileExtension, $this->allowedExtensions[$category])) {
            return [
                'valid' => false,
                'error' => "File type not allowed for category: $category"
            ];
        }

        // Additional security checks
        $securityCheck = $this->performSecurityChecks($file);
        if (!$securityCheck['valid']) {
            return $securityCheck;
        }

        return ['valid' => true];
    }

    private function performSecurityChecks(array $file): array
    {
        // Check for malicious files
        $filename = $file['name'];
        
        // Check for executable extensions
        $executableExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, $executableExtensions)) {
            return [
                'valid' => false,
                'error' => 'Executable files are not allowed'
            ];
        }

        // Check for suspicious filenames
        $suspiciousPatterns = ['/\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$/i'];
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return [
                    'valid' => false,
                    'error' => 'Suspicious file type detected'
                ];
            }
        }

        // Check file content for images
        if (strpos($file['type'], 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return [
                    'valid' => false,
                    'error' => 'Invalid image file'
                ];
            }
        }

        return ['valid' => true];
    }

    private function checkUploadPermissions(string $category): bool
    {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }

        $user = $this->auth->getCurrentUser();
        if (!$user) {
            return false;
        }

        // Check user role permissions
        $role = $user['role'] ?? 'user';
        
        switch ($category) {
            case 'image':
            case 'document':
                return true; // All logged-in users can upload images and documents
            
            case 'video':
            case 'audio':
                return in_array($role, ['user', 'moderator', 'admin']); // Regular users and above
            
            case 'archive':
            case 'code':
                return in_array($role, ['moderator', 'admin']); // Moderators and admins only
            
            default:
                return false;
        }
    }

    private function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize filename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
        $basename = substr($basename, 0, 50); // Limit length
        
        // Generate unique filename
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
    }

    private function getUploadPath(string $category): string
    {
        $paths = [
            'image' => $this->uploadPath . '/images',
            'document' => $this->uploadPath . '/documents',
            'video' => $this->uploadPath . '/videos',
            'audio' => $this->uploadPath . '/audio',
            'archive' => $this->uploadPath . '/archives',
            'code' => $this->uploadPath . '/code',
            'avatar' => $this->uploadPath . '/avatars',
            'attachment' => $this->uploadPath . '/attachments'
        ];

        return $paths[$category] ?? $this->uploadPath . '/general';
    }

    private function getFileUrl(string $filename, string $category): string
    {
        $baseUrl = APP_URL . '/uploads';
        $paths = [
            'image' => '/images',
            'document' => '/documents',
            'video' => '/videos',
            'audio' => '/audio',
            'archive' => '/archives',
            'code' => '/code',
            'avatar' => '/avatars',
            'attachment' => '/attachments'
        ];

        $path = $paths[$category] ?? '/general';
        return $baseUrl . $path . '/' . $filename;
    }

    private function getFileInfo(string $filePath, array $originalFile): array
    {
        $info = [
            'size' => filesize($filePath),
            'type' => $originalFile['type'],
            'extension' => strtolower(pathinfo($originalFile['name'], PATHINFO_EXTENSION)),
            'mime_type' => mime_content_type($filePath),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Get additional info based on file type
        if (strpos($info['type'], 'image/') === 0) {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $info['width'] = $imageInfo[0];
                $info['height'] = $imageInfo[1];
                $info['image_type'] = $imageInfo[2];
            }
        }

        return $info;
    }

    private function processFile(string $filePath, array $fileInfo, array $options): array
    {
        $processed = [
            'file_path' => $filePath,
            'file_info' => $fileInfo,
            'metadata' => []
        ];

        // Process images
        if (strpos($fileInfo['type'], 'image/') === 0) {
            $processed = array_merge($processed, $this->processImage($filePath, $fileInfo, $options));
        }

        // Process videos
        if (strpos($fileInfo['type'], 'video/') === 0) {
            $processed = array_merge($processed, $this->processVideo($filePath, $fileInfo, $options));
        }

        // Process documents
        if (strpos($fileInfo['type'], 'application/pdf') === 0) {
            $processed = array_merge($processed, $this->processPDF($filePath, $fileInfo, $options));
        }

        return $processed;
    }

    private function processImage(string $filePath, array $fileInfo, array $options): array
    {
        $result = [];

        // Generate thumbnail if requested
        if ($options['generate_thumbnail'] ?? true) {
            $thumbnailPath = $this->generateThumbnail($filePath, $fileInfo);
            if ($thumbnailPath) {
                $result['thumbnail_path'] = $thumbnailPath;
                $result['thumbnail_url'] = $this->getThumbnailUrl($thumbnailPath);
            }
        }

        // Resize image if too large
        if (($fileInfo['width'] ?? 0) > 1920 || ($fileInfo['height'] ?? 0) > 1080) {
            $resizedPath = $this->resizeImage($filePath, $fileInfo, 1920, 1080);
            if ($resizedPath) {
                $result['resized_path'] = $resizedPath;
            }
        }

        return $result;
    }

    private function generateThumbnail(string $filePath, array $fileInfo): ?string
    {
        try {
            $thumbnailPath = $this->tempPath . '/thumb_' . basename($filePath);
            
            // Create thumbnail using GD
            $sourceImage = $this->createImageFromFile($filePath, $fileInfo['type']);
            if (!$sourceImage) {
                return null;
            }

            $width = $fileInfo['width'] ?? 0;
            $height = $fileInfo['height'] ?? 0;
            
            // Calculate thumbnail dimensions
            $thumbSize = 200;
            $thumbWidth = $thumbSize;
            $thumbHeight = $thumbSize;
            
            if ($width > $height) {
                $thumbHeight = ($height / $width) * $thumbSize;
            } else {
                $thumbWidth = ($width / $height) * $thumbSize;
            }

            // Create thumbnail
            $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

            // Save thumbnail
            $this->saveImageToFile($thumbnail, $thumbnailPath, $fileInfo['type']);
            
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);

            return $thumbnailPath;
        } catch (\Exception $e) {
            $this->logger->error('Thumbnail generation failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function resizeImage(string $filePath, array $fileInfo, int $maxWidth, int $maxHeight): ?string
    {
        try {
            $resizedPath = $this->tempPath . '/resized_' . basename($filePath);
            
            $sourceImage = $this->createImageFromFile($filePath, $fileInfo['type']);
            if (!$sourceImage) {
                return null;
            }

            $width = $fileInfo['width'] ?? 0;
            $height = $fileInfo['height'] ?? 0;

            // Calculate new dimensions
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = $width * $ratio;
            $newHeight = $height * $ratio;

            // Create resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save resized image
            $this->saveImageToFile($resizedImage, $resizedPath, $fileInfo['type']);
            
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            return $resizedPath;
        } catch (\Exception $e) {
            $this->logger->error('Image resize failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function createImageFromFile(string $filePath, string $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/gif':
                return imagecreatefromgif($filePath);
            case 'image/webp':
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }

    private function saveImageToFile($image, string $filePath, string $mimeType): bool
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($image, $filePath, 90);
            case 'image/png':
                return imagepng($image, $filePath, 9);
            case 'image/gif':
                return imagegif($image, $filePath);
            case 'image/webp':
                return imagewebp($image, $filePath, 90);
            default:
                return false;
        }
    }

    private function processVideo(string $filePath, array $fileInfo, array $options): array
    {
        // Video processing would require FFmpeg
        // For now, just return basic info
        return [
            'metadata' => [
                'duration' => null, // Would extract with FFmpeg
                'resolution' => null,
                'bitrate' => null
            ]
        ];
    }

    private function processPDF(string $filePath, array $fileInfo, array $options): array
    {
        // PDF processing would require PDF libraries
        // For now, just return basic info
        return [
            'metadata' => [
                'pages' => null, // Would extract with PDF library
                'title' => null,
                'author' => null
            ]
        ];
    }

    private function saveFileRecord(array $processedFile, string $category): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO file_uploads (
                user_id, original_name, filename, file_path, file_size, 
                mime_type, category, metadata, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $userId = $this->auth->getCurrentUser()['id'] ?? null;
        $fileInfo = $processedFile['file_info'];
        
        $stmt->execute([
            $userId,
            $processedFile['original_name'] ?? '',
            basename($processedFile['file_path']),
            $processedFile['file_path'],
            $fileInfo['size'],
            $fileInfo['mime_type'],
            $category,
            json_encode($processedFile['metadata'] ?? []),
            date('Y-m-d H:i:s')
        ]);

        return $this->db->lastInsertId();
    }

    private function logFileUpload(int $fileId, array $fileInfo): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO file_upload_logs (
                    file_id, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?)
            ");

            $userId = $this->auth->getCurrentUser()['id'] ?? null;
            
            $stmt->execute([
                $fileId,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to log file upload', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return $messages[$errorCode] ?? 'Unknown upload error';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getThumbnailUrl(string $thumbnailPath): string
    {
        $filename = basename($thumbnailPath);
        return APP_URL . '/uploads/thumbnails/' . $filename;
    }

    public function deleteFile(int $fileId): bool
    {
        try {
            // Get file record
            $stmt = $this->db->prepare("SELECT * FROM file_uploads WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();

            if (!$file) {
                return false;
            }

            // Check permissions
            $user = $this->auth->getCurrentUser();
            if (!$user || ($user['id'] != $file['user_id'] && !in_array($user['role'], ['moderator', 'admin']))) {
                return false;
            }

            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Delete thumbnail if exists
            $thumbnailPath = $this->tempPath . '/thumb_' . basename($file['file_path']);
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }

            // Delete database record
            $stmt = $this->db->prepare("DELETE FROM file_uploads WHERE id = ?");
            $result = $stmt->execute([$fileId]);

            // Log deletion
            $this->logger->info('File deleted', [
                'file_id' => $fileId,
                'user_id' => $user['id']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('File deletion failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getFileInfo(int $fileId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM file_uploads WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();

            if (!$file) {
                return null;
            }

            return [
                'id' => $file['id'],
                'original_name' => $file['original_name'],
                'filename' => $file['filename'],
                'file_size' => $file['file_size'],
                'mime_type' => $file['mime_type'],
                'category' => $file['category'],
                'url' => $this->getFileUrl($file['filename'], $file['category']),
                'created_at' => $file['created_at'],
                'metadata' => json_decode($file['metadata'], true) ?? []
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get file info', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getUserFiles(int $userId, int $limit = 50, int $offset = 0): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM file_uploads 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$userId, $limit, $offset]);
            $files = $stmt->fetchAll();

            $result = [];
            foreach ($files as $file) {
                $result[] = [
                    'id' => $file['id'],
                    'original_name' => $file['original_name'],
                    'filename' => $file['filename'],
                    'file_size' => $file['file_size'],
                    'mime_type' => $file['mime_type'],
                    'category' => $file['category'],
                    'url' => $this->getFileUrl($file['filename'], $file['category']),
                    'created_at' => $file['created_at']
                ];
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user files', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}