<?php
declare(strict_types=1);

/**
 * Modern Forum - Language Controller
 * Handles language switching and management
 */

namespace Controllers;

use Core\Controller;
use Core\View;
use Services\MultiLanguageService;

class LanguageController extends Controller
{
    private MultiLanguageService $languageService;

    public function __construct()
    {
        parent::__construct();
        $this->languageService = new MultiLanguageService();
    }

    public function switch(string $languageCode): void
    {
        if ($this->languageService->setLanguage($languageCode)) {
            Session::flash('success', 'Language changed successfully');
        } else {
            Session::flash('error', 'Invalid language selected');
        }

        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/';
        View::redirect($redirectUrl);
    }

    public function index(): void
    {
        $data = [
            'title' => 'Language Settings',
            'languages' => $this->languageService->getLanguages(),
            'current_language' => $this->languageService->getCurrentLanguage(),
            'stats' => $this->languageService->getTranslationStats()
        ];

        View::render('language/index', $data);
    }

    public function translations(string $languageCode): void
    {
        if (!$this->languageService->isValidLanguage($languageCode)) {
            View::redirect('/language');
            return;
        }

        $data = [
            'title' => 'Translations - ' . $this->languageService->getLanguageInfo($languageCode)['name'],
            'language_code' => $languageCode,
            'language_info' => $this->languageService->getLanguageInfo($languageCode),
            'translations' => $this->languageService->exportTranslations($languageCode),
            'missing_translations' => $this->languageService->getMissingTranslations($languageCode)
        ];

        View::render('language/translations', $data);
    }

    public function updateTranslation(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json(['success' => false, 'error' => 'Only POST method allowed'], 405);
            return;
        }

        $languageCode = $_POST['language'] ?? '';
        $key = $_POST['key'] ?? '';
        $value = $_POST['value'] ?? '';

        if (empty($languageCode) || empty($key)) {
            View::json(['success' => false, 'error' => 'Missing required parameters'], 400);
            return;
        }

        $success = $this->languageService->updateTranslation($languageCode, $key, $value);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'Translation updated successfully' : 'Failed to update translation'
        ]);
    }

    public function export(string $languageCode): void
    {
        if (!$this->languageService->isValidLanguage($languageCode)) {
            View::json(['success' => false, 'error' => 'Invalid language'], 400);
            return;
        }

        $translations = $this->languageService->exportTranslations($languageCode);
        $languageInfo = $this->languageService->getLanguageInfo($languageCode);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="translations_' . $languageCode . '.json"');
        
        echo json_encode([
            'language' => $languageCode,
            'language_name' => $languageInfo['name'],
            'export_date' => date('Y-m-d H:i:s'),
            'translations' => $translations
        ], JSON_PRETTY_PRINT);
    }

    public function import(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json(['success' => false, 'error' => 'Only POST method allowed'], 405);
            return;
        }

        $languageCode = $_POST['language'] ?? '';
        $file = $_FILES['file'] ?? null;

        if (empty($languageCode) || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            View::json(['success' => false, 'error' => 'Invalid file upload'], 400);
            return;
        }

        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if (!$data || !isset($data['translations'])) {
            View::json(['success' => false, 'error' => 'Invalid file format'], 400);
            return;
        }

        $success = $this->languageService->importTranslations($languageCode, $data['translations']);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'Translations imported successfully' : 'Failed to import translations'
        ]);
    }
}