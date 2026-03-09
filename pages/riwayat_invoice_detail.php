<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/RiwayatModel.php';

$model = new RiwayatModel($conn);
$id = (int)($_GET['id'] ?? 0);
$detail = $id > 0 ? $model->getById($id) : null;

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
  <title>Invoice Detail Riwayat</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; color: #222; }
    .toolbar { position: sticky; top: 0; background: #fff; border-bottom: 1px solid #d9d9d9; padding: 12px 18px; display: flex; gap: 10px; z-index: 10; }
    .btn { display: inline-block; border: 1px solid #bdbdbd; background: #fff; color: #222; text-decoration: none; border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer; }
    .btn-primary { background: #d62828; border-color: #d62828; color: #fff; }
    .wrap { max-width: 1100px; margin: 14px auto 30px auto; padding: 0 12px; }
    .invoice { background: #fff; border: 1px solid #dddddd; border-radius: 10px; padding: 18px; }
    .head { display: flex; justify-content: space-between; gap: 16px; border-bottom: 1px dashed #cfcfcf; padding-bottom: 10px; margin-bottom: 12px; }
    .title { font-size: 20px; font-weight: 700; margin: 0 0 4px 0; }
    .meta { margin: 0; font-size: 13px; line-height: 1.6; }
    .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 8px; margin-bottom: 12px; }
    .meta-card { border: 1px solid #d7d7d7; border-radius: 8px; padding: 10px; background: #fafafa; font-size: 13px; }
    .meta-card h3 { margin: 0 0 8px 0; font-size: 13px; text-transform: uppercase; color: #5a5a5a; letter-spacing: .4px; }
    .meta-line { display: flex; justify-content: space-between; gap: 10px; margin: 3px 0; }
    .meta-label { color: #666; }
    .meta-value { font-weight: 600; text-align: right; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { border: 1px solid #d7d7d7; padding: 8px; text-align: left; vertical-align: top; }
    th { background: #f1f1f1; }
    .text-right { text-align: right; }
    .summary { margin-top: 10px; display: flex; justify-content: flex-end; gap: 16px; font-size: 13px; font-weight: 600; }
    .empty { background: #fff3cd; border: 1px solid #ffe08a; color: #6a5300; border-radius: 8px; padding: 12px; }
    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .wrap { margin: 0; max-width: none; }
      .invoice { border: none; border-radius: 0; padding: 0; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <button class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
    <a class="btn" href="index.php?page=riwayat">Kembali</a>
  </div>

  <div class="wrap">
    <?php if (!$detail): ?>
      <div class="empty">Data riwayat tidak ditemukan.</div>
    <?php else: ?>
      <?php
        $invoiceNo = 'INV-RWY-' . date('Ymd') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        $createdAt = date('Y-m-d H:i:s');
        $tglMenunggu = $detail['tgl_menunngu'] ?? ($detail['tgl_menunggu'] ?? '');
        $items = $detail['items'] ?? [];
        $totalQty = 0;
        $totalHarga = 0.0;
        foreach ($items as $item) {
            $totalQty += (int)($item['jumlah'] ?? 0);
            $totalHarga += (float)($item['total_harga'] ?? 0);
        }
      ?>
      <section class="invoice">
        <div class="head">
          <div>
            <h1 class="title">Invoice Detail Riwayat</h1>
            <p class="meta">Dokumen detail riwayat service per transaksi</p>
          </div>
        </div>

        <div class="meta-grid">
          <div class="meta-card">
            <h3>Detail Kendaraan</h3>
            <div class="meta-line"><span class="meta-label">No Polisi:</span><span class="meta-value"><?= e($detail['nopol'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Driver:</span><span class="meta-value"><?= e($detail['driver_nm'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Referensi:</span><span class="meta-value"><?= e('RWY-' . ($detail['id'] ?? '-')) ?></span></div>
            <div class="meta-line"><span class="meta-label">Status:</span><span class="meta-value"><?= e($detail['status'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Kategori:</span><span class="meta-value"><?= e($detail['kategori'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Total KM:</span><span class="meta-value"><?= e((string)($detail['total_km'] ?? 0)) ?> KM</span></div>
            <div class="meta-line"><span class="meta-label">KM Service Terakhir:</span><span class="meta-value"><?= e((string)($detail['last_km_service'] ?? 0)) ?> KM</span></div>
            <div class="meta-line"><span class="meta-label">Masa Pakai:</span><span class="meta-value"><?= e((string)($detail['masa_pakai_km'] ?? 0)) ?> KM</span></div>
          </div>

          <div class="meta-card">
            <h3>Detail Tanggal & Dokumen</h3>
            <div class="meta-line"><span class="meta-label">No Invoice:</span><span class="meta-value"><?= e($invoiceNo) ?></span></div>
            <div class="meta-line"><span class="meta-label">Dibuat :</span><span class="meta-value"><?= e($createdAt) ?></span></div>
            <div class="meta-line"><span class="meta-label">Tanggal :</span><span class="meta-value"><?= e($detail['tanggal'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Tgl Sedang Dikerjakan:</span><span class="meta-value"><?= e(($detail['tgl_sedang_dikerjakan'] ?? '') !== '' ? $detail['tgl_sedang_dikerjakan'] : '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Tgl Siap Operasi:</span><span class="meta-value"><?= e(($detail['tgl_siap_operasi'] ?? '') !== '' ? $detail['tgl_siap_operasi'] : '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Tgl Selesai:</span><span class="meta-value"><?= e(($detail['tgl_selesai'] ?? '') !== '' ? $detail['tgl_selesai'] : '-') ?></span></div>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th style="width: 50px;">No</th>
              <th style="width: 90px;">ID Item</th>
              <th>Barang</th>
              <th style="width: 100px;">Jumlah</th>
              <th style="width: 150px;">Harga Satuan</th>
              <th style="width: 150px;">Total</th>
              <th style="width: 100px;">Mekanik</th>
              <th style="width: 80px;">Tools</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr>
                <td colspan="8">Tidak ada item service.</td>
              </tr>
            <?php else: ?>
              <?php $no = 1; ?>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td><?= $no ?></td>
                  <td><?= (int)($item['id'] ?? 0) ?></td>
                  <td><?= e($item['nama_barang'] ?? '-') ?></td>
                  <td class="text-right"><?= (int)($item['jumlah'] ?? 0) ?></td>
                  <td class="text-right">Rp <?= number_format((float)($item['harga_satuan'] ?? 0), 0, ',', '.') ?></td>
                  <td class="text-right">Rp <?= number_format((float)($item['total_harga'] ?? 0), 0, ',', '.') ?></td>
                  <td><?= e((string)($item['mekanik_id'] ?? '-')) ?></td>
                  <td><?= e($item['tools'] ?? '-') ?></td>
                </tr>
                <?php $no++; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="summary">
          <span>Total Qty: <?= (int)$totalQty ?></span>
          <span>Grand Total: Rp <?= number_format((float)$totalHarga, 0, ',', '.') ?></span>
        </div>

        <div style="margin-top:10px;font-size:13px;">
          <strong>Keterangan:</strong> <?= e(($detail['keterangan'] ?? '') !== '' ? $detail['keterangan'] : '-') ?>
        </div>
      </section>
    <?php endif; ?>
  </div>
</body>
</html>
