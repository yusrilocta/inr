<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/SparepartModel.php';

$model = new SparepartModel($conn);
$action = $_GET['action'] ?? null;

// ================= HANDLE ACTION =================
if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->create($_POST);
    header("Location: index.php?page=sparepart");
    exit;
}

if ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->update($_GET['id'], $_POST);
    header("Location: index.php?page=sparepart");
    exit;
}

if ($action == 'delete') {
    $model->delete($_GET['id']);
    header("Location: index.php?page=sparepart");
    exit;
}

$data = $model->getAll();
include 'core/header.php';
?>

<div class="container-fluid py-4">
<div class="card shadow-lg border-0">
<div class="card-header d-flex justify-content-between">
    <h5>Data Spare Part</h5>
    <a href="index.php?page=sparepart&action=create"
       class="btn bg-gradient-success btn-sm">
       + Tambah Sparepart
    </a>
</div>

<div class="card-body">

<div class="row mb-3 justify-content-end">
    <div class="col-md-4">
        <input type="text" id="searchInput"
               class="form-control"
               placeholder="Cari sparepart...">
    </div>
</div>

<div class="table-responsive">
<table id="sparepartTable" class="table align-items-center">
<thead>
<tr>
    <th class="sortable-header" data-sort-index="0">Kode</th>
    <th class="sortable-header" data-sort-index="1">Nama</th>
    <th class="sortable-header" data-sort-index="2">Harga Jual</th>
    <th class="sortable-header" data-sort-index="3">Stok</th>
    <th class="sortable-header" data-sort-index="4">Ganti / KM</th>
    <th class="text-end">Aksi</th>
</tr>
</thead>

<tbody>
<?php while($row = $data->fetch_assoc()): ?>
<tr>
    <td><?= $row['part_code'] ?></td>
    <td><?= $row['part_name'] ?></td>
    <td>Rp <?= number_format($row['selling_price'],0,',','.') ?></td>
    <td>
        <?php if($row['stock'] <= $row['min_stock']): ?>
            <span class="badge bg-gradient-danger"><?= $row['stock'] ?></span>
        <?php else: ?>
            <span class="badge bg-gradient-success"><?= $row['stock'] ?></span>
        <?php endif; ?>
    </td>
    <td><?= $row['replacement_km'] ? number_format($row['replacement_km'])." KM" : '-' ?></td>

    <td class="text-end">

        <a href="index.php?page=sparepart&action=detail&id=<?= $row['id'] ?>"
           class="btn btn-outline-info btn-sm">
           Detail
        </a>

        <a href="index.php?page=sparepart&action=edit&id=<?= $row['id'] ?>"
           class="btn btn-outline-warning btn-sm">
           Edit
        </a>

        <a href="index.php?page=sparepart&action=delete&id=<?= $row['id'] ?>"
           class="btn btn-outline-danger btn-sm"
           onclick="return confirm('Yakin hapus sparepart?')">
           Hapus
        </a>

    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<div class="d-flex justify-content-between mt-3">
    <div id="dataInfo"></div>
    <div id="pagination" class="btn-group"></div>
</div>

</div>
</div>
</div>
</div>
<style>
.driver-overlay{
position:fixed;
inset:0;
background:rgba(15,23,42,.45);
display:flex;
align-items:center;
justify-content:center;
z-index:1050;
padding:1rem;
}
.driver-overlay-card{
width:min(900px,100%);
max-height:92vh;
overflow-y:auto;
}
</style>
<?php
if ($action === 'create' || $action === 'edit'):

$data_edit = [
    'part_code'=>'','part_name'=>'','category_id'=>'',
    'brand'=>'','vehicle_type'=>'','unit'=>'PCS',
    'purchase_price'=>0,'selling_price'=>0,
    'stock'=>0,'min_stock'=>0,
    'replacement_km'=>'','replacement_month'=>'',
    'is_active'=>1
];

if ($action === 'edit') {
    $data_edit = $model->getById($_GET['id']);
}
?>

<div class="driver-overlay">
<div class="card driver-overlay-card shadow-lg">
<div class="card-header d-flex justify-content-between">
    <h5><?= $action === 'create' ? 'Tambah Sparepart' : 'Edit Sparepart' ?></h5>
    <a href="index.php?page=sparepart" class="btn btn-outline-secondary btn-sm">Tutup</a>
</div>

<div class="card-body">
<form method="POST"
action="index.php?page=sparepart&action=<?= $action==='edit'?'update&id='.$_GET['id']:'create' ?>">

<div class="row">

<div class="col-md-6 mb-3">
<label>Kode</label>
<input type="text" name="part_code" class="form-control"
value="<?= $data_edit['part_code'] ?>">
</div>

<div class="col-md-6 mb-3">
<label>Nama</label>
<input type="text" name="part_name" class="form-control"
value="<?= $data_edit['part_name'] ?>">
</div>

<div class="col-md-6 mb-3">
<label>Harga Beli</label>
<input type="number" name="purchase_price" class="form-control"
value="<?= $data_edit['purchase_price'] ?>">
</div>

<div class="col-md-6 mb-3">
<label>Harga Jual</label>
<input type="number" name="selling_price" class="form-control"
value="<?= $data_edit['selling_price'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Stok</label>
<input type="number" name="stock" class="form-control"
value="<?= $data_edit['stock'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Min Stok</label>
<input type="number" name="min_stock" class="form-control"
value="<?= $data_edit['min_stock'] ?>">
</div>

<div class="col-md-4 mb-3">
<label>Ganti per KM</label>
<input type="number" name="replacement_km" class="form-control"
value="<?= $data_edit['replacement_km'] ?>">
</div>

</div>

<div class="d-flex justify-content-between">
<a href="index.php?page=sparepart" class="btn btn-outline-secondary">Kembali</a>
<button class="btn bg-gradient-primary">
<?= $action === 'create' ? 'Simpan' : 'Update' ?>
</button>
</div>

</form>
</div>
</div>
</div>

<?php endif; ?>

<?php
if ($action === 'detail'):
$detail = $model->getById($_GET['id']);
?>

<div class="driver-overlay">
<div class="card driver-overlay-card shadow-lg">
<div class="card-header d-flex justify-content-between">
    <h5>Detail Sparepart</h5>
    <a href="index.php?page=sparepart" class="btn btn-outline-secondary btn-sm">Tutup</a>
</div>

<div class="card-body">

<div class="row">

<div class="col-md-6 mb-3">
<strong>Kode:</strong><br>
<?= $detail['part_code'] ?>
</div>

<div class="col-md-6 mb-3">
<strong>Nama:</strong><br>
<?= $detail['part_name'] ?>
</div>

<div class="col-md-6 mb-3">
<strong>Harga Beli:</strong><br>
Rp <?= number_format($detail['purchase_price'],0,',','.') ?>
</div>

<div class="col-md-6 mb-3">
<strong>Harga Jual:</strong><br>
Rp <?= number_format($detail['selling_price'],0,',','.') ?>
</div>

<div class="col-md-4 mb-3">
<strong>Stok:</strong><br>
<?= $detail['stock'] ?>
</div>

<div class="col-md-4 mb-3">
<strong>Min Stok:</strong><br>
<?= $detail['min_stock'] ?>
</div>

<div class="col-md-4 mb-3">
<strong>Interval Ganti:</strong><br>
<?= $detail['replacement_km'] ? number_format($detail['replacement_km'])." KM" : '-' ?>
</div>

</div>

</div>
</div>
</div>

<?php endif; ?>

<?php include 'core/footer.php'; ?>