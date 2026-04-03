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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if ($message) {
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
        // Проверяем, существует ли запись
        $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Обновляем существующую запись
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = :svalue, updated_at = NOW() WHERE setting_key = :key");
            return $stmt->execute(['skey' => $key, 'svalue' => $value, 'key' => $key]);
        } else {
            // Вставляем новую запись
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:skey, :svalue)");
            return $stmt->execute(['skey' => $key, 'svalue' => $value]);
        }
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
        // Получаем всех опубликованных участников в той же номинации и разделе с оценками
        $stmt = $pdo->prepare("
            SELECT id, jury_score, created_at 
            FROM applications 
            WHERE is_published = 1 
            AND nomination = :nomination 
            AND section = :section
            AND jury_score IS NOT NULL
            ORDER BY jury_score DESC, created_at ASC, id ASC
        ");
        $stmt->execute([
            'nomination' => $nomination,
            'section' => $section
        ]);
        $results = $stmt->fetchAll();
        
        // Находим место текущего участника
        $place = 0;
        foreach ($results as $index => $row) {
            if ($row['id'] == $currentId) {
                $place = $index + 1;
                break;
            }
        }
        
        return $place > 0 ? $place : 1;
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