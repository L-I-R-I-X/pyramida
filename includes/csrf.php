<?php
/**
 * CSRF Protection Functions
 * 
 * Примечание: Эти функции предполагают, что сессия УЖЕ запущена
 * (через auth.php или напрямую). Они НЕ пытаются запустить сессию самостоятельно.
 */

/**
 * Generate CSRF token and store it in session
 */
function generateCsrfToken() {
    // Сессия должна быть уже запущена
    if (session_status() === PHP_SESSION_NONE) {
        // Это ошибка - сессия должна быть запущена до вызова этой функции
        error_log('CSRF: session not started before generateCsrfToken()');
        return '';
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get current CSRF token (without regenerating)
 */
function getCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        error_log('CSRF: session not started before getCsrfToken()');
        return '';
    }
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Validate CSRF token from request
 * @param string|null $token Token from request
 * @return bool True if valid
 */
function validateCsrfToken($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        error_log('CSRF: session not started before validateCsrfToken()');
        return false;
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output hidden CSRF token field for forms
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * Require valid CSRF token or terminate with error
 */
function requireCsrfValidation() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die('Ошибка безопасности: недействительный CSRF токен');
    }
}
?>
