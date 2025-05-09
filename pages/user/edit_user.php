<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$username = $user_role = '';
$errors = [];

if ($user_id > 0) {
    $sql_fetch = "SELECT username, role FROM users WHERE id = ?";
    if ($stmt_fetch = mysqli_prepare($koneksi, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
        mysqli_stmt_execute($stmt_fetch);
        $result = mysqli_stmt_get_result($stmt_fetch);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_fetch);

        if ($user) {
            $username = $user['username'];
            $role = $user['role'];
        } else {
            header("Location: list_user.php?error=User tidak ditemukan.");
            exit();
        }
    } else {
        die("Error preparing fetch statement: " . mysqli_error($koneksi));
    }
} else {
    header("Location: list_user.php?error=ID User tidak valid.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $new_role = trim($_POST['role']);
    $current_user_id = (int)$_POST['user_id'];

    if (empty($new_username)) $errors[] = "Username wajib diisi.";
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore.";
    }
    if (!empty($new_password) && strlen($new_password) < 6) {
        $errors[] = "Password baru minimal harus 6 karakter.";
    }
    if (empty($new_role)) $errors[] = "Role wajib dipilih.";
    elseif ($new_role !== 'admin' && $new_role !== 'user') {
        $errors[] = "Role tidak valid.";
    }
    if ($current_user_id !== $user_id) {
         $errors[] = "ID User tidak cocok.";
    }

    if ($new_username !== $username && empty($errors)) {
        $sql_check = "SELECT id FROM users WHERE username = ? AND id != ?";
        if ($stmt_check = mysqli_prepare($koneksi, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "si", $new_username, $user_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $errors[] = "Username baru sudah digunakan.";
            }
            mysqli_stmt_close($stmt_check);
        } else {
            $errors[] = "Gagal memeriksa username: " . mysqli_error($koneksi);
        }
    }

    if (empty($errors)) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";

            if ($stmt_update = mysqli_prepare($koneksi, $sql_update)) {
                mysqli_stmt_bind_param($stmt_update, "sssi", $new_username, $hashed_password, $new_role, $user_id);

                if (mysqli_stmt_execute($stmt_update)) {
                    mysqli_stmt_close($stmt_update);
                    mysqli_close($koneksi);
                    header("Location: list_user.php?success=User berhasil diperbarui.");
                    exit();
                } else {
                    $errors[] = "Gagal memperbarui user: " . mysqli_stmt_error($stmt_update);
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $errors[] = "Gagal menyiapkan statement update: " . mysqli_error($koneksi);
            }
        } else {
            $sql_update = "UPDATE users SET username = ?, role = ? WHERE id = ?";

            if ($stmt_update = mysqli_prepare($koneksi, $sql_update)) {
                mysqli_stmt_bind_param($stmt_update, "ssi", $new_username, $new_role, $user_id);

                if (mysqli_stmt_execute($stmt_update)) {
                    mysqli_stmt_close($stmt_update);
                    mysqli_close($koneksi);
                    header("Location: list_user.php?success=User berhasil diperbarui.");
                    exit();
                } else {
                    $errors[] = "Gagal memperbarui user: " . mysqli_stmt_error($stmt_update);
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $errors[] = "Gagal menyiapkan statement update: " . mysqli_error($koneksi);
            }
        }
    }
    $username = $new_username;
    $role_display = $new_role;
    mysqli_close($koneksi);
} else {
    $role_display = $user['role'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Bumi Library <3</title>
    <link href="../../assets/bootstrap.css/css/theme.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        .sidebar {
            width: 280px;
            min-height: 100vh;
        }
        .content {
            flex-grow: 1;
            padding: 1.5rem;
        }
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
                    <a class="nav-link text-white" href="../../dashboard.php"><i class="bi bi-house-door-fill me-2"></i> Dashboard</a>
                </li>
                <li>
                    <a class="nav-link text-white" href="../buku/list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
                </li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li>
                    <a class="nav-link text-white" href="../buku/tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
                </li>
                <li>
                    <a class="nav-link active text-white" href="list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
                </li>
                 <li>
                    <a class="nav-link text-white" href="tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
                </li>
                <?php endif; ?>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="content animate__animated animate__fadeIn">
            <div class="container-fluid">
                <h2 class="animate__animated animate__fadeInLeft">Edit User (ID: <?php echo htmlspecialchars($user_id); ?>)</h2>
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

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $user_id; ?>" method="post" class="animate__animated animate__fadeInUp card shadow-sm p-4" style="animation-delay: 0.2s;">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control <?php if(in_array("Username wajib diisi.", $errors) || in_array("Username hanya boleh berisi huruf, angka, dan underscore.", $errors) || in_array("Username baru sudah digunakan.", $errors)) echo 'is-invalid'; ?>" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        <?php if(in_array("Username wajib diisi.", $errors)): ?>
                            <div class="invalid-feedback">Username wajib diisi.</div>
                        <?php elseif(in_array("Username hanya boleh berisi huruf, angka, dan underscore.", $errors)): ?>
                            <div class="invalid-feedback">Username hanya boleh berisi huruf, angka, dan underscore.</div>
                        <?php elseif(in_array("Username baru sudah digunakan.", $errors)): ?>
                            <div class="invalid-feedback">Username baru sudah digunakan.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru (Opsional)</label>
                        <input type="password" class="form-control <?php if(in_array("Password baru minimal harus 6 karakter.", $errors)) echo 'is-invalid'; ?>" id="password" name="password">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter jika diisi.</div>
                        <?php if(in_array("Password baru minimal harus 6 karakter.", $errors)): ?>
                            <div class="invalid-feedback">Password baru minimal harus 6 karakter.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select <?php if(in_array("Role wajib dipilih.", $errors) || in_array("Role tidak valid.", $errors)) echo 'is-invalid'; ?>" id="role" name="role" required>
                            <option value="admin" <?php echo ($role_display == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo ($role_display == 'user') ? 'selected' : ''; ?>>User</option>
                        </select>
                        <?php if(in_array("Role wajib dipilih.", $errors)): ?>
                            <div class="invalid-feedback">Role wajib dipilih.</div>
                        <?php elseif(in_array("Role tidak valid.", $errors)): ?>
                            <div class="invalid-feedback">Role tidak valid.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save-fill me-2"></i>Simpan Perubahan</button>
                        <a href="list_user.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Batal</a>
                    </div>
                </form>

                <div class="mt-5 mb-3 pt-4 animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
                    <hr class="border-2 border-dark opacity-25">
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <div class="text-muted small">
                            <i class="bi bi-book-half me-1"></i> Bumi Library <3
                        </div>
                        <div class="text-muted small">
                            &copy; <?php echo date('Y'); ?> | Crafted with <i class="bi bi-heart-fill text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/bootstrap.js/bootstrap.bundle.min.js"></script>
</body>
</html>
