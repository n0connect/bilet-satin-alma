<?php
/**
 * Pure WAF Module - Web Application Firewall + Advanced Validation
 *
 * @author n0connect
 * @version 8.0 - SIMPLIFIED & SELECTIVE
 * @description Basit, etkili, seçici - Tehdit tespiti + Whitelist validation
 *
 * PHILOSOPHY:
 * - Whitelist-based approach (sadece güvenli karakterlere izin ver)
 * - Selective bypass (email/password gibi özel alanlar için)
 * - No false positives on legitimate inputs
 * - Simple but effective
 */

class SecurityModule {
    private static $logEnabled = true;

    // ============================================
    // DANGEROUS CHARACTERS LIST (BLACKLIST)
    // ============================================

    /**
     * Dangerous characters that are commonly used in attacks
     * Note: This list is NOT actively used in v8.0 whitelist approach,
     * but kept for reference and potential future blacklist needs
     */
    private static $badCharList = [
        '<', '>', '"', "'", '`', '\\', '/', ';', '|', '&',
        '$', '(', ')', '[', ']', '{', '}', '%', '#',
        '!', '~', '^', '*', '=', '+', ':', ',', '.',"-","@",'~',"`"
    ];

    // ============================================
    // SELECTIVE BYPASS MODES
    // ============================================

    const MODE_STRICT = 'strict';        // Tüm tehlikeli karakterleri engelle
    const MODE_EMAIL = 'email';          // Email için @ karakterine izin ver
    const MODE_PASSWORD = 'password';    // Password için özel karakterlere izin ver
    const MODE_TEXT = 'text';            // Normal text için temel karakterler
    const MODE_PASSTHROUGH = 'passthrough'; // Sadece çok tehlikeli olanları engelle

    /**
     * Dangerous patterns - Always blocked regardless of mode
     * SQL Injection, XSS, Command Injection patterns
     */
    private static $alwaysBlockedPatterns = [
        // SQL Injection
        '/(\bUNION\b.*\bSELECT\b)/i',
        '/(\bSELECT\b.*\bFROM\b)/i',
        '/(\bINSERT\b.*\bINTO\b)/i',
        '/(\bUPDATE\b.*\bSET\b)/i',
        '/(\bDELETE\b.*\bFROM\b)/i',
        '/(\bDROP\b.*\bTABLE\b)/i',
        '/(\bEXEC\b|\bEXECUTE\b)/i',

        // XSS
        '/<script[^>]*>/is',
        '/javascript:/i',
        '/on\w+=/i', // onclick, onerror, etc.

        // Command Injection
        '/[;&|]\s*(cat|ls|wget|curl|nc|bash|sh|cmd|whoami|id|pwd)/i',
        '/&&/',
        '/\|\|/',

        // Path Traversal
        '/\.\.\//i',
        '/\.\.\\\\/',

        // Null bytes
        "/\x00/",
    ];

    // ============================================
    // MAIN WAF FUNCTIONS - SELECTIVE MODES
    // ============================================

    /**
     * WAF with sanitization (default behavior)
     * Detects threats AND applies htmlspecialchars()
     *
     * @param mixed $input Input to check and sanitize
     * @param string $mode Bypass mode (strict, email, password, text, passthrough)
     * @return mixed Sanitized output (or blocks with 403)
     */
    public static function sanitize($input, $mode = self::MODE_STRICT) {
        return self::wafCore($input, true, $mode);
    }

    /**
     * WAF without sanitization (pass-through mode)
     * Only detects threats, does NOT apply htmlspecialchars()
     * Returns original input if safe
     *
     * @param mixed $input Input to check
     * @param string $mode Bypass mode
     * @return mixed Original input (or blocks with 403)
     */
    public static function pass($input, $mode = self::MODE_PASSTHROUGH) {
        return self::wafCore($input, false, $mode);
    }

    /**
     * Core WAF logic with selective bypass
     *
     * @param mixed $input Input to check
     * @param bool $applySanitization Whether to apply htmlspecialchars
     * @param string $mode Bypass mode
     * @return mixed Processed input
     */
    private static function wafCore($input, $applySanitization = true, $mode = self::MODE_STRICT) {

        // Null or empty - allow and return as-is
        if ($input === null || $input === '') {
            return $input;
        }

        // Array handling - recursive check
        if (is_array($input)) {
            $cleaned = [];
            foreach ($input as $key => $value) {
                $cleaned[$key] = self::wafCore($value, $applySanitization, $mode);
            }
            return $cleaned;
        }

        // Object handling
        if (is_object($input)) {
            if (method_exists($input, '__toString')) {
                $input = (string)$input;
            } else {
                self::blockRequest('Invalid object type', $input);
            }
        }

        // Convert to string
        $input = (string)$input;
        $originalInput = $input;

        // ========================================
        // STEP 1: ALWAYS BLOCKED PATTERNS CHECK
        // ========================================

        foreach (self::$alwaysBlockedPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::blockRequest("Dangerous pattern detected", $originalInput);
            }
        }

        // ========================================
        // STEP 2: MULTI-LAYER DECODING (Bypass Detection)
        // ========================================

        $decoded = $input;
        $maxIterations = 5;

        for ($i = 0; $i < $maxIterations; $i++) {
            $previous = $decoded;

            // URL Decoding
            $decoded = urldecode($decoded);

            // HTML Entity Decoding
            $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Stop if no change detected
            if ($decoded === $previous) {
                break;
            }

            // Re-check always blocked patterns on decoded version
            foreach (self::$alwaysBlockedPatterns as $pattern) {
                if (preg_match($pattern, $decoded)) {
                    self::blockRequest("Encoded attack detected", $originalInput);
                }
            }
        }

        // ========================================
        // STEP 3: MODE-BASED WHITELIST VALIDATION
        // ========================================

        $isValid = self::validateByMode($input, $mode);

        if (!$isValid) {
            self::blockRequest("Invalid characters for mode: $mode", $originalInput);
        }

        // ========================================
        // STEP 4: FINAL OUTPUT
        // ========================================

        if ($applySanitization) {
            return htmlspecialchars(
                $input,
                ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
                'UTF-8',
                false
            );
        } else {
            return $input;
        }
    }

    /**
     * Validate input based on mode using WHITELIST approach
     */
    private static function validateByMode($input, $mode) {
        switch ($mode) {
            case self::MODE_EMAIL:
                // Email: a-z, 0-9, @, ., _, +, -, %
                return preg_match('/^[a-zA-Z0-9@._+%-]+$/', $input);

            case self::MODE_PASSWORD:
                // Password: a-z, 0-9, and common safe symbols
                // Allow: ! @ # $ % - _ = + * ? & (but NOT dangerous ones like ' " < > ; | \ /)
                return preg_match('/^[a-zA-Z0-9!@#$%\-_=+*?&]+$/', $input);

            case self::MODE_TEXT:
                // Normal text: letters, numbers, spaces, basic punctuation
                // Turkish characters included: ğ, ü, ş, ı, ö, ç, İ, Ğ, Ü, Ş, Ö, Ç
                return preg_match('/^[\p{L}\p{N}\s.,!?\-]+$/u', $input);

            case self::MODE_PASSTHROUGH:
                // Very permissive - only block EXTREMELY dangerous chars
                // Block: < > ' " ` ; | \0
                return !preg_match('/[<>\'"`;\|\x00]/', $input);

            case self::MODE_STRICT:
            default:
                // Strict mode: Only alphanumeric + Turkish chars + spaces
                return preg_match('/^[\p{L}\p{N}\s]+$/u', $input);
        }
    }

    // ============================================
    // BLOCK REQUEST - IMMEDIATE TERMINATION (403)
    // ============================================

    public static function blockRequest($threat, $original = null) {

        // Log threat
        self::logThreat($threat, $original ?? $threat);

        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set 403 Forbidden status
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-WAF-Blocked: true');
        header('X-WAF-Reason: ' . substr($threat, 0, 100));

        // Prepare threat data for 403 page - secure with SecurityModule::reflect.
        $waf_incident_id = SecurityModule::reflect(strtoupper(bin2hex(random_bytes(6))));
        $waf_timestamp = SecurityModule::reflect(date('Y-m-d H:i:s'));
        $waf_ip = SecurityModule::reflect($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
        $waf_threat_reason = SecurityModule::reflect($threat);
        $waf_request_uri = SecurityModule::reflect($_SERVER['REQUEST_URI'] ?? '/');
        $waf_user_agent = SecurityModule::reflect($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');

        // Include professional 403 blocked page
        $blockedPage = __DIR__ . '/239fcbd0-c512-4694-aa09-36d87260396c.php';

        if (file_exists($blockedPage)) {
            include $blockedPage;
        } else {
            // Fallback if 403 page is missing
            echo '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>';
            echo '<h1>403 - Access Forbidden</h1>';
            echo '<p>Your request has been blocked by our security system.</p>';
            echo '<p>Incident ID: ' . SecurityModule::reflect($waf_incident_id) . '</p>';
            echo '</body></html>';
        }

        // TERMINATE IMMEDIATELY
        exit(1);
    }

    // ============================================
    // LOGGING
    // ============================================

    private static function logThreat($threat, $input) {
        if (!self::$logEnabled) return;

        $logFile = __DIR__ . '/logs/waf_threats.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0750, true);
        }

        $entry = [
            'timestamp' => SecurityModule::reflect(date('c')),
            'ip' => SecurityModule::reflect($_SERVER['REMOTE_ADDR'] ?? 'CLI'),
            'user_agent' => SecurityModule::reflect($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
            'uri' => SecurityModule::reflect($_SERVER['REQUEST_URI'] ?? 'N/A'),
            'method' => SecurityModule::reflect($_SERVER['REQUEST_METHOD'] ?? 'N/A'),
            'threat' => SecurityModule::reflect($threat),
            'input_sample' => SecurityModule::reflect(substr($input, 0, 500)),
            'input_length' => SecurityModule::reflect(strlen($input)),
        ];

        @file_put_contents(
            $logFile,
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL . "---" . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    // ============================================
    // VALIDATION METHODS (WHITELIST-BASED)
    // ============================================

    /**
     * Validate Name/Surname (Turkish & International support)
     */
    public static function validateName($name, $minLength = 2, $maxLength = 50) {
        if (!is_string($name) || empty($name)) {
            return false;
        }

        $name = trim($name);
        $len = mb_strlen($name, 'UTF-8');

        if ($len < $minLength || $len > $maxLength) {
            return false;
        }

        // WHITELIST: Unicode letters, spaces, hyphens, apostrophes, dots
        if (!preg_match('/^[\p{L}\s\'\-\.]+$/u', $name)) {
            return false;
        }

        // No consecutive special characters
        if (preg_match('/[\'\-\.]{2,}/', $name)) {
            return false;
        }

        // Cannot start or end with special characters
        if (preg_match('/^[\'\-\.\s]|[\'\-\.\s]$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * Validate Email (RFC 5322 compliant)
     */
    public static function validateEmail($email) {
        if (!is_string($email) || empty($email)) {
            return false;
        }

        $email = trim($email);

        // Length check
        if (strlen($email) > 254 || strlen($email) < 3) {
            return false;
        }

        // PHP's built-in filter
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate Integer
     */
    public static function validateInteger($value, $min = null, $max = null) {
        if (!is_int($value) && !is_string($value) && !is_float($value)) {
            return false;
        }

        $strValue = (string)$value;

        // WHITELIST: Only digits with optional leading minus
        if (!preg_match('/^-?\d+$/', $strValue)) {
            return false;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        if ($filtered === false) {
            return false;
        }

        $intValue = (int)$value;

        if ($min !== null && $intValue < $min) {
            return false;
        }

        if ($max !== null && $intValue > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate Float
     */
    public static function validateFloat($value, $min = null, $max = null) {
        if (!is_float($value) && !is_string($value) && !is_int($value)) {
            return false;
        }

        $strValue = (string)$value;

        // WHITELIST: digits, optional minus, optional decimal point
        if (!preg_match('/^-?\d+(\.\d+)?$/', $strValue)) {
            return false;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($filtered === false) {
            return false;
        }

        $floatValue = (float)$value;

        if (!is_finite($floatValue)) {
            return false;
        }

        if ($min !== null && $floatValue < $min) {
            return false;
        }

        if ($max !== null && $floatValue > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate UUID
     */
    public static function validateUUID($uuid)
    {
        return is_string($uuid) && preg_match('/^[0-9a-f]{32}$/', $uuid);
    }

    /**
     * Validate DateTime
     */
    public static function validateDateTime($datetime, $format = 'Y-m-d') {
        if (!is_string($datetime) || empty($datetime)) {
            return false;
        }

        $datetime = trim($datetime);

        // WHITELIST: Only date/time characters
        if (!preg_match('/^[0-9:\-\/\.\sTZ+]+$/', $datetime)) {
            return false;
        }

        $dt = \DateTime::createFromFormat($format, $datetime);

        if (!$dt || $dt->format($format) !== $datetime) {
            return false;
        }

        // Year must be reasonable (1900-2100)
        $year = (int)$dt->format('Y');
        if ($year < 1900 || $year > 2100) {
            return false;
        }

        return true;
    }

    /**
     * WAF Reflect mode - Encode dangerous characters WITHOUT blocking
     */
    public static function reflect($input) {
        if ($input === null || $input === '') {
            return $input;
        }

        if (is_array($input)) {
            $encoded = [];
            foreach ($input as $key => $value) {
                $encoded[$key] = self::reflect($value);
            }
            return $encoded;
        }

        if (is_object($input)) {
            if (method_exists($input, '__toString')) {
                $input = (string)$input;
            } else {
                return '[Object]';
            }
        }

        $input = (string)$input;

        // Simple encoding for safety
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', false);
    }

    // ============================================
    // BLACKLIST HELPER METHODS
    // ============================================

    /**
     * Get the dangerous characters list
     * @return array List of dangerous characters
     */
    public static function getBadCharList() {
        return self::$badCharList;
    }

    /**
     * Check if input contains any character from badCharList
     * Note: This is a blacklist approach - use with caution
     *
     * @param string $input Input to check
     * @return bool True if contains bad characters, false otherwise
     */
    public static function containsBadChars($input) {
        if (!is_string($input)) {
            return false;
        }

        foreach (self::$badCharList as $badChar) {
            if (strpos($input, $badChar) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove all characters from badCharList from input
     * Note: This strips characters - use with caution as it may break legitimate data
     *
     * @param string $input Input to clean
     * @return string Cleaned input
     */
    public static function stripBadChars($input) {
        if (!is_string($input)) {
            return $input;
        }

        return str_replace(self::$badCharList, '', $input);
    }
}

// ============================================
// GLOBAL HELPER FUNCTIONS
// ============================================

/**
 * WAF Check - Default strict mode
 */
function secure($input, $mode = SecurityModule::MODE_STRICT) {
    return SecurityModule::sanitize($input, $mode);
}

/**
 * Alias for secure()
 */
function waf($input, $mode = SecurityModule::MODE_STRICT) {
    return SecurityModule::sanitize($input, $mode);
}

/**
 * WAF Pass-through mode - Minimal blocking
 */
function wafPass($input) {
    return SecurityModule::pass($input, SecurityModule::MODE_PASSTHROUGH);
}

/**
 * WAF Reflect mode - Safe HTML output
 */
function wafReflect($input) {
    return SecurityModule::reflect($input);
}

// ============================================
// VALIDATION HELPER FUNCTIONS
// ============================================

function isValidName($name, $minLength = 2, $maxLength = 50) {
    return SecurityModule::validateName($name, $minLength, $maxLength);
}

function isValidEmail($email) {
    return SecurityModule::validateEmail($email);
}

function isValidInteger($value, $min = null, $max = null) {
    return SecurityModule::validateInteger($value, $min, $max);
}

function isValidFloat($value, $min = null, $max = null) {
    return SecurityModule::validateFloat($value, $min, $max);
}

function isValidUUID($uuid) {
    return SecurityModule::validateUUID($uuid);
}

function isValidDateTime($datetime, $format = 'Y-m-d') {
    return SecurityModule::validateDateTime($datetime, $format);
}
