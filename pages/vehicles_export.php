<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/VehiclesModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$model = new VehiclesModel($conn);

$search = trim($_GET['search'] ?? '');
$result = $model->getAll($search);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = [
    'Vehicle ID', 'No Pol', 'Brand', 'Model', 'Type', 'Driver',
    'Total KM', 'Service Terakhir', 'KM Service'
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
    $sheet->setCellValue('A' . $rowNum, $row['vehicle_id'] ?? '');
    $sheet->setCellValue('B' . $rowNum, $row['nopol'] ?? '');
    $sheet->setCellValue('C' . $rowNum, $row['brand'] ?? '');
    $sheet->setCellValue('D' . $rowNum, $row['model'] ?? '');
    $sheet->setCellValue('E' . $rowNum, $row['type'] ?? '');
    $sheet->setCellValue('F' . $rowNum, $row['driver_nm'] ?? '');
    $sheet->setCellValue('G' . $rowNum, $row['total_km'] ?? '');
    $sheet->setCellValue('H' . $rowNum, $row['last_service'] ?? '');
    $sheet->setCellValue('I' . $rowNum, ($row['total_km'] ?? 0) - ($row['last_km_service'] ?? 0));
    $rowNum++;
}

foreach (range(1, count($headers)) as $colNum) {
    $sheet->getColumnDimensionByColumn($colNum)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$filename = 'vehicles_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer->save('php://output');
exit;
