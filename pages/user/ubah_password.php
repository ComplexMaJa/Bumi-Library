<?php
// filepath: c:\xampp\htdocs\Bumi-Library\pages\user\ubah_password.php
session_start(); // Ensure session is started
require_once '../../config/koneksi.php';

// Only allow non-admin users to access this page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php?error=Silakan login terlebih dahulu.");
    exit();
}

// Redirect admin users back to dashboard with a message
if ($_SESSION['role'] === 'admin') {
    header("Location: ../../dashboard.php?error=Fitur ubah password hanya untuk user biasa.");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username']; // To display in sidebar
$user_role = $_SESSION['role']; // To display correct sidebar links

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($current_password)) $errors[] = "Password saat ini wajib diisi.";
    if (empty($new_password)) $errors[] = "Password baru wajib diisi.";
    else if (strlen($new_password) < 6) $errors[] = "Password baru minimal harus 6 karakter.";
    if (empty($confirm_password)) $errors[] = "Konfirmasi password baru wajib diisi.";

    if (!empty($new_password) && !empty($confirm_password) && $new_password !== $confirm_password) {
        $errors[] = "Password baru dan konfirmasi password tidak cocok.";
    }

    if (empty($errors)) {
        // Verify current password
        $sql_fetch_user = "SELECT password FROM users WHERE id = ?";
        if ($stmt_fetch = mysqli_prepare($koneksi, $sql_fetch_user)) {
            mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
            mysqli_stmt_execute($stmt_fetch);
            $result_user = mysqli_stmt_get_result($stmt_fetch);
            $user_data = mysqli_fetch_assoc($result_user);
            mysqli_stmt_close($stmt_fetch);

            if ($user_data && password_verify($current_password, $user_data['password'])) {
                // Current password is correct, proceed to update
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE users SET password = ? WHERE id = ?";
                if ($stmt_update = mysqli_prepare($koneksi, $sql_update)) {
                    mysqli_stmt_bind_param($stmt_update, "si", $hashed_new_password, $user_id);
                    if (mysqli_stmt_execute($stmt_update)) {
                        $success_message = "Password berhasil diubah.";
                    } else {
                        $errors[] = "Gagal memperbarui password: " . mysqli_stmt_error($stmt_update);
                    }
                    mysqli_stmt_close($stmt_update);
                } else {
                    $errors[] = "Gagal menyiapkan statement update: " . mysqli_error($koneksi);
                }
            } else {
                $errors[] = "Password saat ini salah.";
            }
        } else {
            $errors[] = "Gagal mengambil data user: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - Bumi Library <3</title>
    <link href="../../assets/bootstrap.css/css/theme.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        .sidebar { width: 280px; min-height: 100vh; }
        .content { flex-grow: 1; padding: 1.5rem; }
    </style>
</head>
<body>
    <div class="d-flex">
        <nav class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar">
            <a href="../../dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <i class="bi bi-book-half me-2" style="font-size: 1.5rem;"></i>
                <span class="fs-4">Bumi Library <3</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="../../dashboard.php"><i class="bi bi-house-door-fill me-2"></i> Dashboard</a>
                </li>
                <li>
                    <a class="nav-link text-white <?php echo (strpos($_SERVER['PHP_SELF'], 'list_buku.php') !== false) ? 'active' : ''; ?>" href="../buku/list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
                </li>
                <?php if ($user_role === 'user'): ?>
                <li>
                    <a class="nav-link text-white <?php echo (strpos($_SERVER['PHP_SELF'], 'pinjam_buku.php') !== false) ? 'active' : ''; ?>" href="../peminjaman/pinjam_buku.php"><i class="bi bi-journal-arrow-down me-2"></i> Pinjam Buku</a>
                </li>
                <li>
                    <a class="nav-link text-white <?php echo (strpos($_SERVER['PHP_SELF'], 'daftar_pinjaman.php') !== false) ? 'active' : ''; ?>" href="../peminjaman/daftar_pinjaman.php"><i class="bi bi-journal-bookmark-fill me-2"></i> Buku Dipinjam</a>
                </li>
                <?php endif; ?>
                <?php if ($user_role === 'admin'): ?>
                <li>
                    <a class="nav-link text-white <?php echo (strpos($_SERVER['PHP_SELF'], 'tambah_buku.php') !== false) ? 'active' : ''; ?>" href="../buku/tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
                </li>
                <li>
                    <a class="nav-link text-white <?php
                        $isUserManagementPage = strpos($_SERVER['PHP_SELF'], 'list_user.php') !== false ||
                                                strpos($_SERVER['PHP_SELF'], 'edit_user.php') !== false ||
                                                strpos($_SERVER['PHP_SELF'], 'tambah_user.php') !== false;
                        echo $isUserManagementPage ? 'active' : '';
                    ?>" href="list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
                </li>
                 <li>
                    <a class="nav-link text-white <?php echo (strpos($_SERVER['PHP_SELF'], 'tambah_user.php') !== false) ? 'active' : ''; ?>" href="tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
                </li>
                <?php endif; ?>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong><?php echo htmlspecialchars($current_username); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <?php if ($user_role !== 'admin'): ?>
                    <li><a class="dropdown-item <?php echo (strpos($_SERVER['PHP_SELF'], 'ubah_password.php') !== false) ? 'active' : ''; ?>" href="ubah_password.php"><i class="bi bi-key-fill me-2"></i> Ubah Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="content animate__animated animate__fadeIn">
            <div class="container-fluid">
                <h2 class="animate__animated animate__fadeInLeft">Ubah Password Anda</h2>
                <hr class="animate__animated animate__fadeInLeft" style="animation-delay: 0.1s;">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                        <strong>Error:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="animate__animated animate__fadeInUp card shadow-sm p-4" style="animation-delay: 0.2s;">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" class="form-control <?php if(in_array("Password saat ini wajib diisi.", $errors) || in_array("Password saat ini salah.", $errors)) echo 'is-invalid'; ?>" id="current_password" name="current_password" required>
                        <?php if(in_array("Password saat ini wajib diisi.", $errors)): ?>
                            <div class="invalid-feedback">Password saat ini wajib diisi.</div>
                        <?php elseif(in_array("Password saat ini salah.", $errors)): ?>
                            <div class="invalid-feedback">Password saat ini salah.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control <?php if(in_array("Password baru wajib diisi.", $errors) || in_array("Password baru minimal harus 6 karakter.", $errors) || in_array("Password baru dan konfirmasi password tidak cocok.", $errors)) echo 'is-invalid'; ?>" id="new_password" name="new_password" required>
                        <?php if(in_array("Password baru wajib diisi.", $errors)): ?>
                            <div class="invalid-feedback">Password baru wajib diisi.</div>
                        <?php elseif(in_array("Password baru minimal harus 6 karakter.", $errors)): ?>
                            <div class="invalid-feedback">Password baru minimal harus 6 karakter.</div>
                        <?php elseif(in_array("Password baru dan konfirmasi password tidak cocok.", $errors)): ?>
                            <div class="invalid-feedback">Password baru dan konfirmasi password tidak cocok (pastikan konfirmasi diisi).</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control <?php if(in_array("Konfirmasi password baru wajib diisi.", $errors) || in_array("Password baru dan konfirmasi password tidak cocok.", $errors)) echo 'is-invalid'; ?>" id="confirm_password" name="confirm_password" required>
                         <?php if(in_array("Konfirmasi password baru wajib diisi.", $errors)): ?>
                            <div class="invalid-feedback">Konfirmasi password baru wajib diisi.</div>
                        <?php elseif(in_array("Password baru dan konfirmasi password tidak cocok.", $errors)): ?>
                            <div class="invalid-feedback">Password baru dan konfirmasi password tidak cocok.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary me-2"><i class="bi bi-key-fill me-2"></i>Ubah Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../../assets/bootstrap.js/bootstrap.bundle.min.js"></script>
    <?php
    if (isset($koneksi) && $koneksi) {
        mysqli_close($koneksi);
    }
    ?>
</body>
</html>
