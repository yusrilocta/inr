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

function editableText($value, $className = '')
{
    $classAttr = trim('editable-text ' . $className);
    return '<span class="' . e($classAttr) . '" contenteditable="true" spellcheck="false">' . e($value) . '</span>';
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
    .toolbar { position: sticky; top: 0; background: #fff; border-bottom: 1px solid #d9d9d9; padding: 12px 18px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px; z-index: 10; }
    .btn { display: inline-block; border: 1px solid #bdbdbd; background: #fff; color: #222; text-decoration: none; border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer; }
    .btn-primary { background: #d62828; border-color: #d62828; color: #fff; }
    .btn-danger { background: #fff4f4; border-color: #d62828; color: #b71919; }
    .toolbar-note { color: #555; font-size: 13px; }
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
    .editable-text, .editable-cell { display: inline-block; min-width: 18px; border-radius: 4px; outline: 1px dashed transparent; }
    .editable-text { max-width: 100%; }
    .editable-cell { display: block; min-height: 18px; }
    .editable-text:hover, .editable-cell:hover,
    .editable-text:focus, .editable-cell:focus { background: #fff8d8; outline-color: #e3b505; }
    .row-actions { text-align: center; width: 78px; }
    .summary-value { min-width: 78px; text-align: right; }
    .summary { margin-top: 10px; display: flex; justify-content: flex-end; gap: 16px; font-size: 13px; font-weight: 600; }
    .empty { background: #fff3cd; border: 1px solid #ffe08a; color: #6a5300; border-radius: 8px; padding: 12px; }
    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .no-print { display: none !important; }
      .wrap { margin: 0; max-width: none; }
      .invoice { border: none; border-radius: 0; padding: 0; }
      .editable-text, .editable-cell { outline: none !important; background: transparent !important; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <button class="btn btn-primary" type="button" id="printInvoiceBtn">Cetak / Simpan PDF</button>
    <button class="btn" type="button" id="addItemBtn">Tambah Baris</button>
    <button class="btn" type="button" id="recalcBtn">Hitung Ulang Total</button>
    <a class="btn" href="index.php?page=riwayat">Kembali</a>
    <span class="toolbar-note">Klik teks pada invoice untuk mengedit sebelum dicetak.</span>
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
            <p class="meta"><?= editableText('Dokumen detail riwayat service per transaksi') ?></p>
          </div>
        </div>

        <div class="meta-grid">
          <div class="meta-card">
            <h3>Detail Kendaraan</h3>
            <div class="meta-line"><span class="meta-label">No Polisi:</span><span class="meta-value"><?= editableText($detail['nopol'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Driver:</span><span class="meta-value"><?= editableText($detail['driver_nm'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Referensi:</span><span class="meta-value"><?= editableText('RWY-' . ($detail['id'] ?? '-')) ?></span></div>
            <div class="meta-line"><span class="meta-label">Status:</span><span class="meta-value"><?= editableText($detail['status'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Kategori:</span><span class="meta-value"><?= editableText($detail['kategori'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Total KM:</span><span class="meta-value"><?= editableText((string)($detail['total_km'] ?? 0)) ?> KM</span></div>
            <div class="meta-line"><span class="meta-label">KM Service Terakhir:</span><span class="meta-value"><?= editableText((string)($detail['last_km_service'] ?? 0)) ?> KM</span></div>
            <div class="meta-line"><span class="meta-label">Masa Pakai:</span><span class="meta-value"><?= editableText((string)($detail['masa_pakai_km'] ?? 0)) ?> KM</span></div>
          </div>

          <div class="meta-card">
            <h3>Detail Tanggal & Dokumen</h3>
            <div class="meta-line"><span class="meta-label">No Invoice:</span><span class="meta-value"><?= editableText($invoiceNo) ?></span></div>
            <div class="meta-line"><span class="meta-label">Dibuat :</span><span class="meta-value"><?= editableText($createdAt) ?></span></div>
            <div class="meta-line"><span class="meta-label">Tanggal :</span><span class="meta-value"><?= editableText($detail['tanggal'] ?? '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Tgl Sedang Dikerjakan:</span><span class="meta-value"><?= editableText(($detail['tgl_sedang_dikerjakan'] ?? '') !== '' ? $detail['tgl_sedang_dikerjakan'] : '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Tgl Siap Operasi:</span><span class="meta-value"><?= editableText(($detail['tgl_siap_operasi'] ?? '') !== '' ? $detail['tgl_siap_operasi'] : '-') ?></span></div>
            <div class="meta-line"><span class="meta-label">Tgl Selesai:</span><span class="meta-value"><?= editableText(($detail['tgl_selesai'] ?? '') !== '' ? $detail['tgl_selesai'] : '-') ?></span></div>
          </div>
        </div>

        <table id="invoiceItemsTable">
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
              <th class="no-print row-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr>
                <td colspan="9">Tidak ada item service.</td>
              </tr>
            <?php else: ?>
              <?php $no = 1; ?>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td class="row-number"><?= $no ?></td>
                  <td><span class="editable-cell" contenteditable="true" spellcheck="false"><?= (int)($item['id'] ?? 0) ?></span></td>
                  <td><span class="editable-cell" contenteditable="true" spellcheck="false"><?= e($item['nama_barang'] ?? '-') ?></span></td>
                  <td class="text-right"><span class="editable-cell calc-qty" contenteditable="true" spellcheck="false"><?= (int)($item['jumlah'] ?? 0) ?></span></td>
                  <td class="text-right"><span class="editable-cell calc-price" contenteditable="true" spellcheck="false">Rp <?= number_format((float)($item['harga_satuan'] ?? 0), 0, ',', '.') ?></span></td>
                  <td class="text-right"><span class="editable-cell calc-total" contenteditable="true" spellcheck="false">Rp <?= number_format((float)($item['total_harga'] ?? 0), 0, ',', '.') ?></span></td>
                  <td><span class="editable-cell" contenteditable="true" spellcheck="false"><?= e((string)($item['nama_mekanik'] ?? ($item['mekanik_id'] ?? '-'))) ?></span></td>
                  <td><span class="editable-cell" contenteditable="true" spellcheck="false"><?= e($item['tools'] ?? '-') ?></span></td>
                  <td class="no-print row-actions"><button class="btn btn-danger remove-row-btn" type="button">Hapus</button></td>
                </tr>
                <?php $no++; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="summary">
          <span>Total Qty: <span id="totalQty" class="summary-value"><?= (int)$totalQty ?></span></span>
          <span>Grand Total: <span id="grandTotal" class="summary-value">Rp <?= number_format((float)$totalHarga, 0, ',', '.') ?></span></span>
        </div>

        <div style="margin-top:10px;font-size:13px;">
          <strong>Keterangan:</strong> <?= editableText(($detail['keterangan'] ?? '') !== '' ? $detail['keterangan'] : '-') ?>
        </div>
      </section>
    <?php endif; ?>
  </div>
  <script>
    (function () {
      var table = document.getElementById('invoiceItemsTable');
      var addBtn = document.getElementById('addItemBtn');
      var recalcBtn = document.getElementById('recalcBtn');
      var printBtn = document.getElementById('printInvoiceBtn');
      var totalQtyEl = document.getElementById('totalQty');
      var grandTotalEl = document.getElementById('grandTotal');

      function parseNumber(value) {
        var normalized = String(value || '').replace(/[^0-9,-]/g, '').replace(/\./g, '').replace(',', '.');
        var parsed = parseFloat(normalized);
        return isNaN(parsed) ? 0 : parsed;
      }

      function formatRupiah(value) {
        return 'Rp ' + Math.round(value).toLocaleString('id-ID');
      }

      function renumberRows() {
        if (!table) return;
        table.querySelectorAll('tbody tr').forEach(function (row, index) {
          var numberCell = row.querySelector('.row-number');
          if (numberCell) numberCell.textContent = String(index + 1);
        });
      }

      function recalculateRowTotal(row) {
        var qtyCell = row.querySelector('.calc-qty');
        var priceCell = row.querySelector('.calc-price');
        var totalCell = row.querySelector('.calc-total');
        if (!qtyCell || !priceCell || !totalCell) return;

        totalCell.textContent = formatRupiah(parseNumber(qtyCell.textContent) * parseNumber(priceCell.textContent));
      }

      function recalculateRowTotals() {
        if (!table) return;
        table.querySelectorAll('tbody tr').forEach(recalculateRowTotal);
      }

      function recalculateTotals() {
        if (!table || !totalQtyEl || !grandTotalEl) return;
        var qty = 0;
        var total = 0;

        table.querySelectorAll('tbody tr').forEach(function (row) {
          var qtyCell = row.querySelector('.calc-qty');
          var totalCell = row.querySelector('.calc-total');
          qty += parseNumber(qtyCell ? qtyCell.textContent : '0');
          total += parseNumber(totalCell ? totalCell.textContent : '0');
        });

        totalQtyEl.textContent = String(qty);
        grandTotalEl.textContent = formatRupiah(total);
      }

      function createEditableCell(text, className) {
        var td = document.createElement('td');
        var span = document.createElement('span');
        span.className = 'editable-cell' + (className ? ' ' + className : '');
        span.contentEditable = 'true';
        span.spellcheck = false;
        span.textContent = text;
        td.appendChild(span);
        return td;
      }

      function addRow() {
        if (!table) return;
        var tbody = table.querySelector('tbody');
        var emptyRow = tbody.querySelector('td[colspan]');
        if (emptyRow) {
          emptyRow.parentElement.remove();
        }

        var row = document.createElement('tr');
        var no = document.createElement('td');
        no.className = 'row-number';
        row.appendChild(no);
        row.appendChild(createEditableCell('', ''));
        row.appendChild(createEditableCell('Nama barang', ''));
        row.appendChild(createEditableCell('1', 'calc-qty'));
        row.lastChild.className = 'text-right';
        row.appendChild(createEditableCell('Rp 0', 'calc-price'));
        row.lastChild.className = 'text-right';
        row.appendChild(createEditableCell('Rp 0', 'calc-total'));
        row.lastChild.className = 'text-right';
        row.appendChild(createEditableCell('-', ''));
        row.appendChild(createEditableCell('-', ''));

        var action = document.createElement('td');
        action.className = 'no-print row-actions';
        var remove = document.createElement('button');
        remove.className = 'btn btn-danger remove-row-btn';
        remove.type = 'button';
        remove.textContent = 'Hapus';
        action.appendChild(remove);
        row.appendChild(action);

        tbody.appendChild(row);
        renumberRows();
        recalculateTotals();
      }

      if (table) {
        table.addEventListener('input', function (event) {
          if (event.target.classList.contains('calc-qty') || event.target.classList.contains('calc-price')) {
            recalculateRowTotal(event.target.closest('tr'));
            recalculateTotals();
          }

          if (event.target.classList.contains('calc-total')) {
            recalculateTotals();
          }
        });

        table.addEventListener('click', function (event) {
          if (!event.target.classList.contains('remove-row-btn')) return;
          event.target.closest('tr').remove();
          renumberRows();
          recalculateTotals();
        });
      }

      if (addBtn) addBtn.addEventListener('click', addRow);
      if (recalcBtn) {
        recalcBtn.addEventListener('click', function () {
          recalculateRowTotals();
          recalculateTotals();
        });
      }
      if (printBtn) {
        printBtn.addEventListener('click', function () {
          if (document.activeElement) document.activeElement.blur();
          recalculateTotals();
          window.print();
        });
      }
    })();
  </script>
</body>
</html>
