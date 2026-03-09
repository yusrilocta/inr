<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/RiwayatModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$model = new RiwayatModel($conn);

// same filter parameters as riwayat page
$search = trim($_GET['search'] ?? '');
$vehicleFilter = trim($_GET['vehicle_id'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd   = trim($_GET['date_end'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$allowedStatus = ['jadwal', 'pending', 'sedang dikerjakan', 'siap operasi', 'selesai'];
if (!in_array(strtolower($statusFilter), $allowedStatus, true)) {
    $statusFilter = '';
}

$data = $model->getParentExportList($search, $vehicleFilter, $dateStart, $dateEnd, $statusFilter);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// header row
$headers = [
    'ID', 'Tanggal', 'No Pol', 'Driver', 'Status', 'Kategori',
    'Masa Pakai KM', 'Item List', 'Total Qty', 'Total Harga', 'Keterangan'
];
// helper to convert 1-based column number to Excel letter
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
foreach ($data as $row) {
    $sheet->setCellValue('A' . $rowNum, $row['id']);
    $sheet->setCellValue('B' . $rowNum, $row['tanggal']);
    $sheet->setCellValue('C' . $rowNum, $row['nopol']);
    $sheet->setCellValue('D' . $rowNum, $row['driver_nm']);
    $sheet->setCellValue('E' . $rowNum, $row['status']);
    $sheet->setCellValue('F' . $rowNum, $row['kategori']);
    $sheet->setCellValue('G' . $rowNum, $row['masa_pakai_km']);
    $sheet->setCellValue('H' . $rowNum, $row['item_list']);
    $sheet->setCellValue('I' . $rowNum, $row['total_qty']);
    $sheet->setCellValue('J' . $rowNum, $row['total_harga']);
    $sheet->setCellValue('K' . $rowNum, $row['keterangan']);
    $rowNum++;
}

// auto-size columns
foreach (range(1, count($headers)) as $colNum) {
    $sheet->getColumnDimensionByColumn($colNum)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$filename = 'riwayat_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer->save('php://output');
exit;
