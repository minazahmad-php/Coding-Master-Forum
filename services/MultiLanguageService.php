<?php
declare(strict_types=1);

/**
 * Modern Forum - Multi-language Support Service
 * Handles internationalization and localization
 */

namespace Services;

use Core\Database;
use Core\Logger;
use Core\Session;

class MultiLanguageService
{
    private Database $db;
    private Logger $logger;
    private array $languages = [];
    private array $translations = [];
    private string $currentLanguage = 'en';
    private string $fallbackLanguage = 'en';
    private array $rtlLanguages = ['ar', 'he', 'fa', 'ur'];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->loadLanguages();
        $this->setCurrentLanguage();
    }

    private function loadLanguages(): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT code, name, native_name, direction, is_active, is_default
                FROM languages 
                WHERE is_active = 1 
                ORDER BY sort_order ASC, name ASC
            ");
            $stmt->execute();
            $this->languages = $stmt->fetchAll();

            // Set fallback language
            foreach ($this->languages as $lang) {
                if ($lang['is_default']) {
                    $this->fallbackLanguage = $lang['code'];
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to load languages', [
                'error' => $e->getMessage()
            ]);
            $this->languages = $this->getDefaultLanguages();
        }
    }

    private function setCurrentLanguage(): void
    {
        // Priority: URL parameter > Session > User preference > Browser > Default
        $language = null;

        // 1. Check URL parameter
        if (isset($_GET['lang']) && $this->isValidLanguage($_GET['lang'])) {
            $language = $_GET['lang'];
        }

        // 2. Check session
        if (!$language && Session::has('language')) {
            $sessionLang = Session::get('language');
            if ($this->isValidLanguage($sessionLang)) {
                $language = $sessionLang;
            }
        }

        // 3. Check user preference (if logged in)
        if (!$language && Session::has('user_id')) {
            $userLang = $this->getUserLanguage(Session::get('user_id'));
            if ($userLang && $this->isValidLanguage($userLang)) {
                $language = $userLang;
            }
        }

        // 4. Check browser language
        if (!$language) {
            $browserLang = $this->getBrowserLanguage();
            if ($browserLang && $this->isValidLanguage($browserLang)) {
                $language = $browserLang;
            }
        }

        // 5. Use default
        if (!$language) {
            $language = $this->fallbackLanguage;
        }

        $this->currentLanguage = $language;
        Session::set('language', $language);
    }

    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }

    public function setLanguage(string $languageCode): bool
    {
        if (!$this->isValidLanguage($languageCode)) {
            return false;
        }

        $this->currentLanguage = $languageCode;
        Session::set('language', $languageCode);

        // Update user preference if logged in
        if (Session::has('user_id')) {
            $this->updateUserLanguage(Session::get('user_id'), $languageCode);
        }

        return true;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function getLanguageInfo(string $languageCode): ?array
    {
        foreach ($this->languages as $lang) {
            if ($lang['code'] === $languageCode) {
                return $lang;
            }
        }
        return null;
    }

    public function isValidLanguage(string $languageCode): bool
    {
        foreach ($this->languages as $lang) {
            if ($lang['code'] === $languageCode) {
                return true;
            }
        }
        return false;
    }

    public function isRTL(string $languageCode = null): bool
    {
        $lang = $languageCode ?? $this->currentLanguage;
        return in_array($lang, $this->rtlLanguages);
    }

    public function getDirection(string $languageCode = null): string
    {
        return $this->isRTL($languageCode) ? 'rtl' : 'ltr';
    }

    public function translate(string $key, array $params = [], string $language = null): string
    {
        $lang = $language ?? $this->currentLanguage;
        
        // Load translations if not loaded
        if (!isset($this->translations[$lang])) {
            $this->loadTranslations($lang);
        }

        $translation = $this->getTranslation($key, $lang);
        
        // Replace parameters
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace(":$param", $value, $translation);
            }
        }

        return $translation;
    }

    public function t(string $key, array $params = [], string $language = null): string
    {
        return $this->translate($key, $params, $language);
    }

    private function getTranslation(string $key, string $language): string
    {
        // Try current language first
        if (isset($this->translations[$language][$key])) {
            return $this->translations[$language][$key];
        }

        // Try fallback language
        if ($language !== $this->fallbackLanguage && isset($this->translations[$this->fallbackLanguage][$key])) {
            return $this->translations[$this->fallbackLanguage][$key];
        }

        // Return key if no translation found
        return $key;
    }

    private function loadTranslations(string $language): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT t.key, t.value
                FROM translations t
                JOIN languages l ON t.language_id = l.id
                WHERE l.code = ? AND t.is_active = 1
            ");
            $stmt->execute([$language]);
            $translations = $stmt->fetchAll();

            $this->translations[$language] = [];
            foreach ($translations as $translation) {
                $this->translations[$language][$translation['key']] = $translation['value'];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to load translations', [
                'language' => $language,
                'error' => $e->getMessage()
            ]);
            $this->translations[$language] = [];
        }
    }

    private function getBrowserLanguage(): ?string
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang = explode('-', $lang)[0]; // Get primary language code
            
            if ($this->isValidLanguage($lang)) {
                return $lang;
            }
        }

        return null;
    }

    private function getUserLanguage(int $userId): ?string
    {
        try {
            $stmt = $this->db->prepare("SELECT language FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            return $user ? $user['language'] : null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user language', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function updateUserLanguage(int $userId, string $languageCode): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET language = ? WHERE id = ?");
            $stmt->execute([$languageCode, $userId]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user language', [
                'user_id' => $userId,
                'language' => $languageCode,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function addTranslation(string $languageCode, string $key, string $value): bool
    {
        try {
            $languageId = $this->getLanguageId($languageCode);
            if (!$languageId) {
                return false;
            }

            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO translations (language_id, key, value, is_active, created_at, updated_at)
                VALUES (?, ?, ?, 1, ?, ?)
            ");
            
            $now = date('Y-m-d H:i:s');
            $result = $stmt->execute([$languageId, $key, $value, $now, $now]);

            if ($result) {
                // Update cache
                $this->translations[$languageCode][$key] = $value;
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add translation', [
                'language' => $languageCode,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function updateTranslation(string $languageCode, string $key, string $value): bool
    {
        return $this->addTranslation($languageCode, $key, $value);
    }

    public function deleteTranslation(string $languageCode, string $key): bool
    {
        try {
            $languageId = $this->getLanguageId($languageCode);
            if (!$languageId) {
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM translations WHERE language_id = ? AND key = ?");
            $result = $stmt->execute([$languageId, $key]);

            if ($result) {
                // Update cache
                unset($this->translations[$languageCode][$key]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete translation', [
                'language' => $languageCode,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function getLanguageId(string $languageCode): ?int
    {
        foreach ($this->languages as $lang) {
            if ($lang['code'] === $languageCode) {
                return $lang['id'] ?? null;
            }
        }
        return null;
    }

    public function getMissingTranslations(string $languageCode): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT t.key
                FROM translations t
                JOIN languages l ON t.language_id = l.id
                WHERE l.code = ? AND t.is_active = 1
            ");
            $stmt->execute([$this->fallbackLanguage]);
            $fallbackKeys = array_column($stmt->fetchAll(), 'key');

            $stmt->execute([$languageCode]);
            $currentKeys = array_column($stmt->fetchAll(), 'key');

            return array_diff($fallbackKeys, $currentKeys);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get missing translations', [
                'language' => $languageCode,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function exportTranslations(string $languageCode): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT t.key, t.value
                FROM translations t
                JOIN languages l ON t.language_id = l.id
                WHERE l.code = ? AND t.is_active = 1
                ORDER BY t.key
            ");
            $stmt->execute([$languageCode]);
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('Failed to export translations', [
                'language' => $languageCode,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function importTranslations(string $languageCode, array $translations): bool
    {
        try {
            $languageId = $this->getLanguageId($languageCode);
            if (!$languageId) {
                return false;
            }

            $this->db->beginTransaction();

            foreach ($translations as $translation) {
                $stmt = $this->db->prepare("
                    INSERT OR REPLACE INTO translations (language_id, key, value, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, 1, ?, ?)
                ");
                
                $now = date('Y-m-d H:i:s');
                $stmt->execute([
                    $languageId,
                    $translation['key'],
                    $translation['value'],
                    $now,
                    $now
                ]);
            }

            $this->db->commit();
            
            // Clear cache
            unset($this->translations[$languageCode]);
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to import translations', [
                'language' => $languageCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getTranslationStats(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    l.code,
                    l.name,
                    COUNT(t.id) as translation_count,
                    COUNT(CASE WHEN t.is_active = 1 THEN 1 END) as active_count
                FROM languages l
                LEFT JOIN translations t ON l.id = t.language_id
                WHERE l.is_active = 1
                GROUP BY l.id, l.code, l.name
                ORDER BY l.name
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('Failed to get translation stats', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getDefaultLanguages(): array
    {
        return [
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_active' => 1,
                'is_default' => 1
            ],
            [
                'code' => 'bn',
                'name' => 'Bengali',
                'native_name' => 'বাংলা',
                'direction' => 'ltr',
                'is_active' => 1,
                'is_default' => 0
            ],
            [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'is_active' => 1,
                'is_default' => 0
            ],
            [
                'code' => 'hi',
                'name' => 'Hindi',
                'native_name' => 'हिन्दी',
                'direction' => 'ltr',
                'is_active' => 1,
                'is_default' => 0
            ]
        ];
    }

    public function formatDate(string $date, string $format = null, string $language = null): string
    {
        $lang = $language ?? $this->currentLanguage;
        
        // Use locale-specific formatting
        $formats = [
            'en' => 'M j, Y',
            'bn' => 'j M, Y',
            'ar' => 'j M Y',
            'hi' => 'j M, Y'
        ];
        
        $dateFormat = $format ?? $formats[$lang] ?? $formats['en'];
        
        return date($dateFormat, strtotime($date));
    }

    public function formatNumber(float $number, int $decimals = 0, string $language = null): string
    {
        $lang = $language ?? $this->currentLanguage;
        
        // Use locale-specific number formatting
        $formats = [
            'en' => ['.', ','],
            'bn' => ['.', ','],
            'ar' => ['.', ','],
            'hi' => ['.', ',']
        ];
        
        $format = $formats[$lang] ?? $formats['en'];
        
        return number_format($number, $decimals, $format[0], $format[1]);
    }

    public function formatCurrency(float $amount, string $currency = 'USD', string $language = null): string
    {
        $lang = $language ?? $this->currentLanguage;
        
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'BDT' => '৳',
            'INR' => '₹',
            'SAR' => '﷼'
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        $formattedAmount = $this->formatNumber($amount, 2, $lang);
        
        return $symbol . $formattedAmount;
    }
}