<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/DriverModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$model = new DriverModel($conn);
$result = $model->getAll();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = [
    'Code', 'Nama', 'Phone', 'SIM No', 'SIM Class', 'Deposit', 'Join Date', 'Alamat'
];

function colLetter($c) {
    $c--;
    $letter = '';
    while ($c >= 0) {
        $letter = chr(($c % 26) + 65) . $letter;
        $c = intval($c / 26) - 1;
    }
    return $letter;
}

foreach ($headers as $col => $text) {
    $coord = colLetter($col + 1) . '1';
    $sheet->setCellValue($coord, $text);
}

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowNum, $row['code'] ?? '');
    $sheet->setCellValue('B' . $rowNum, $row['name'] ?? '');
    $sheet->setCellValue('C' . $rowNum, $row['phone_no'] ?? '');
    $sheet->setCellValue('D' . $rowNum, $row['sim_no'] ?? '');
    $sheet->setCellValue('E' . $rowNum, $row['sim_class'] ?? '');
    $sheet->setCellValue('F' . $rowNum, $row['deposit'] ?? 0);
    $sheet->setCellValue('G' . $rowNum, $row['join_date'] ?? '');
    $sheet->setCellValue('H' . $rowNum, $row['addr'] ?? '');
    $rowNum++;
}

foreach (range(1, count($headers)) as $colNum) {
    $sheet->getColumnDimensionByColumn($colNum)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$filename = 'driver_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer->save('php://output');
exit;
