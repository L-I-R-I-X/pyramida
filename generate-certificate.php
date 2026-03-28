<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$participantName = $_GET['fio'] ?? 'Иванов Иван Иванович';
$nominationName  = $_GET['nomination'] ?? 'Архитектурная композиция';
$sectionName     = $_GET['section'] ?? 'Абстрактная';
$issueDate       = date('d.m.Y');
$certificateType = $_GET['type'] ?? 'certificate'; 
$placeText       = $_GET['place'] ?? ''; 

$basePath = realpath(__DIR__); 

$nominationNames = [
    'arch_composition' => 'Архитектурная композиция',
    'art_graphics' => 'Художественно-проектная графика',
    'nature_drawing' => 'Рисунок с натуры',
    'photography' => 'Фотография'
];

$nominationDisplay = $nominationNames[$nominationName] ?? $nominationName;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->setChroot($basePath);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

if ($certificateType === 'diploma' && $placeText) {
    $docTitle = 'ДИПЛОМ';
    $docText = "занял(а) $placeText место";
} else {
    $docTitle = 'СЕРТИФИКАТ';
    $docText = 'принял(-а) участие';
}

$signatureMaximovaPath = $basePath . '/assets/img/signature_maximova.png';
$signatureZhigadloPath = $basePath . '/assets/img/signature_zhigadlo.png';
$stampPath = $basePath . '/assets/img/stamp.png';

ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">