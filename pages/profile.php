<?php
require_once __DIR__ . '/../db/db.php';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userId = $_SESSION['user_id'] ?? null;
  $username = trim($_POST['username'] ?? '');
  $currentPassword = trim($_POST['current_password'] ?? '');
  $newPassword = trim($_POST['new_password'] ?? '');
  $confirmPassword = trim($_POST['confirm_password'] ?? '');

  if (!$userId) {
    $error = 'Silakan login kembali.';
  } elseif ($username === '' || $currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    $error = 'Semua field wajib diisi.';
  } elseif ($newPassword !== $confirmPassword) {
    $error = 'Konfirmasi password tidak sama.';
  } else {
    $stmt = $conn->prepare("SELECT username, password FROM admin WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
      $error = 'Akun tidak ditemukan.';
    } else {
      $user = $result->fetch_assoc();
      if (md5($currentPassword) !== $user['password']) {
        $error = 'Password saat ini salah.';
      } else {
        $check = $conn->prepare("SELECT id FROM admin WHERE username = ? AND id <> ?");
        $check->bind_param("si", $username, $userId);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
          $error = 'Username sudah digunakan.';
        } else {
          $newPasswordHash = md5($newPassword);
          $update = $conn->prepare("UPDATE admin SET username = ?, password = ? WHERE id = ?");
          $update->bind_param("ssi", $username, $newPasswordHash, $userId);

          if ($update->execute()) {
            $_SESSION['username'] = $username;
            $success = 'Profil berhasil diperbarui.';
          } else {
            $error = 'Gagal memperbarui profil.';
          }

          $update->close();
        }

        $check->close();
      }
    }

    $stmt->close();
  }
}
include 'core/header.php'; 
?>

<div class="container-fluid py-4 mt-2">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="card">
        <div class="card-header pb-0">
          <h5 class="mb-0">Profil</h5>
          <p class="text-sm mb-0">Ganti username dan password Anda</p>
        </div>
        <div class="card-body">
          <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
              <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
              <?php echo htmlspecialchars($success); ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="">
            <div class="mb-3">
              <label for="username" class="form-label">Username Baru</label>
              <input
                type="text"
                class="form-control"
                id="username"
                name="username"
                placeholder="Masukkan username baru"
                required
              />
            </div>

            <div class="mb-3">
              <label for="current_password" class="form-label">Password Saat Ini</label>
              <input
                type="password"
                class="form-control"
                id="current_password"
                name="current_password"
                placeholder="Masukkan password saat ini"
                required
              />
            </div>

            <div class="mb-3">
              <label for="new_password" class="form-label">Password Baru</label>
              <input
                type="password"
                class="form-control"
                id="new_password"
                name="new_password"
                placeholder="Masukkan password baru"
                required
              />
            </div>

            <div class="mb-3">
              <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
              <input
                type="password"
                class="form-control"
                id="confirm_password"
                name="confirm_password"
                placeholder="Ulangi password baru"
                required
              />
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" class="btn bg-gradient-primary mb-0">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'core/footer.php'; ?>
