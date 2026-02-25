<?php
/**
 * Advanced Security System
 * - DDoS Protection (Rate Limiting)
 * - Anti-Bot Detection
 * - SQL Injection Prevention
 * - XSS Protection
 * - CSRF Protection
 */

class Security {
    private $db;
    private $max_requests = 60; // Max requests per minute
    private $ban_duration = 3600; // Ban for 1 hour
    
    public function __construct($database = null) {
        $this->db = $database;
    }
    
    /**
     * Initialize security checks
     */
    public function init() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if IP is banned
        if ($this->isIPBanned()) {
            $this->blockAccess('Your IP has been temporarily banned due to suspicious activity');
        }
        
        // Rate limiting (DDoS protection)
        if (!$this->checkRateLimit()) {
            $this->banIP();
            $this->blockAccess('Too many requests. Please try again later.');
        }
        
        // Bot detection
        if ($this->isBot()) {
            $this->logSuspiciousActivity('Bot detected');
            if (!$this->isGoodBot()) {
                $this->blockAccess('Bot access denied');
            }
        }
        
        // Check for common attack patterns
        $this->detectAttackPatterns();
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Rate Limiting - DDoS Protection
     */
    private function checkRateLimit() {
        $ip = $this->getIP();
        $key = 'rate_limit_' . md5($ip);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => time()
            ];
            return true;
        }
        
        $data = $_SESSION[$key];
        $elapsed = time() - $data['start_time'];
        
        // Reset counter after 60 seconds
        if ($elapsed > 60) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => time()
            ];
            return true;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        
        // Check if exceeded limit
        if ($data['count'] > $this->max_requests) {
            $this->logSuspiciousActivity("Rate limit exceeded: {$data['count']} requests");
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if IP is banned
     */
    private function isIPBanned() {
        $ip = $this->getIP();
        $ban_file = __DIR__ . '/../storage/banned_ips.json';
        
        if (!file_exists($ban_file)) {
            return false;
        }
        
        $banned_ips = json_decode(file_get_contents($ban_file), true) ?: [];
        
        if (isset($banned_ips[$ip])) {
            // Check if ban expired
            if (time() > $banned_ips[$ip]['expires']) {
                unset($banned_ips[$ip]);
                file_put_contents($ban_file, json_encode($banned_ips));
                return false;
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Ban IP address
     */
    private function banIP() {
        $ip = $this->getIP();
        $ban_file = __DIR__ . '/../storage/banned_ips.json';
        
        // Create storage directory if not exists
        $storage_dir = __DIR__ . '/../storage';
        if (!file_exists($storage_dir)) {
            mkdir($storage_dir, 0755, true);
        }
        
        $banned_ips = file_exists($ban_file) ? json_decode(file_get_contents($ban_file), true) : [];
        
        $banned_ips[$ip] = [
            'banned_at' => time(),
            'expires' => time() + $this->ban_duration,
            'reason' => 'Rate limit exceeded'
        ];
        
        file_put_contents($ban_file, json_encode($banned_ips, JSON_PRETTY_PRINT));
    }
    
    /**
     * Bot Detection
     */
    private function isBot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Common bot patterns
        $bot_patterns = [
            'bot', 'crawl', 'spider', 'slurp', 'scraper', 'curl', 'wget',
            'python', 'java', 'perl', 'ruby', 'scrapy', 'mechanize'
        ];
        
        foreach ($bot_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                return true;
            }
        }
        
        // Check for missing user agent
        if (empty($user_agent)) {
            return true;
        }
        
        // Check for suspicious headers
        if (!isset($_SERVER['HTTP_ACCEPT']) || !isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if good bot (Google, Bing, etc)
     */
    private function isGoodBot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $good_bots = ['googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot', 'facebookexternalhit'];
        
        foreach ($good_bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect attack patterns in input
     */
    private function detectAttackPatterns() {
        $inputs = array_merge($_GET, $_POST, $_COOKIE);
        
        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                // SQL Injection patterns
                if ($this->detectSQLInjection($value)) {
                    $this->logSuspiciousActivity("SQL Injection attempt in $key: $value");
                    $this->banIP();
                    $this->blockAccess('Malicious request detected');
                }
                
                // XSS patterns
                if ($this->detectXSS($value)) {
                    $this->logSuspiciousActivity("XSS attempt in $key: $value");
                }
            }
        }
    }
    
    /**
     * Detect SQL Injection
     */
    private function detectSQLInjection($input) {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(;|\-\-|\/\*|\*\/)/i',
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i',
            '/(\'|\"|\`)(.*)(\'|\"|\`)/i',
            '/(\bhex\b|\bchar\b|\bconcat\b)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect XSS
     */
    private function detectXSS($input) {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/i',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/i',
            '/javascript:/i',
            '/on\w+\s*=\s*["\'][^"\']*["\']/i',
            '/<object\b[^>]*>/i',
            '/<embed\b[^>]*>/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            $this->logSuspiciousActivity('CSRF token mismatch');
            return false;
        }
        return true;
    }
    
    /**
     * Generate CSRF token
     */
    public function getCsrfToken() {
        return $_SESSION['csrf_token'] ?? '';
    }
    
    /**
     * Get real IP address
     */
    private function getIP() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // CloudFlare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity($message) {
        $log_file = __DIR__ . '/../storage/security_log.txt';
        $storage_dir = __DIR__ . '/../storage';
        
        if (!file_exists($storage_dir)) {
            mkdir($storage_dir, 0755, true);
        }
        
        $ip = $this->getIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $timestamp = date('Y-m-d H:i:s');
        
        $log_entry = "[$timestamp] IP: $ip | UA: $user_agent | URI: $request_uri | $message\n";
        
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Block access with message
     */
    private function blockAccess($message) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .error-box { text-align: center; padding: 40px; background: #2a2a2a; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        h1 { color: #d4af37; font-size: 48px; margin: 0 0 20px; }
        p { font-size: 18px; color: #aaa; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>⛔ ACCESS DENIED</h1>
        <p>' . htmlspecialchars($message) . '</p>
    </div>
</body>
</html>';
        exit;
    }
    
    /**
     * Sanitize input (enhanced)
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
            return $data;
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }
}
