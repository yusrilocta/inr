<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/SparepartHistoryModel.php';

$model = new SparepartHistoryModel($conn);
$data  = $model->getAll();
include 'core/header.php';
?>

<div class="container-fluid py-4">

  <div class="card shadow-lg border-0">
    <div class="card-header pb-0 d-flex justify-content-between align-items-center">
      <h5>Data Sparepart</h5>
      <button class="btn btn-primary btn-sm" onclick="openModal()">+ Tambah Sparepart</button>
    </div>

    <div class="card-body">

      <div class="row mb-3">
        <div class="col-md-4">
          <input type="text" id="searchInput" class="form-control" placeholder="Cari sparepart...">
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-items-center mb-0" id="sparepartTable">
          <thead class="bg-light">
            <tr>
              <th>Nama</th>
              <th>Kategori</th>
              <th>Harga</th>
              <th>Interval KM</th>
              <th>Stok</th>
              <th width="100">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($data as $row): ?>
            <tr>
              <td><?= $row['nama'] ?></td>
              <td><?= $row['kategori'] ?></td>
              <td>Rp <?= number_format($row['harga_satuan']) ?></td>
              <td><?= number_format($row['interval_km']) ?> KM</td>
              <td><?= $row['stok'] ?></td>
              <td>
                <button class="btn btn-warning btn-sm" onclick='editData(<?= json_encode($row) ?>)'>Edit</button>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3 px-3">
        <div id="dataInfo" class="text-sm text-secondary"></div>
        <div>
          <button class="btn btn-outline-secondary btn-sm" id="prevBtn">←</button>
          <button class="btn btn-outline-secondary btn-sm" id="nextBtn">→</button>
        </div>
      </div>

    </div>
  </div>
</div>

<div id="sparepartModal" class="modal-overlay">
  <div class="modal-card">
    <h5 id="modalTitle">Tambah Sparepart</h5>
    <form id="sparepartForm" method="POST" action="index.php?page=sparepart&action=create">

      <input type="hidden" name="id" id="sparepart_id">

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Nama Sparepart</label>
          <input type="text" name="nama" id="nama" class="form-control" required>
        </div>

        <div class="col-md-6 mb-3">
          <label>Kategori</label>
          <input type="text" name="kategori" id="kategori" class="form-control">
        </div>

        <div class="col-md-6 mb-3">
          <label>Harga Satuan</label>
          <input type="number" name="harga_satuan" id="harga_satuan" class="form-control">
        </div>

        <div class="col-md-6 mb-3">
          <label>Interval KM</label>
          <input type="number" name="interval_km" id="interval_km" class="form-control">
        </div>

        <div class="col-md-6 mb-3">
          <label>Stok</label>
          <input type="number" name="stok" id="stok" class="form-control">
        </div>
      </div>

      <div class="text-end">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>
<style>
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
  }
  .modal-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 500px;
  }
  </style>
  <script>
let rows = document.querySelectorAll("#sparepartTable tbody tr");
let currentPage = 1;
let rowsPerPage = 5;

function displayTable() {
  let start = (currentPage - 1) * rowsPerPage;
  let end = start + rowsPerPage;

  rows.forEach((row, index) => {
    row.style.display = (index >= start && index < end) ? "" : "none";
  });

  document.getElementById("dataInfo").innerText =
    "Menampilkan " + (start + 1) + " - " + Math.min(end, rows.length) + " dari " + rows.length;
}

document.getElementById("prevBtn").addEventListener("click", function(){
  if(currentPage > 1){
    currentPage--;
    displayTable();
  }
});

document.getElementById("nextBtn").addEventListener("click", function(){
  if(currentPage < Math.ceil(rows.length / rowsPerPage)){
    currentPage++;
    displayTable();
  }
});

document.getElementById("searchInput").addEventListener("keyup", function(){
  let value = this.value.toLowerCase();
  rows.forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
  });
});

displayTable();

// Modal
function openModal(){
  document.getElementById("sparepartForm").reset();
  document.getElementById("modalTitle").innerText = "Tambah Sparepart";
  document.getElementById("sparepartModal").style.display = "flex";
}

function closeModal(){
  document.getElementById("sparepartModal").style.display = "none";
}

function editData(data){
  openModal();
  document.getElementById("modalTitle").innerText = "Edit Sparepart";

  document.getElementById("sparepart_id").value = data.id;
  document.getElementById("nama").value = data.nama;
  document.getElementById("kategori").value = data.kategori;
  document.getElementById("harga_satuan").value = data.harga_satuan;
  document.getElementById("interval_km").value = data.interval_km;
  document.getElementById("stok").value = data.stok;

  document.getElementById("sparepartForm").action =
    "index.php?page=sparepart&action=update&id=" + data.id;
}
</script>

<?php include 'core/footer.php'; ?>