<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/RiwayatModel.php';

$model = new RiwayatModel($conn);

$search = trim($_GET['search'] ?? '');
$vehicleFilter = trim($_GET['vehicle_id'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');

$canGenerate = $search !== '' && ($dateStart !== '' || $dateEnd !== '');
$rows = $canGenerate ? $model->getAll($search, $vehicleFilter, $dateStart, $dateEnd) : [];

$grouped = [];
foreach ($rows as $row) {
    $vehicleId = (string)($row['vehicle_id'] ?? '');
    $driverNm = (string)($row['driver_nm'] ?? '');
    $tanggal = (string)($row['tanggal'] ?? '');

    $groupKey = $vehicleId . '|' . $driverNm . '|' . $tanggal;
    if (!isset($grouped[$groupKey])) {
        $grouped[$groupKey] = [
            'vehicle_id' => $vehicleId,
            'driver_nm' => $driverNm,
            'tanggal' => $tanggal,
            'items' => [],
            'total_harga' => 0.0,
            'total_qty' => 0
        ];
    }

    $jumlah = (int)($row['jumlah'] ?? 0);
    $totalHarga = (float)($row['total_harga'] ?? 0);
    $grouped[$groupKey]['items'][] = $row;
    $grouped[$groupKey]['total_harga'] += $totalHarga;
    $grouped[$groupKey]['total_qty'] += $jumlah;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice Service</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f7f7f7;
      margin: 0;
      color: #222;
    }
    .toolbar {
      position: sticky;
      top: 0;
      background: #ffffff;
      border-bottom: 1px solid #d9d9d9;
      padding: 12px 18px;
      display: flex;
      gap: 10px;
      z-index: 10;
    }
    .btn {
      display: inline-block;
      border: 1px solid #bdbdbd;
      background: #fff;
      color: #222;
      text-decoration: none;
      border-radius: 6px;
      padding: 8px 12px;
      font-size: 13px;
      cursor: pointer;
    }
    .btn-primary {
      background: #d62828;
      border-color: #d62828;
      color: #fff;
    }
    .wrap {
      max-width: 1100px;
      margin: 14px auto 30px auto;
      padding: 0 12px;
    }
    .invoice {
      background: #fff;
      border: 1px solid #dddddd;
      border-radius: 10px;
      padding: 18px;
      margin-bottom: 18px;
      page-break-after: always;
    }
    .invoice:last-child {
      page-break-after: auto;
    }
    .head {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      border-bottom: 1px dashed #cfcfcf;
      padding-bottom: 10px;
      margin-bottom: 12px;
    }
    .title {
      font-size: 20px;
      font-weight: 700;
      margin: 0 0 4px 0;
    }
    .meta {
      margin: 0;
      font-size: 13px;
      line-height: 1.6;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }
    th, td {
      border: 1px solid #d7d7d7;
      padding: 8px;
      text-align: left;
    }
    th {
      background: #f1f1f1;
    }
    .text-right {
      text-align: right;
    }
    .summary {
      margin-top: 10px;
      display: flex;
      justify-content: flex-end;
      gap: 16px;
      font-size: 13px;
      font-weight: 600;
    }
    .empty {
      background: #fff3cd;
      border: 1px solid #ffe08a;
      color: #6a5300;
      border-radius: 8px;
      padding: 12px;
    }
    @media print {
      body {
        background: #fff;
      }
      .toolbar {
        display: none;
      }
      .wrap {
        margin: 0;
        max-width: none;
      }
      .invoice {
        border: none;
        border-radius: 0;
        padding: 0;
      }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
    <a class="btn" href="index.php?page=riwayat">Kembali</a>
  </div>

  <div class="wrap">
    <?php if (!$canGenerate): ?>
      <div class="empty">
        Tombol cetak invoice aktif jika `search` terisi dan filter tanggal dipilih.
      </div>
    <?php elseif (empty($grouped)): ?>
      <div class="empty">
        Data riwayat tidak ditemukan untuk filter saat ini.
      </div>
    <?php else: ?>
      <?php $invoiceNo = 1; ?>
      <?php foreach ($grouped as $group): ?>
        <section class="invoice">
          <div class="head">
            <div>
              <h1 class="title">Invoice Service</h1>
              <p class="meta">
                No Invoice: <?= e('INV-SRV-' . date('Ymd') . '-' . str_pad((string)$invoiceNo, 3, '0', STR_PAD_LEFT)) ?><br>
                Tanggal Service: <strong><?= e($group['tanggal'] !== '' ? $group['tanggal'] : '-') ?></strong>
              </p>
            </div>
            <p class="meta">
              ID Vehicle: <strong><?= e($group['vehicle_id'] !== '' ? $group['vehicle_id'] : '-') ?></strong><br>
              Driver: <strong><?= e($group['driver_nm'] !== '' ? $group['driver_nm'] : '-') ?></strong>
            </p>
          </div>

          <table>
            <thead>
              <tr>
                <th style="width: 50px;">No</th>
                <th>Barang</th>
                <th style="width: 110px;">Jumlah</th>
                <th style="width: 160px;">Harga Satuan</th>
                <th style="width: 160px;">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php $rowNo = 1; ?>
              <?php foreach ($group['items'] as $item): ?>
                <tr>
                  <td><?= $rowNo ?></td>
                  <td><?= e($item['nama_barang'] ?? '-') ?></td>
                  <td class="text-right"><?= (int)($item['jumlah'] ?? 0) ?></td>
                  <td class="text-right">Rp <?= number_format((float)($item['harga_satuan'] ?? 0), 0, ',', '.') ?></td>
                  <td class="text-right">Rp <?= number_format((float)($item['total_harga'] ?? 0), 0, ',', '.') ?></td>
                </tr>
                <?php $rowNo++; ?>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="summary">
            <span>Total Qty: <?= (int)$group['total_qty'] ?></span>
            <span>Grand Total: Rp <?= number_format((float)$group['total_harga'], 0, ',', '.') ?></span>
          </div>
        </section>
        <?php $invoiceNo++; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
