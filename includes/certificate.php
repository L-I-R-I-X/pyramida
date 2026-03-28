<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateCertificate($participantData, $type = 'certificate') {
    
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

    $logoPath = __DIR__ . '/../assets/img/logo.png';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $imageData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
    }

    $signaturePath = __DIR__ . '/../assets/img/signature.png';
    $signatureHtml = '';
    if (file_exists($signaturePath)) {
        $imageData = file_get_contents($signaturePath);
        $signatureBase64 = 'data:image/png;base64,' . base64_encode($imageData);
        $signatureHtml = '<img src="' . $signatureBase64 . '" class="signature-image" alt="Подпись">';
    }

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