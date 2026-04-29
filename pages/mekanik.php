<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../model/MekanikModel.php';

$model = new MekanikModel($conn);

$action = $_GET['action'] ?? null;

// HANDLE ACTION
if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->create($_POST);
    header("Location: index.php?page=mekanik");
    exit;
}

if ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $model->update($_GET['id'], $_POST);
    header("Location: index.php?page=mekanik");
    exit;
}

if ($action == 'delete') {
    $model->delete($_GET['id']);
    header("Location: index.php?page=mekanik");
    exit;
}

$data = $model->getAll();

include 'core/header.php';
?>
<div class="container-fluid py-4">

<div class="card shadow-lg border-0">
  <div class="card-header pb-0 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Data Mekanik</h5>

    <a href="index.php?page=mekanik&action=create" 
       class="btn bg-gradient-success btn-sm">
       <i class="fas fa-plus me-1"></i> Tambah Mekanik
    </a>
  </div>

  <div class="card-body px-0 pt-3 pb-2">
    <div class="table-responsive p-3">
<div class="row">
    <div class="col">

    </div>
    <div class="col">
<div class="d-flex flex-nowrap justify-content-end align-items-center gap-2 px-1 mb-3 overflow-auto">
  <div class="d-flex flex-nowrap align-items-center gap-2 m-0">
    <input type="text" id="searchInput"
           class="form-control"
           placeholder="Cari mekanik..."
           style="min-width:260px;">
  </div>
</div>
    </div>
</div>
      <table id="mekanikTable" class="table align-items-center mb-0">
        <thead>
          <tr>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder sortable-header" data-sort-index="0">Nama</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder sortable-header" data-sort-index="1">No KTP</th>
            <th class="text-xs text-uppercase text-secondary font-weight-bolder sortable-header" data-sort-index="2">Alamat</th>
            <th class="text-end text-secondary">Aksi</th>
          </tr>
        </thead>

        <tbody>

        <?php while($row = $data->fetch_assoc()): ?>
          <tr>

            <td>
              <p class="text-sm font-weight-bold mb-0"><?= $row['nama'] ?></p>
            </td>

            <td>
              <p class="text-sm mb-0"><?= $row['no_ktp'] ?></p>
            </td>

            <td>
              <p class="text-sm mb-0"><?= $row['alamat'] ?></p>
            </td>

            <td class="text-end">
              <a href="index.php?page=mekanik&action=edit&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-warning">
                 <i class="fas fa-edit"></i>
              </a>

              <a href="index.php?page=mekanik&action=delete&id=<?= $row['id'] ?>" 
                 class="btn btn-outline-danger"
                 onclick="return confirm('Yakin hapus mekanik?')">
                 <i class="fas fa-trash"></i>
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
    'nama' => '',
    'no_ktp' => '',
    'alamat' => ''
];

if ($action === 'edit') {
    $data_edit = $model->getById($_GET['id']);
}
?>

<style>
  .mekanik-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }
  .mekanik-overlay-card {
    width: min(820px, 100%);
    max-height: 92vh;
    overflow-y: auto;
  }
</style>

<div class="mekanik-overlay">
  <div class="card shadow-lg border-0 mekanik-overlay-card">
    <div class="card-header pb-0 d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><?= $action === 'create' ? 'Tambah Mekanik' : 'Edit Mekanik' ?></h5>
      <a href="index.php?page=mekanik" class="btn btn-sm btn-outline-secondary">Tutup</a>
    </div>

    <div class="card-body">
      <form method="POST" action="index.php?page=mekanik&action=<?= $action === 'edit' ? 'update&id='.$_GET['id'] : 'create' ?>">

        <div class="row">

          <div class="col-md-6 mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="nama" class="form-control"
                   value="<?= $data_edit['nama'] ?>" required>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">No KTP</label>
            <input type="text" name="no_ktp" class="form-control"
                   value="<?= $data_edit['no_ktp'] ?>" required>
          </div>

          <div class="col-md-12 mb-3">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" required><?= $data_edit['alamat'] ?></textarea>
          </div>

        </div>

        <hr>

        <div class="d-flex justify-content-between">
          <a href="index.php?page=mekanik" class="btn btn-outline-secondary">Kembali</a>
          <button type="submit" class="btn bg-gradient-primary">
            <?= $action === 'create' ? 'Simpan Mekanik' : 'Update Mekanik' ?>
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

const table = document.getElementById("mekanikTable");
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
            header.textContent = `${originalLabel} ${sortDirection === 1 ? "â–²" : "â–¼"}`;
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
