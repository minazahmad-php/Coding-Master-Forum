<?php

namespace App\Controllers;

/**
 * Language Controller
 * Handles language switching
 */
class LanguageController extends BaseController
{
    /**
     * Switch language
     */
    public function switch($lang)
    {
        $allowedLanguages = ['en', 'bn'];
        
        if (in_array($lang, $allowedLanguages)) {
            $this->session->set('locale', $lang);
        }
        
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/';
        redirect($redirectUrl);
    }
}