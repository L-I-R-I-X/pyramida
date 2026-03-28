<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateCertificate($participantData, $type = 'certificate') {
    // Создаём папки для кэша
    $cacheFontsDir = __DIR__ . '/../cache/fonts';
    $cacheTempDir = __DIR__ . '/../cache/temp';
    
    if (!is_dir($cacheFontsDir)) {
        mkdir($cacheFontsDir, 0755, true);
    }
    if (!is_dir($cacheTempDir)) {
        mkdir($cacheTempDir, 0755, true);
    }
    
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('fontDir', $cacheFontsDir);
    $options->set('fontCache', $cacheFontsDir);
    $options->set('tempDir', $cacheTempDir);
    $options->set('logOutputFile', $cacheTempDir . '/log.htm');
    
    $dompdf = new Dompdf($options);
    
    $nominationNames = [
        'arch_composition' => 'Архитектурная композиция',
        'art_graphics' => 'Художественно-проектная графика',
        'nature_drawing' => 'Рисунок с натуры',
        'photography' => 'Фотография'
    ];
    
    $nomination = $nominationNames[$participantData['nomination']] ?? $participantData['nomination'];
    $section = $participantData['section'] ?? '';
    $fio = $participantData['fio'];
    $vuz = $participantData['vuz'];
    $date = date('d.m.Y');
    
    if ($type === 'diploma') {
        $place = $participantData['place'] ?? 1;
        $placeText = getPlaceText($place);
        $title = 'ДИПЛОМ';
        $subtitle = 'Настоящим дипломом подтверждается, что';
        $achievement = "$fio\n\nзанял(а) $placeText место";
        $competition = 'в I Международном конкурсе по архитектурной графике «Пирамида»';
    } else {
        $title = 'СЕРТИФИКАТ УЧАСТНИКА';
        $subtitle = 'Настоящим сертификатом подтверждается, что';
        $achievement = "$fio\n\nпринял(а) участие";
        $competition = 'в I Международном конкурсе по архитектурной графике «Пирамида»';
    }
    
    // Логотип
    $logoPath = __DIR__ . '/../assets/img/logo.png';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $imageData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
    }
    
    // Подпись (если есть файл)
    $signaturePath = __DIR__ . '/../assets/img/signature.png';
    $signatureHtml = '';
    if (file_exists($signaturePath)) {
        $imageData = file_get_contents($signaturePath);
        $signatureBase64 = 'data:image/png;base64,' . base64_encode($imageData);
        $signatureHtml = '<img src="' . $signatureBase64 . '" class="signature-image" alt="Подпись">';
    }
    
    // Печать (если есть файл)
    $stampPath = __DIR__ . '/../assets/img/stamp.png';
    $stampHtml = '';
    if (file_exists($stampPath)) {
        $imageData = file_get_contents($stampPath);
        $stampBase64 = 'data:image/png;base64,' . base64_encode($imageData);
        $stampHtml = '<img src="' . $stampBase64 . '" class="stamp" alt="Печать">';
    }
    
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            color: #1A1A1A;
        }
        
        .container {
            position: relative;
            width: 210mm;
            height: 297mm;
            padding: 10mm 12mm 12mm 12mm;
            margin: 0 auto;
        }
        
        /* Рамка — строго внутри страницы */
        .border {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 1.5px solid #FF6B00;
            z-index: 1;
        }
        
        /* Логотип в левом верхнем углу */
        .logo {
            position: absolute;
            top: 12mm;
            left: 12mm;
            width: 20mm;
            height: auto;
            z-index: 10;
        }
        
        /* Заголовок */
        .header-block {
            margin-top: 28mm;
            margin-bottom: 10mm;
            text-align: center;
        }
        
        .header-line {
            font-size: 8pt;
            margin: 1px 0;
            line-height: 1.1;
        }
        
        .header-line.bold {
            font-weight: bold;
        }
        
        /* Заголовок ДИПЛОМ / СЕРТИФИКАТ */
        .title {
            font-size: 24pt;
            font-weight: bold;
            text-align: center;
            margin: 10px 0 8px 0;
            color: #FF6B00;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .subtitle {
            font-size: 10pt;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .participant-name {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }
        
        .achievement {
            font-size: 11pt;
            text-align: center;
            margin: 8px 0;
            line-height: 1.4;
        }
        
        .competition {
            font-size: 11pt;
            text-align: center;
            margin: 8px 0;
            font-weight: bold;
        }
        
        .nomination {
            font-size: 9pt;
            text-align: center;
            margin: 10px 0 5px 0;
            color: #444;
        }
        
        /* Footer */
        .footer {
            position: absolute;
            bottom: 12mm;
            left: 15mm;
            right: 15mm;
            z-index: 5;
        }
        
        .date {
            font-size: 9pt;
            margin-bottom: 15px;
        }
        
        .signature-title {
            font-size: 9pt;
            margin-bottom: 5px;
        }
        
        .signature-line {
            display: inline-block;
            border-bottom: 1px solid #1A1A1A;
            padding-bottom: 2px;
            min-width: 60mm;
            margin-right: 10px;
            vertical-align: bottom;
        }
        
        .signature-text {
            font-size: 10pt;
            display: inline-block;
        }
        
        .signature-image {
            width: 40mm;
            height: auto;
            margin-left: 5px;
            vertical-align: bottom;
        }
        
        .stamp {
            position: absolute;
            bottom: 15mm;
            left: 70mm;
            width: 20mm;
            height: auto;
            opacity: 0.7;
            z-index: 3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="border"></div>
        
        <img src="$logoBase64" class="logo" alt="Логотип">
        
        <div class="header-block">
            <div class="header-line bold">Министерство науки и высшего образования Российской Федерации</div>
            <div class="header-line bold">Федеральное государственное бюджетное образовательное учреждение высшего образования</div>
            <div class="header-line bold">«Сибирский автомобильно-дорожный университет» (СибАДИ)</div>
            <div class="header-line">Кафедра «Архитектурно-конструктивное проектирование» (АКП)</div>
        </div>
        
        <div class="title">$title</div>
        
        <div class="subtitle">$subtitle</div>
        
        <div class="participant-name">$fio</div>
        
        <div class="achievement">$achievement</div>
        
        <div class="competition">$competition</div>
        
        <div class="nomination">
            Номинация: $nomination | Раздел: $section | ВУЗ: $vuz
        </div>
        
        <div class="footer">
            <div class="date">Дата выдачи: $date</div>
            
            <div class="signature-title">Зав. кафедрой АКП ФГБОУ ВО «СибАДИ»</div>
            
            <div>
                <span class="signature-line">
                    <span class="signature-text">М. В. Максимова</span>
                </span>
                $signatureHtml
            </div>
            
            $stampHtml
        </div>
    </div>
</body>
</html>
HTML;
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    return $dompdf->output();
}

function getPlaceText($place) {
    $places = [
        1 => 'I (первое)',
        2 => 'II (второе)',
        3 => 'III (третье)'
    ];
    
    return $places[$place] ?? $place . '-е';
}

function downloadCertificate($pdo, $applicationId, $type = 'certificate') {
    require_once __DIR__ . '/functions.php';
    
    if ($type === 'certificate') {
        $showCertificates = getSetting('show_certificates', '0');
        if ($showCertificates !== '1') {
            http_response_code(403);
            die('Доступ запрещён');
        }
    } else {
        $showDiplomas = getSetting('show_diplomas', '0');
        if ($showDiplomas !== '1') {
            http_response_code(403);
            die('Доступ запрещён');
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT id, fio, vuz, course, nomination, section, work_title, is_published, jury_score, created_at 
        FROM applications 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $applicationId]);
    $participant = $stmt->fetch();
    
    if (!$participant) {
        http_response_code(404);
        die('Участник не найден');
    }
    
    if ($type === 'diploma' && !$participant['is_published']) {
        http_response_code(403);
        die('Диплом доступен только для опубликованных работ');
    }
    
    $place = 0;
    if ($type === 'diploma' && $participant['jury_score'] !== null) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) + 1 as place 
            FROM applications 
            WHERE is_published = 1 
            AND nomination = :nomination 
            AND section = :section 
            AND (jury_score > :score OR (jury_score = :score AND created_at < :created_at))
        ");
        $stmt->execute([
            'nomination' => $participant['nomination'],
            'section' => $participant['section'],
            'score' => $participant['jury_score'],
            'created_at' => $participant['created_at']
        ]);
        $placeResult = $stmt->fetch();
        $place = $placeResult['place'] ?? 1;
    }
    
    $participant['place'] = $place;
    
    $pdfContent = generateCertificate($participant, $type);
    
    $filename = $type === 'certificate' 
        ? 'certificate_' . preg_replace('/[^a-zA-Zа-яА-Я0-9]/u', '_', $participant['fio']) . '.pdf'
        : 'diploma_' . preg_replace('/[^a-zA-Zа-яА-Я0-9]/u', '_', $participant['fio']) . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    
    echo $pdfContent;
    exit;
}
?>