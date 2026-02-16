<?php include 'core/header.php';
require_once __DIR__ . '/../model/ServiceModel.php';

// 🔥 WAJIB ADA INI
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

$serviceModel = new ServiceModel($conn);
if ($start_date && $end_date) {
    $data = $serviceModel->getByDateRange($start_date, $end_date);
} else {
    $data = $serviceModel->getAll();
}

$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? null;
// HANDLE INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $serviceModel->create($_POST);
    header("Location: index.php?page=service");
    exit;
}

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $serviceModel->update($id, $_POST);
    header("Location: index.php?page=service");
    exit; 
}

// AMBIL DATA EDIT
$data_edit = null;
if ($action === 'edit' && $id) {
    $data_edit = $serviceModel->getById($id);
}
?>

<div class="container-fluid py4 mt-4">
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <a href="index.php?page=service&action=create" 
       class="btn bg-gradient-success btn">+ Tambah</a>


    <ul class="navbar-nav  justify-content-end">
            <li class="nav-item d-flex align-items-center">
                  

       <form method="GET" class="row g-3">

<input type="hidden" name="page" value="service">

<div class="col-md-4">
    <input type="date" name="start_date" class="form-control"
           value="<?= $start_date ?>">
</div>

<div class="col-md-4">
    <input type="date" name="end_date" class="form-control"
           value="<?= $end_date ?>">
</div>

<div class="col-md-4 d-flex align-items-end">
    <button type="submit" class="btn bg-gradient-primary me-2">
        Filter
    </button>

    <a href="index.php?page=service" class="btn btn-secondary">
        Reset
    </a>

</div>

</form>
            </li> 
             <li class="nav-item d-flex align-items-center">

             </li>                     
          </ul>


</div>
  <div class="table-responsive">
    <table class="table align-items-center mb-0">
      <thead>
        <tr>
          <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Author</th>
          <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Function</th>
          <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Technology</th>
          <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employed</th>
          <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employed</th>
          <th class="text-secondary opacity-7"></th>
        </tr>
      </thead>
      <tbody>

      <?php while($row = $data->fetch_assoc()): ?>
        <tr>
          <td>
            <div class="d-flex px-2 py-1">
              <div>
                <img src="https://demos.creative-tim.com/soft-ui-design-system-pro/assets/img/team-2.jpg" class="avatar avatar-sm me-3">
              </div>
              <div class="d-flex flex-column justify-content-center">
                <h6 class="mb-0 text-xs"><?= $row['nomor'] ?></h6>
                <p class="text-xs text-secondary mb-0"><?= $row['no_pol'] ?></p>
              </div>
            </div>
          </td>
          <td>
            <p class="text-xs font-weight-bold mb-0"><?= $row['nama_supir'] ?></p>
            <p class="text-xs text-secondary mb-0"><?= $row['total_harga'] ?></p>
          </td>
          <td class="align-middle text-center">
            <span class=" text-secondary text-xs font-weight-bold"><?= $row['total_harga'] ?></span>
          </td>
          <td class="align-middle text-center">
            <span class="text-secondary text-xs font-weight-bold"><?= $row['total_harga'] ?></span>
          </td>
            <td>
    <a href="index.php?page=service&action=edit&id=<?= $row['id'] ?>" 
       class="btn btn-warning btn-sm">Edit</a>

    <a href="index.php?page=service&action=delete&id=<?= $row['id'] ?>" 
       class="btn btn-danger btn-sm"
       onclick="return confirm('Yakin hapus?')">Hapus</a>
  </td>
        </tr>
        <?php endwhile; ?>

      </tbody>
    </table>
  </div>
</div>
</div>

<!--FORM CREATE & EDIT-->
<?php if ($action === 'create' || $action === 'edit'): ?>

<div class="card">
  <div class="card-header">
    <h5><?= $action === 'create' ? 'Tambah Service' : 'Edit Service' ?></h5>
  </div>
  <div class="card-body">

<form method="POST">

<div class="row">

<div class="col-md-6">
  <div class="form-group">
    <label>Nomor</label>
    <input type="text" name="nomor" class="form-control"
      value="<?= $data_edit['nomor'] ?? '' ?>" required>
  </div>
</div>

<div class="col-md-6">
  <div class="form-group">
    <label>No Polisi</label>
    <input type="text" name="no_pol" class="form-control"
      value="<?= $data_edit['no_pol'] ?? '' ?>" required>
  </div>
</div>

<div class="col-md-6">
  <div class="form-group">
    <label>Nama Supir</label>
    <input type="text" name="nama_supir" class="form-control"
      value="<?= $data_edit['nama_supir'] ?? '' ?>">
  </div>
</div>

<div class="col-md-6">
  <div class="form-group">
    <label>Tanggal</label>
    <input type="date" name="tanggal" class="form-control"
      value="<?= $data_edit['tanggal'] ?? '' ?>">
  </div>
</div>

<div class="col-md-6">
  <div class="form-group">
    <label>Nama Barang</label>
    <input type="text" name="nama_barang" class="form-control"
      value="<?= $data_edit['nama_barang'] ?? '' ?>">
  </div>
</div>

<div class="col-md-3">
  <div class="form-group">
    <label>Jumlah</label>
    <input type="number" name="jumlah" id="jumlah" class="form-control"
      value="<?= $data_edit['jumlah'] ?? 0 ?>">
  </div>
</div>

<div class="col-md-3">
  <div class="form-group">
    <label>Harga Satuan</label>
    <input type="number" name="harga_satuan" id="harga_satuan" class="form-control"
      value="<?= $data_edit['harga_satuan'] ?? 0 ?>">
  </div>
</div>

<div class="col-md-6">
  <div class="form-group">
    <label>Total Harga</label>
    <input type="number" name="total_harga" id="total_harga" class="form-control"
      value="<?= $data_edit['total_harga'] ?? 0 ?>" readonly>
  </div>
</div>

</div>

<button type="submit" class="btn bg-gradient-primary">
  <?= $action === 'create' ? 'Simpan' : 'Update' ?>
</button>

<a href="index.php?page=service" class="btn btn-secondary">Kembali</a>

</form>

  </div>
</div>

<script>
document.getElementById('jumlah').addEventListener('input', hitungTotal);
document.getElementById('harga_satuan').addEventListener('input', hitungTotal);

function hitungTotal() {
    let jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
    let harga  = parseFloat(document.getElementById('harga_satuan').value) || 0;
    document.getElementById('total_harga').value = jumlah * harga;
}
</script>

<?php return; endif; ?>








<? include 'core/footer.php'; ?>