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

$data = $model->getAll($search, $vehicleFilter, $dateStart, $dateEnd);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// header row
$headers = [
    'ID', 'Tanggal', 'Vehicle ID', 'No Pol', 'Driver', 'Status', 'Kategori',
    'Masa Pakai KM', 'Barang', 'Jumlah', 'Harga Satuan', 'Total Harga', 'Keterangan'
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
    $sheet->setCellValue('C' . $rowNum, $row['vehicle_id']);
    $sheet->setCellValue('D' . $rowNum, $row['nopol']);
    $sheet->setCellValue('E' . $rowNum, $row['driver_nm']);
    $sheet->setCellValue('F' . $rowNum, $row['status']);
    $sheet->setCellValue('G' . $rowNum, $row['kategori']);
    $sheet->setCellValue('H' . $rowNum, $row['masa_pakai_km']);
    $sheet->setCellValue('I' . $rowNum, $row['nama_barang']);
    $sheet->setCellValue('J' . $rowNum, $row['jumlah']);
    $sheet->setCellValue('K' . $rowNum, $row['harga_satuan']);
    $sheet->setCellValue('L' . $rowNum, $row['total_harga']);
    $sheet->setCellValue('M' . $rowNum, $row['keterangan']);
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