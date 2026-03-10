<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/InventoriModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$model = new InventoriModel($conn);

$search = trim($_GET['search'] ?? '');
$stokFilter = trim($_GET['stok_filter'] ?? '');
if (!in_array($stokFilter, ['aman', 'peringatan'], true)) {
    $stokFilter = '';
}
$kategoriFilter = trim($_GET['kategori'] ?? '');
$data = $model->getAll($search, $stokFilter, $kategoriFilter);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = [
    'Nama', 'Kategori', 'Stok', 'Peringatan Stok', 'Harga Satuan', 'Masa Pakai (KM)'
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
foreach ($data as $row) {
    $sheet->setCellValue('A' . $rowNum, $row['nama']);
    $sheet->setCellValue('B' . $rowNum, $row['kategori']);
    $sheet->setCellValue('C' . $rowNum, $row['stok']);
    $sheet->setCellValue('D' . $rowNum, $row['peringatan_stok']);
    $sheet->setCellValue('E' . $rowNum, $row['harga_satuan']);
    $sheet->setCellValue('F' . $rowNum, $row['masa_pakai']);
    $rowNum++;
}

foreach (range(1, count($headers)) as $colNum) {
    $sheet->getColumnDimensionByColumn($colNum)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$filename = 'inventory_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer->save('php://output');
exit;
