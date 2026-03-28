<?php
/**
 * CSRF Protection Functions
 */

/**
 * Generate CSRF token and store it in session
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get current CSRF token (without regenerating)
 */
function getCsrfToken() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Validate CSRF token from request
 * @param string|null $token Token from request
 * @return bool True if valid
 */
function validateCsrfToken($token = null) {
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
