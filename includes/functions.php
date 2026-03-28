<?php
require_once 'config.php';

function validateFileSignature($filePath) {
    $file = fopen($filePath, 'rb');
    $header = fread($file, 4);
    fclose($file);
    
    $signature = unpack('H*', $header)[1];
    
    $jpegSignatures = [
        'ffd8ffe0',
        'ffd8ffe1',
        'ffd8ffe2',
        'ffd8ffe3',
        'ffd8ffe8',
    ];
    
    foreach ($jpegSignatures as $sig) {
        if (strpos($signature, $sig) === 0) {
            return true;
        }
    }
    
    return false;
}

function generateUniqueFilename($extension) {
    return uniqid('work_', true) . '.' . $extension;
}

function createThumbnail($sourcePath, $destPath, $maxWidth = 800) {
    $imageInfo = getimagesize($sourcePath);
    
    if (!$imageInfo) {
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    
    if ($originalWidth <= $maxWidth) {
        copy($sourcePath, $destPath);
        return true;
    }
    
    $ratio = $maxWidth / $originalWidth;
    $newWidth = $maxWidth;
    $newHeight = (int)($originalHeight * $ratio);
    
    $source = imagecreatefromjpeg($sourcePath);
    
    if (!$source) {
        return false;
    }
    
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    imagecopyresampled(
        $thumbnail,
        $source,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $originalWidth,
        $originalHeight
    );
    
    imagejpeg($thumbnail, $destPath, 85);
    
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return true;
}

function sanitizeInput($data) {
    return trim($data);
}

function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        session_start();
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

function getSetting($key, $default = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function updateSetting($key, $value) {
    global $pdo;
    
    try {
        
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (:skey, :svalue)
            ON DUPLICATE KEY UPDATE setting_value = :uvalue, updated_at = NOW()
        ");
        
        return $stmt->execute([
            'skey' => $key,
            'svalue' => $value,
            'uvalue' => $value
        ]);
    } catch (PDOException $e) {
        error_log('updateSetting error: ' . $e->getMessage());
        return false;
    }
}

function validateImageDimensions($filePath, $maxWidth = 5000, $maxHeight = 5000) {
    $imageInfo = getimagesize($filePath);
    
    if (!$imageInfo) {
        return false;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    if ($width > $maxWidth || $height > $maxHeight) {
        return false;
    }
    
    return true;
}

function getNominationName($code) {
    $names = [
        'arch_composition' => 'Архитектурная композиция',
        'art_graphics' => 'Художественно-проектная графика',
        'nature_drawing' => 'Рисунок с натуры',
        'photography' => 'Фотография'
    ];
    
    return $names[$code] ?? $code;
}

function getPlaceInNomination($pdo, $nomination, $section, $currentScore, $currentId) {
    try {
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) + 1 as place 
            FROM applications 
            WHERE is_published = 1 
            AND nomination = :nomination 
            AND section = :section 
            AND (
                jury_score > :score 
                OR (jury_score = :score AND (created_at < :created_at OR id < :id))
            )
        ");
        $stmt->execute([
            'nomination' => $nomination,
            'section' => $section,
            'score' => $currentScore,
            'created_at' => $currentId, 
            'id' => $currentId
        ]);
        $result = $stmt->fetch();
        return $result['place'] ?? 1;
    } catch (PDOException $e) {
        return 0;
    }
}

function getPlaceText($place) {
    $places = [
        1 => 'I (первое)',
        2 => 'II (второе)',
        3 => 'III (третье)'
    ];
    return $places[$place] ?? $place . '-е';
}