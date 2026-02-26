<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/DriverModel.php';

$model = new DriverModel($conn);

$action = $_GET['action'] ?? null;

// HANDLE ACTION
if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->create($_POST);
    header("Location: index.php?page=driver");
    exit;
}

if ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->update($_GET['id'], $_POST);
    header("Location: index.php?page=driver");
    exit;
}

if ($action == 'delete') {
    $model->delete($_GET['id']);
    header("Location: index.php?page=driver");
    exit;
}

$data = $model->getAll();

include 'core/header.php';
?>
<div class="container-fluid py-4">

<div class="card shadow-lg border-0">
  <div class="card-header pb-0 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Data Driver</h5>

    <a href="index.php?page=driver&action=create" 
       class="btn bg-gradient-success btn-sm">
       <i class="fas fa-plus me-1"></i> Tambah Driver
    </a>
  </div>

  <div class="card-body px-0 pt-3 pb-2">
    <div class="table-responsive p-3">
<div class="row">
    <div class="col">

    </div>
    <div class="col">
<div class="row mb-3 px-3 justify-content-end">
  <div class="col-md-4">
    <input type="text" id="searchInput" 
           class="form-control" 
           placeholder="Cari driver...">
  </div>
</div>
    </div>
</div>
      <table id="driverTable" class="table align-items-center mb-0">
        <thead>
          <tr>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder sortable-header" data-sort-index="0">Code</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder sortable-header" data-sort-index="1">Nama</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder sortable-header" data-sort-index="2">Phone</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder sortable-header" data-sort-index="3">SIM</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder sortable-header" data-sort-index="4">Deposit</th>
            <th class="text-end text-secondary">Aksi</th>
          </tr>
        </thead>

        <tbody>

        <?php while($row = $data->fetch_assoc()): ?>
          <tr>

            <td>
              <p class="text-sm font-weight-bold mb-0"><?= $row['code'] ?></p>
            </td>

            <td>
              <p class="text-sm mb-0"><?= $row['name'] ?></p>
            </td>

            <td>
              <p class="text-sm mb-0"><?= $row['phone_no'] ?></p>
            </td>

            <td>
              <span class="badge badge-sm bg-gradient-info">
                <?= $row['sim_class'] ?>
              </span>
            </td>

            <td>
              <?php if($row['deposit'] > 0): ?>
                <span class="badge badge-sm bg-gradient-success">
                  Rp <?= number_format($row['deposit'],0,',','.') ?>
                </span>
              <?php else: ?>
                <span class="badge badge-sm bg-gradient-secondary">
                  Rp 0
                </span>
              <?php endif; ?>
            </td>

            <td class="text-end">
              <a href="index.php?page=driver&action=edit&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-warning btn-sm">
                 <i class="fas fa-edit"></i>
                 Edit
              </a>

              <a href="index.php?page=driver&action=delete&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-danger btn-sm"
                 onclick="return confirm('Yakin hapus driver?')">
                 <i class="fas fa-trash"></i>
                 Hapus
              </a>
            </td>

          </tr>
        <?php endwhile; ?>

        </tbody>
      </table>
<div class="d-flex justify-content-between align-items-center mt-3 px-3">

  <div id="dataInfo" class="text-sm text-secondary"></div>

  <div id="pagination" class="btn-group"></div>

</div>
    </div>
  </div>
</div>

</div>

<?php
if ($action === 'create' || $action === 'edit'):

$data_edit = [
    'code' => '',
    'name' => '',
    'addr' => '',
    'phone_no' => '',
    'sim_no' => '',
    'sim_class' => '',
    'deposit' => 0,
    'join_date' => date('Y-m-d')
];

if ($action === 'edit') {
    $data_edit = $model->getById($_GET['id']);
}
?>

<style>
  .driver-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }
  .driver-overlay-card {
    width: min(920px, 100%);
    max-height: 92vh;
    overflow-y: auto;
  }
</style>

<div class="driver-overlay">
  <div class="card shadow-lg border-0 driver-overlay-card">
    <div class="card-header pb-0 d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><?= $action === 'create' ? 'Tambah Driver' : 'Edit Driver' ?></h5>
      <a href="index.php?page=driver" class="btn btn-sm btn-outline-secondary">Tutup</a>
    </div>

    <div class="card-body">
      <form method="POST" action="index.php?page=driver&action=<?= $action === 'edit' ? 'update&id='.$_GET['id'] : 'create' ?>">

        <div class="row">

          <div class="col-md-6 mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control"
                   value="<?= $data_edit['code'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control"
                   value="<?= $data_edit['name'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone_no" class="form-control"
                   value="<?= $data_edit['phone_no'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">SIM No</label>
            <input type="text" name="sim_no" class="form-control"
                   value="<?= $data_edit['sim_no'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">SIM Class</label>
            <input type="text" name="sim_class" class="form-control"
                   value="<?= $data_edit['sim_class'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Deposit</label>
            <input type="number" name="deposit" class="form-control"
                   value="<?= $data_edit['deposit'] ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Join Date</label>
            <input type="date" name="join_date" class="form-control"
                   value="<?= date('Y-m-d', strtotime($data_edit['join_date'])) ?>">
          </div>

          <div class="col-md-12 mb-3">
            <label class="form-label">Alamat</label>
            <textarea name="addr" class="form-control"><?= $data_edit['addr'] ?></textarea>
          </div>

        </div>

        <hr>

        <div class="d-flex justify-content-between">
          <a href="index.php?page=driver" class="btn btn-outline-secondary">Kembali</a>
          <button type="submit" class="btn bg-gradient-primary">
            <?= $action === 'create' ? 'Simpan Driver' : 'Update Driver' ?>
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<?php endif; ?>
<script>
let rowsPerPage = 10;
let currentPage = 1;
let sortColumnIndex = null;
let sortDirection = 1;

const table = document.getElementById("driverTable");
const tbody = table.querySelector("tbody");
const rows = Array.from(tbody.querySelectorAll("tr"));
const sortableHeaders = table.querySelectorAll(".sortable-header");

function parseCellValue(value) {
    const num = parseFloat(value.replace(/[^0-9.-]+/g, ""));
    return Number.isNaN(num) ? null : num;
}

function updateSortIndicators() {
    sortableHeaders.forEach((header) => {
        const originalLabel = header.getAttribute("data-label") || header.textContent.trim();
        if (!header.getAttribute("data-label")) {
            header.setAttribute("data-label", originalLabel);
        }

        const index = Number(header.dataset.sortIndex);
        if (sortColumnIndex === index) {
            header.textContent = `${originalLabel} ${sortDirection === 1 ? "▲" : "▼"}`;
        } else {
            header.textContent = originalLabel;
        }
    });
}

function displayTable() {

    const searchValue = document.getElementById("searchInput").value.toLowerCase();

    let visibleRows = rows.filter(row => {
        const text = row.innerText.toLowerCase();
        return text.includes(searchValue);
    });

    if (sortColumnIndex !== null) {
        visibleRows.sort((a, b) => {
            const cellA = a.children[sortColumnIndex].innerText.trim();
            const cellB = b.children[sortColumnIndex].innerText.trim();

            const numA = parseCellValue(cellA);
            const numB = parseCellValue(cellB);

            if (numA !== null && numB !== null) {
                return (numA - numB) * sortDirection;
            }

            return cellA.localeCompare(cellB, "id", { sensitivity: "base" }) * sortDirection;
        });
    }

    const hiddenRows = rows.filter(row => !visibleRows.includes(row));
    [...visibleRows, ...hiddenRows].forEach(row => tbody.appendChild(row));

    const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
    if(currentPage > totalPages) currentPage = totalPages || 1;

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = currentPage * rowsPerPage;

    rows.forEach(row => row.style.display = "none");
    visibleRows.slice(startIndex, endIndex).forEach(row => row.style.display = "");

    renderPagination(totalPages);
    renderInfo(visibleRows.length);
    updateSortIndicators();
}

function renderPagination(totalPages) {

    const pagination = document.getElementById("pagination");
    pagination.innerHTML = "";

    if(totalPages <= 1) return;

    // Prev Button
    const prevBtn = document.createElement("button");
    prevBtn.className = "btn btn-sm btn-outline-primary";
    prevBtn.innerHTML = "&laquo;";
    prevBtn.disabled = currentPage === 1;

    prevBtn.onclick = function(){
        if(currentPage > 1){
            currentPage--;
            displayTable();
        }
    };

    // Next Button
    const nextBtn = document.createElement("button");
    nextBtn.className = "btn btn-sm btn-outline-primary";
    nextBtn.innerHTML = "&raquo;";
    nextBtn.disabled = currentPage === totalPages;

    nextBtn.onclick = function(){
        if(currentPage < totalPages){
            currentPage++;
            displayTable();
        }
    };

    pagination.appendChild(prevBtn);
    pagination.appendChild(nextBtn);
}

function renderInfo(totalData) {

    const start = (currentPage - 1) * rowsPerPage + 1;
    const end = Math.min(currentPage * rowsPerPage, totalData);

    document.getElementById("dataInfo").innerHTML =
        `Menampilkan ${start} - ${end} dari ${totalData} data`;
}

document.getElementById("searchInput").addEventListener("keyup", function(){
    currentPage = 1;
    displayTable();
});

sortableHeaders.forEach((header) => {
    header.style.cursor = "pointer";
    header.addEventListener("click", function () {
        const clickedIndex = Number(this.dataset.sortIndex);

        if (sortColumnIndex === clickedIndex) {
            sortDirection *= -1;
        } else {
            sortColumnIndex = clickedIndex;
            sortDirection = 1;
        }

        currentPage = 1;
        displayTable();
    });
});

displayTable();
</script>
<?php
include 'core/footer.php';
?>
