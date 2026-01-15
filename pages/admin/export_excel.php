<?php
require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Recupero KPI dal database
$stmt = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM libri) as totale_titoli,
    (SELECT COUNT(*) FROM copie) as copie_fisiche,
    (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as prestiti_attivi,
    (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza = CURDATE()) as scadenza_oggi,
    (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza < CURDATE()) as prestiti_scaduti,
    (SELECT COUNT(*) FROM utenti) as utenti_totali,
    (SELECT COUNT(*) FROM multe WHERE pagata = 0) as multe_totali
")->fetch(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('KPI Bibliotecari');

// Intestazioni
$sheet->setCellValue('A1', 'KPI');
$sheet->setCellValue('B1', 'Valore');

$row = 2;
foreach ($stmt as $label => $val) {
    $sheet->setCellValue("A$row", str_replace('_', ' ', ucfirst($label)));
    $sheet->setCellValue("B$row", $val);
    $row++;
}

// Scarica file Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="report_biblioteca.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
