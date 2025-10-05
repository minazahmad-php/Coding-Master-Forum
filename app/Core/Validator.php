<?php

namespace App\Core;

/**
 * Input Validator
 * Handles input validation and sanitization
 */
class Validator
{
    private $errors = [];

    /**
     * Validate required fields
     */
    public function required($fields, $data)
    {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = ucfirst($field) . ' is required.';
            }
        }
        
        $this->errors = array_merge($this->errors, $errors);
        return empty($errors);
    }

    /**
     * Validate email format
     */
    public function email($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Invalid email format.';
            return false;
        }
        return true;
    }

    /**
     * Validate password strength
     */
    public function password($password)
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
        $this->errors = array_merge($this->errors, $errors);
        return empty($errors);
    }

    /**
     * Validate string length
     */
    public function length($value, $min, $max = null)
    {
        $len = strlen($value);
        
        if ($len < $min) {
            $this->errors[] = "Value must be at least {$min} characters long.";
            return false;
        }
        
        if ($max && $len > $max) {
            $this->errors[] = "Value must be no more than {$max} characters long.";
            return false;
        }
        
        return true;
    }

    /**
     * Validate numeric value
     */
    public function numeric($value)
    {
        if (!is_numeric($value)) {
            $this->errors[] = 'Value must be numeric.';
            return false;
        }
        return true;
    }

    /**
     * Validate integer value
     */
    public function integer($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[] = 'Value must be an integer.';
            return false;
        }
        return true;
    }

    /**
     * Validate URL format
     */
    public function url($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors[] = 'Invalid URL format.';
            return false;
        }
        return true;
    }

    /**
     * Validate date format
     */
    public function date($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            $this->errors[] = 'Invalid date format.';
            return false;
        }
        return true;
    }

    /**
     * Validate file upload
     */
    public function file($file, $allowedTypes = [], $maxSize = 5242880)
    {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed.';
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size too large.';
        }
        
        if (!empty($allowedTypes)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes)) {
                $errors[] = 'Invalid file type.';
            }
        }
        
        $this->errors = array_merge($this->errors, $errors);
        return empty($errors);
    }

    /**
     * Sanitize string
     */
    public function sanitize($value)
    {
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }

    /**
     * Sanitize array
     */
    public function sanitizeArray($data)
    {
        return array_map([$this, 'sanitize'], $data);
    }

    /**
     * Get validation errors
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Check if validation passed
     */
    public function passes()
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails()
    {
        return !empty($this->errors);
    }

    /**
     * Clear errors
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * Get first error
     */
    public function firstError()
    {
        return !empty($this->errors) ? $this->errors[0] : null;
    }
}