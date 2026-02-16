<?php
require_once __DIR__ . '/../db/db.php'; // koneksi ke database

// Cek jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Proses login jika ada POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query cek user
    $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Cek apakah user ditemukan
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password (gunakan md5 sementara)
        if (md5($password) === $user['password']) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php?page=dashboard");
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Username tidak ditemukan.";
    }

    $stmt->close();
}
?>

<h2>Login Admin</h2>
<form method="POST" action="">
    <input type="text" name="username" placeholder="Username" required /><br>
    <input type="password" name="password" placeholder="Password" required /><br>
    <button type="submit">Login</button>
</form>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

