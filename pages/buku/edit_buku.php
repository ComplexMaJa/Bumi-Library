<?php
require_once '../../config/koneksi.php'; // Provides $koneksi
check_login('admin');

$user_id = $_SESSION['user_id'] ?? null;
$nama_user = $_SESSION['nama_user'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables for form fields
$judul = $pengarang = $penerbit = $tahun_terbit = $genre = $stok = '';
$db_current_gambar = ''; // Stores the image name from DB
$errors = [];
$field_errors = [];

if ($book_id <= 0) {
    $_SESSION['error_message'] = "ID buku tidak valid.";
    header("Location: list_buku.php");
    exit();
}

// --- Initial Data Fetch (for GET or to pre-fill form before POST processing overwrites) ---
// Use global $koneksi directly
$sql_fetch = "SELECT * FROM buku WHERE id = ?";
if ($stmt_fetch = mysqli_prepare($koneksi, $sql_fetch)) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $book_id);
    if ($stmt_fetch && mysqli_stmt_execute($stmt_fetch)) {
        $result = mysqli_stmt_get_result($stmt_fetch);
        if ($book = mysqli_fetch_assoc($result)) {
            $judul = $book['judul'];
            $pengarang = $book['pengarang'];
            $penerbit = $book['penerbit'];
            $tahun_terbit = $book['tahun_terbit'];
            $genre = $book['genre'];
            $stok = $book['stok'];
            $db_current_gambar = $book['gambar'] ?? '';
        } else {
            $_SESSION['error_message'] = "Buku tidak ditemukan (ID: " . htmlspecialchars($book_id) . ").";
            mysqli_stmt_close($stmt_fetch);
            mysqli_close($koneksi);
            header("Location: list_buku.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Gagal mengambil data buku: " . mysqli_stmt_error($stmt_fetch);
        mysqli_stmt_close($stmt_fetch);
        mysqli_close($koneksi);
        header("Location: list_buku.php");
        exit();
    }
    mysqli_stmt_close($stmt_fetch);
    // Do not close $koneksi here, it might be needed for POST on the same script execution if POST fails
} else {
    $_SESSION['error_message'] = "Gagal menyiapkan statement fetch: " . mysqli_error($koneksi);
    mysqli_close($koneksi);
    header("Location: list_buku.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $posted_book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

    // Overwrite fetched data with POST data for form repopulation on error
    $judul = trim($_POST['judul']);
    $pengarang = trim($_POST['pengarang']);
    $penerbit = trim($_POST['penerbit']);
    $tahun_terbit = trim($_POST['tahun_terbit']);
    $genre = trim($_POST['genre']);
    $stok = trim($_POST['stok']);
    // $db_current_gambar is from the initial fetch, used for the hidden field value
    // $form_submitted_current_gambar is what the form submitted as current_gambar
    $form_submitted_current_gambar = $_POST['current_gambar_name'] ?? '';

    if ($posted_book_id !== $book_id) {
        $errors[] = "ID buku tidak cocok. Operasi dibatalkan.";
    }

    if (empty($judul)) $field_errors['judul'] = "Judul wajib diisi.";
    if (empty($pengarang)) $field_errors['pengarang'] = "Pengarang wajib diisi.";
    if (empty($penerbit)) $field_errors['penerbit'] = "Penerbit wajib diisi.";
    if (empty($tahun_terbit)) {
        $field_errors['tahun_terbit'] = "Tahun terbit wajib diisi.";
    } elseif (!preg_match('/^\d{4}$/', $tahun_terbit)) {
        $field_errors['tahun_terbit'] = "Format tahun terbit harus 4 digit angka (YYYY).";
    }
    if (empty($genre)) $field_errors['genre'] = "Genre wajib diisi.";
    if ($stok === '') {
        $field_errors['stok'] = "Stok wajib diisi.";
    } elseif (!filter_var($stok, FILTER_VALIDATE_INT) || $stok < 0) {
        $field_errors['stok'] = "Stok harus berupa angka non-negatif.";
    }

    $gambar_to_save_in_db = $form_submitted_current_gambar; // Default to existing image name from form

    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $file_type = $_FILES['gambar']['type'];

        if(!in_array($file_type, $allowed_types)) {
            $field_errors['gambar'] = "File harus berformat JPG, JPEG, atau PNG.";
        } else {
            $file_size = $_FILES['gambar']['size'];
            if($file_size > 2097152) { // 2MB
                $field_errors['gambar'] = "Ukuran file tidak boleh lebih dari 2MB.";
            } else {
                $original_filename = basename($_FILES['gambar']['name']);
                $safe_filename = preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $original_filename);
                $new_file_name = time() . '_' . $safe_filename;

                $upload_dir = '../../assets/book_images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $upload_path = $upload_dir . $new_file_name;

                if(!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $errors[] = "Gagal mengupload gambar baru.";
                } else {
                    // Delete old image if it exists, is not empty, and is not a default placeholder
                    if(!empty($form_submitted_current_gambar) && $form_submitted_current_gambar !== 'default_cover.png') {
                        $old_image_path = $upload_dir . $form_submitted_current_gambar;
                        if(file_exists($old_image_path)) {
                            @unlink($old_image_path);
                        }
                    }
                    $gambar_to_save_in_db = $new_file_name; // Update to new filename for DB save
                    $db_current_gambar = $new_file_name; // Update for display if POST fails & re-renders
                }
            }
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $field_errors['gambar'] = "Terjadi kesalahan saat mengupload gambar. Kode Error: " . $_FILES['gambar']['error'];
    }

    if (empty($errors) && empty($field_errors)) {
        $sql_update = "UPDATE buku SET judul = ?, pengarang = ?, penerbit = ?, tahun_terbit = ?, genre = ?, stok = ?, gambar = ? WHERE id = ?";
        if ($stmt_update = mysqli_prepare($koneksi, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "sssssisi", $judul, $pengarang, $penerbit, $tahun_terbit, $genre, $stok, $gambar_to_save_in_db, $book_id);
            if (mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);
                mysqli_close($koneksi); // Close connection on success
                $_SESSION['success_message'] = "Buku '".htmlspecialchars($judul)."' berhasil diperbarui.";
                header("Location: list_buku.php");
                exit();
            } else {
                $errors[] = "Gagal memperbarui buku: " . mysqli_stmt_error($stmt_update);
            }
            mysqli_stmt_close($stmt_update);
        } else {
             $errors[] = "Gagal menyiapkan statement update: " . mysqli_error($koneksi);
        }
    }
    // If POST fails, script continues to render form. $koneksi is still open.
}

// If script reaches here, $koneksi might still be open. It will be closed by PHP at script end if not already closed.
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - Bumi Library <3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="../../assets/bootstrap.css/css/theme.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 280px;
            background-color: #212529;
            color: #fff;
            min-height: 100vh;
            padding-top: 1rem;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: #343a40;
        }
        .sidebar .nav-link .bi {
            margin-right: 0.75rem;
        }
        .sidebar .dropdown-toggle::after {
            margin-left: auto;
        }
        .sidebar .dropdown-menu {
            background-color: #343a40;
            border: none;
        }
        .sidebar .dropdown-item {
            color: #adb5bd;
        }
        .sidebar .dropdown-item:hover {
            color: #fff;
            background-color: #495057;
        }
        .sidebar-header {
            padding: 0 1.5rem 1rem 1.5rem;
            border-bottom: 1px solid #495057;
            margin-bottom: 1rem;
        }
        .sidebar-header h4 {
            color: #fff;
            font-weight: bold;
        }
        .content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        .img-preview {
            max-height: 250px;
            width: auto;
            object-fit: cover;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            display: block;
        }
        .footer {
            background-color: #343a40;
            color: #adb5bd;
            padding: 1rem 0;
            text-align: center;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <nav class="sidebar animate__animated animate__fadeInLeft">
        <div class="sidebar-header text-center">
            <h4 class="mb-0">Bumi Library <3</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="../../dashboard.php"><i class="bi bi-house-door-fill"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="list_buku.php"><i class="bi bi-book-fill"></i> Daftar Buku</a>
            </li>
            <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="../user/list_user.php"><i class="bi bi-people-fill"></i> Manajemen User</a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="../peminjaman/daftar_pinjaman.php"><i class="bi bi-journal-bookmark-fill"></i> Pinjaman Saya</a>
            </li>
             <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="../peminjaman/pinjam_buku.php"><i class="bi bi-bag-plus-fill"></i> Pinjamkan Buku</a>
            </li>
            <?php endif; ?>
        </ul>
        <div class="mt-auto p-3">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="../../assets/images/profile_placeholder.png" alt="User Image" width="32" height="32" class="rounded-circle me-2">
                    <strong><?php echo htmlspecialchars($nama_user); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="content d-flex flex-column animate__animated animate__fadeIn">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800 animate__animated animate__fadeInLeft">Edit Buku (ID: <?php echo htmlspecialchars($book_id); ?>)</h1>
                <a href="list_buku.php" class="btn btn-outline-secondary animate__animated animate__fadeInRight">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar Buku
                </a>
            </div>
            <hr class="animate__animated animate__fadeIn" style="animation-delay: 0.1s;">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX" role="alert">
                    <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Gagal memperbarui buku:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm p-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $book_id; ?>" method="post" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($book_id); ?>">
                    <input type="hidden" name="current_gambar_name" value="<?php echo htmlspecialchars($db_current_gambar); ?>">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                                <label for="judul" class="form-label fw-semibold">Judul Buku <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['judul']) ? 'is-invalid' : ''; ?>" id="judul" name="judul" value="<?php echo htmlspecialchars($judul); ?>" required>
                                <?php if (isset($field_errors['judul'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['judul']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3 animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                                <label for="pengarang" class="form-label fw-semibold">Pengarang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['pengarang']) ? 'is-invalid' : ''; ?>" id="pengarang" name="pengarang" value="<?php echo htmlspecialchars($pengarang); ?>" required>
                                <?php if (isset($field_errors['pengarang'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['pengarang']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3 animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
                                <label for="penerbit" class="form-label fw-semibold">Penerbit <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['penerbit']) ? 'is-invalid' : ''; ?>" id="penerbit" name="penerbit" value="<?php echo htmlspecialchars($penerbit); ?>" required>
                                <?php if (isset($field_errors['penerbit'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['penerbit']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3 animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
                                    <label for="tahun_terbit" class="form-label fw-semibold">Tahun Terbit <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control <?php echo isset($field_errors['tahun_terbit']) ? 'is-invalid' : ''; ?>" id="tahun_terbit" name="tahun_terbit" placeholder="YYYY" pattern="\d{4}" value="<?php echo htmlspecialchars($tahun_terbit); ?>" required>
                                    <?php if (isset($field_errors['tahun_terbit'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['tahun_terbit']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3 animate__animated animate__fadeInUp" style="animation-delay: 0.7s;">
                                    <label for="stok" class="form-label fw-semibold">Stok <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control <?php echo isset($field_errors['stok']) ? 'is-invalid' : ''; ?>" id="stok" name="stok" min="0" value="<?php echo htmlspecialchars($stok); ?>" required>
                                    <?php if (isset($field_errors['stok'])): ?>
                                        <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['stok']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-3 animate__animated animate__fadeInUp" style="animation-delay: 0.8s;">
                                <label for="genre" class="form-label fw-semibold">Genre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['genre']) ? 'is-invalid' : ''; ?>" id="genre" name="genre" value="<?php echo htmlspecialchars($genre); ?>" required>
                                 <?php if (isset($field_errors['genre'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['genre']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.9s;">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0 fw-semibold">Gambar Sampul</h5>
                                </div>
                                <div class="card-body text-center">
                                    <img id="coverPreview" src="<?php echo !empty($db_current_gambar) ? '../../assets/book_images/'.htmlspecialchars($db_current_gambar) : '../../assets/book_images/default_cover.png'; ?>" class="img-fluid img-preview mb-3" alt="Preview Sampul">
                                    <label for="gambar" class="form-label btn btn-sm btn-outline-primary w-100">
                                        <i class="bi bi-upload me-2"></i> Ubah Gambar
                                    </label>
                                    <input type="file" class="form-control d-none <?php echo isset($field_errors['gambar']) ? 'is-invalid' : ''; ?>" id="gambar" name="gambar" accept=".jpg,.jpeg,.png" onchange="previewFile()">
                                    <div class="form-text mt-1">Format: JPG, JPEG, PNG. Maks 2MB. Kosongkan jika tidak ingin mengubah.</div>
                                    <?php if (isset($field_errors['gambar'])): ?>
                                        <div class="invalid-feedback d-block text-center mt-2"><?php echo htmlspecialchars($field_errors['gambar']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top animate__animated animate__fadeInUp" style="animation-delay: 1s;">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-2"></i>Simpan Perubahan</button>
                        <a href="list_buku.php" class="btn btn-outline-secondary btn-lg ms-2"><i class="bi bi-x-lg me-2"></i>Batal</a>
                    </div>
                </form>
            </div>
        </div>
        <footer class="footer mt-auto py-3 animate__animated animate__fadeInUp" style="animation-delay: 1.1s;">
            <div class="container text-center">
                <span class="text-muted">&copy; <?php echo date("Y"); ?> Bumi Library. Hak Cipta Dilindungi.</span>
            </div>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewFile() {
            const preview = document.getElementById('coverPreview');
            const fileInput = document.getElementById('gambar');
            const file = fileInput.files[0];
            const reader = new FileReader();

            const initialImageSrc = <?php echo json_encode(!empty($db_current_gambar) ? '../../assets/book_images/'.htmlspecialchars($db_current_gambar) : '../../assets/book_images/default_cover.png'); ?>;

            reader.addEventListener("load", function () {
                preview.src = reader.result;
            }, false);

            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (allowedTypes.includes(file.type)) {
                    reader.readAsDataURL(file);
                } else {
                    alert("Format file tidak valid. Harap pilih JPG, JPEG, atau PNG.");
                    preview.src = initialImageSrc; // Revert to initial image if invalid file selected
                    fileInput.value = '';
                }
            } else {
                 // If no file is selected (e.g., user clears selection), revert to initial image
                 preview.src = initialImageSrc;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alertEl) { // Renamed alert to alertEl to avoid conflict with window.alert
                setTimeout(function() {
                    new bootstrap.Alert(alertEl).close();
                }, 5000);
            });

            <?php if(!empty($field_errors)): ?>
                <?php foreach ($field_errors as $field => $message): ?>
                const inputElement = document.getElementById('<?php echo $field; ?>');
                if (inputElement) {
                    inputElement.classList.add('is-invalid');
                }
                <?php endforeach; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>

