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
    <style>
        @page {
            margin: 0; 
            size: A4;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            font-size: 10pt;
            line-height: 1.3;
        }
        
        .frame {
            position: absolute;
            top: 15mm;
            left: 15mm;
            right: 15mm;
            bottom: 15mm;
            border: 2px solid #FF6B00;
            box-sizing: border-box;
        }

        .header {
            background-image: url('<?= $basePath ?>/assets/img/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #FFFFFF;
            padding: 8mm 15mm 6mm 15mm;
            position: relative;
        }
        
        .header-text {
            text-align: center;
            font-size: 9pt;
            line-height: 1.2;
            margin-left: 45mm; 
            margin-right: 15mm;
            margin-top: 1mm;
            color: #FFFFFF;
        }

        .logo {
            position: absolute;
            top: 10mm;
            left: 17mm;
            width: 30mm;
            height: auto;
        }

        .title {
            text-align: center;
            color: #FF6B00;
            font-size: 42pt;
            font-weight: bold;
            margin: 2mm 0 3mm 0;
            letter-spacing: 3px;
            text-transform: uppercase;
            line-height: 1;
        }

        .separator {
            height: 2px;
            background-color: #FF6B00;
            margin: 0 40mm 4mm 40mm;
        }

        .content {
            padding: 0 25mm;
            text-align: center;
        }

        .intro {
            font-size: 11pt;
            margin-bottom: 3mm;
        }

        .name {
            font-size: 16pt;
            font-weight: bold;
            margin: 3mm 0;
        }

        .achievement {
            font-size: 12pt;
            margin: 2mm 0;
            line-height: 1.3;
        }

        .competition {
            font-size: 14pt;
            font-weight: bold;
            margin: 2mm 0 4mm 0;
        }

        .nomination {
            font-size: 12pt;
            text-align: left;
            margin: 3mm 0 3mm 0;
            color: #444;
            line-height: 1.5;
        }

        .footer {
            position: absolute;
            bottom: 38mm;
            left: 20mm;
            right: 20mm;
        }

        .signatures-container {
            display: flex;
            justify-content: space-between;
            gap: 5mm;
            margin-bottom: 5mm;
        }

        .signature-block {
            flex: 1;
            text-align: left;
        }

        .signature-title {
            font-size: 9pt;
            font-weight: 600;
            margin-bottom: 0;
            line-height: 1.1;
        }

        .signature-degree {
            font-size: 8pt;
            color: #666;
            font-style: italic;
            margin-bottom: 1mm;
            display: block;
        }

        .signature-name-line {
            display: flex;
            align-items: flex-end;
            gap: 1mm;
        }

        .signature-name {
            font-size: 10pt;
            display: inline-block;
        }

        .signature-underscore {
            font-size: 12pt;
            color: #1A1A1A;
            letter-spacing: 0;
            display: inline-block;
            flex: 1;
            border-bottom: 1px solid #1A1A1A;
            padding-bottom: 0;
        }

        .signature-img {
            height: 9mm;
            margin-left: 1mm;
            vertical-align: bottom;
            display: inline-block;
        }

        .stamp-img {
            position: absolute;
            bottom: 48mm;
            left: 135mm;
            width: 40mm;
            height: auto;
            opacity: 0.75;
            z-index: 1;
        }

        .date {
            font-size: 10pt;
            text-align: left;
            margin-top: 2mm;
        }
    </style>
</head>
<body>
    <div class="frame">
        <div class="header">
            <img src="<?= $basePath ?>/assets/img/logo.png" class="logo" alt="Logo">
            
            <div class="header-text">
                Министерство науки и высшего образования Российской Федерации<br>
                Федеральное государственное бюджетное образовательное учреждение высшего образования<br>
                «Сибирский автомобильно-дорожный университет» (СибАДИ)<br>
                Кафедра «Архитектурно-конструктивное проектирование» (АКП)
            </div>
            
            <div class="title"><?= $docTitle ?></div>
        </div>
        
        <div class="separator"></div>

        <div class="content">
            <div class="intro">
                Настоящим <?= $certificateType === 'diploma' ? 'дипломом' : 'сертификатом' ?> подтверждается, что
            </div>
            
            <div class="name">
                <?= htmlspecialchars($participantName) ?>
            </div>
            
            <div class="achievement">
                <?= $docText ?>
            </div>
            
            <div class="competition">
                в I Международном конкурсе по архитектурной графике и онлайн-выставке<br>
                творческих работ и проектов «Пирамида»
            </div>
            
            <div class="nomination">
                Номинация: <?= htmlspecialchars($nominationDisplay) ?><br>
                Раздел: <?= htmlspecialchars($sectionName) ?>
            </div>
        </div>

        <div class="footer">
            <div class="signatures-container">
                <div class="signature-block">
                    <div class="signature-title">
                        Заведующая кафедрой АКП ФГБОУ ВО «СибАДИ»<br>
                        к.т.н., член Союза дизайнеров России
                    </div>
                    <div class="signature-name-line">
                        <span class="signature-name">М. В. Максимова</span>
                        <span class="signature-underscore"></span>
                        <?php if (file_exists($signatureMaximovaPath)): ?>
                            <img src="<?= $basePath ?>/assets/img/signature_maximova.png" class="signature-img" alt="Подпись Максимовой М. В.">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="signature-block">
                    <br>
                    <br>
                </div>
                
                <div class="signature-block">
                    <div class="signature-title">
                        Ректор ФГБОУ ВО «СибАДИ»
                    </div>
                    <div class="signature-name-line">
                        <span class="signature-name">А. П. Жигадло</span>
                        <span class="signature-underscore"></span>
                        <?php if (file_exists($signatureZhigadloPath)): ?>
                            <img src="<?= $basePath ?>/assets/img/signature_zhigadlo.png" class="signature-img" alt="Подпись Жигадло А. П.">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
                <div class="signature-block">
                    <br>
                    <br>
                </div>

            <div class="date">
                Дата выдачи: <?= htmlspecialchars($issueDate) ?>
            </div>
        </div>
    </div>
    
    <?php if (file_exists($stampPath)): ?>
        <img src="<?= $basePath ?>/assets/img/stamp.png" class="stamp-img" alt="Печать">
    <?php endif; ?>
</body>
</html>
<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = ($certificateType === 'diploma' ? 'Diploma' : 'Certificate') . '_' . 
            preg_replace('/[^a-zA-Zа-яА-Я0-9]/u', '_', $participantName) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
echo $dompdf->output();
exit;
?>