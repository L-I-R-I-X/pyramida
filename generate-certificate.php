<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Входные данные (можно получать из базы данных)
$participantName = $_GET['fio'] ?? 'Иванов Иван Иванович';
$nominationName  = $_GET['nomination'] ?? 'Архитектурная композиция';
$sectionName     = $_GET['section'] ?? 'Абстрактная';
$issueDate       = date('d.m.Y');
$certificateType = $_GET['type'] ?? 'certificate'; // 'certificate' или 'diploma'
$placeText       = $_GET['place'] ?? ''; // 'I (первое)', 'II (второе)', и т.д.

// Абсолютный путь к директории скрипта
$basePath = realpath(__DIR__); 

// ✅ Массив для преобразования кодов номинаций в читаемые названия
$nominationNames = [
    'arch_composition' => 'Архитектурная композиция',
    'art_graphics' => 'Художественно-проектная графика',
    'nature_drawing' => 'Рисунок с натуры',
    'photography' => 'Фотография'
];

// ✅ Преобразуем код номинации в читаемое название
$nominationDisplay = $nominationNames[$nominationName] ?? $nominationName;

// Настройки Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->setChroot($basePath);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

// Текст в зависимости от типа документа
if ($certificateType === 'diploma' && $placeText) {
    $docTitle = 'ДИПЛОМ';
    $docText = "занял(а) $placeText место";
} else {
    $docTitle = 'СЕРТИФИКАТ';
    $docText = 'принял(-а) участие';
}

// Пути к файлам подписей
$signatureMaximovaPath = $basePath . '/assets/img/signature_maximova.png';
$signatureZhigadloPath = $basePath . '/assets/img/signature_zhigadlo.png';
$stampPath = $basePath . '/assets/img/stamp.png';

// Начинаем сборку HTML
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
        
        /* Рамка — 15мм от края */
        .frame {
            position: absolute;
            top: 15mm;
            left: 15mm;
            right: 15mm;
            bottom: 15mm;
            border: 2px solid #FF6B00;
            box-sizing: border-box;
        }

        /* Верхняя часть: тёмно-серый фон — минимальные отступы */
        .header {
            background-color: #1A1A1A;
            color: #FFFFFF;
            padding: 8mm 15mm 6mm 15mm;
            position: relative;
        }
        
        .logo {
            position: absolute;
            top: 10mm;
            left: 17mm;
            width: 30mm;
            height: auto;
        }

        .header-text {
            text-align: center;
            font-size: 9pt;
            line-height: 1.2;
            margin-left: 45mm; 
            margin-right: 15mm;
            margin-top: 1mm;
        }

        /* ЗАГОЛОВОК: 42pt */
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

        /* Оранжевый разделитель */
        .separator {
            height: 2px;
            background-color: #FF6B00;
            margin: 0 40mm 4mm 40mm;
        }

        /* Основной контент */
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

        /* ✅ Минимальный отступ у номинации */
        .nomination {
            font-size: 12pt;
            text-align: left;
            margin: 3mm 0 3mm 0; /* Минимальный отступ снизу */
            color: #444;
            line-height: 1.5;
        }

        /* Подвал — ещё выше */
        .footer {
            position: absolute;
            bottom: 38mm; /* Ещё выше */
            left: 20mm;
            right: 20mm;
        }

        .signatures-container {
            display: flex;
            justify-content: space-between;
            gap: 5mm; /* Минимальный зазор между подписями */
            margin-bottom: 5mm;
        }

        .signature-block {
            flex: 1;
            text-align: left;
        }

        /* ✅ Минимальные отступы в подписях */
        .signature-title {
            font-size: 9pt;
            font-weight: 600;
            margin-bottom: 0; /* Убран отступ */
            line-height: 1.1;
        }

        .signature-degree {
            font-size: 8pt;
            color: #666;
            font-style: italic;
            margin-bottom: 1mm; /* Минимальный отступ */
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

        /* Печать */
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
                <!-- Подпись 1: Максимова М. В. -->
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
                
                <!-- Подпись 2: Жигадло А. П. -->
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

// Скачивание файла
$filename = ($certificateType === 'diploma' ? 'Diploma' : 'Certificate') . '_' . 
            preg_replace('/[^a-zA-Zа-яА-Я0-9]/u', '_', $participantName) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
echo $dompdf->output();
exit;
?>