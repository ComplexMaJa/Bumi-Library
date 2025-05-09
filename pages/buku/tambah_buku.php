<?php
require_once '../../config/koneksi.php';
check_login('admin');

$user_id = $_SESSION['user_id'];
$nama_user = $_SESSION['nama_user'] ?? 'Nama Pengguna'; // Fix: Use null coalescing operator
$role = $_SESSION['role'];

$judul = $pengarang = $penerbit = $tahun_terbit = $genre = $stok = '';
$errors = [];
$field_errors = []; // For specific field errors

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $pengarang = trim($_POST['pengarang']);
    $penerbit = trim($_POST['penerbit']);
    $tahun_terbit = trim($_POST['tahun_terbit']);
    $genre = trim($_POST['genre']);
    $stok = trim($_POST['stok']);

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

    $gambar = null;
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
                // Sanitize filename
                $original_filename = basename($_FILES['gambar']['name']);
                $safe_filename = preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $original_filename);
                $file_name = time() . '_' . $safe_filename;

                $upload_dir = '../../assets/book_images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $upload_path = $upload_dir . $file_name;

                if(!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $errors[] = "Gagal mengupload gambar.";
                } else {
                    $gambar = $file_name;
                }
            }
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $field_errors['gambar'] = "Terjadi kesalahan saat mengupload gambar. Kode Error: " . $_FILES['gambar']['error'];
    }


    if (empty($errors) && empty($field_errors)) {
        // $koneksi is available from require_once '../../config/koneksi.php';
        $sql = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, genre, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssis", $judul, $pengarang, $penerbit, $tahun_terbit, $genre, $stok, $gambar);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($koneksi);
                $_SESSION['success_message'] = "Buku '".htmlspecialchars($judul)."' berhasil ditambahkan.";
                header("Location: list_buku.php");
                exit();
            } else {
                $errors[] = "Gagal menambahkan buku: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Gagal menyiapkan statement: " . mysqli_error($koneksi);
        }
    }
    // Close connection if not already closed on success
    if (isset($koneksi) && mysqli_ping($koneksi)) {
        mysqli_close($koneksi);
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Bumi Library <3</title>
    <link href="../../assets/bootstrap.css/css/theme.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .sidebar { /* Added from tambah_user.php */
            width: 280px;
            min-height: 100vh;
        }
        .content { /* Added from tambah_user.php */
            flex-grow: 1;
            padding: 1.5rem;
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
                <li class="nav-item">
                    <a class="nav-link text-white" href="list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
                </li>
                <?php if ($role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link active text-white" href="tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="../user/list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="../user/tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
                </li>
                <?php endif; ?>
                <?php if ($role !== 'admin'): // Hide Pinjaman Saya for admin ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="../peminjaman/daftar_pinjaman.php"><i class="bi bi-journal-bookmark-fill me-2"></i> Pinjaman Saya</a>
                </li>
                <?php endif; ?>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong><?php echo htmlspecialchars($nama_user); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign out</a></li>
                </ul>
            </div>
        </nav>

        <div class="content animate__animated animate__fadeIn">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0 animate__animated animate__fadeInLeft">Tambah Buku Baru</h2>
                    <a href="list_buku.php" class="btn btn-outline-secondary animate__animated animate__fadeInRight">
                        <i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar Buku
                    </a>
                </div>
                <hr class="animate__animated animate__fadeInLeft" style="animation-delay: 0.1s;">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Gagal menambahkan buku:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" novalidate class="card shadow-sm p-4 w-100 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">

                        <div class="mb-3">
                            <label for="judul" class="form-label fw-semibold">Judul Buku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($field_errors['judul']) ? 'is-invalid' : ''; ?>" id="judul" name="judul" value="<?php echo htmlspecialchars($judul); ?>" required>
                            <?php if (isset($field_errors['judul'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['judul']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="pengarang" class="form-label fw-semibold">Pengarang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($field_errors['pengarang']) ? 'is-invalid' : ''; ?>" id="pengarang" name="pengarang" value="<?php echo htmlspecialchars($pengarang); ?>" required>
                            <?php if (isset($field_errors['pengarang'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['pengarang']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="penerbit" class="form-label fw-semibold">Penerbit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($field_errors['penerbit']) ? 'is-invalid' : ''; ?>" id="penerbit" name="penerbit" value="<?php echo htmlspecialchars($penerbit); ?>" required>
                            <?php if (isset($field_errors['penerbit'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['penerbit']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tahun_terbit" class="form-label fw-semibold">Tahun Terbit <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?php echo isset($field_errors['tahun_terbit']) ? 'is-invalid' : ''; ?>" id="tahun_terbit" name="tahun_terbit" placeholder="YYYY" pattern="\d{4}" value="<?php echo htmlspecialchars($tahun_terbit); ?>" required>
                                <?php if (isset($field_errors['tahun_terbit'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['tahun_terbit']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stok" class="form-label fw-semibold">Stok <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?php echo isset($field_errors['stok']) ? 'is-invalid' : ''; ?>" id="stok" name="stok" min="0" value="<?php echo htmlspecialchars($stok); ?>" required>
                                <?php if (isset($field_errors['stok'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['stok']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="genre" class="form-label fw-semibold">Genre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($field_errors['genre']) ? 'is-invalid' : ''; ?>" id="genre" name="genre" value="<?php echo htmlspecialchars($genre); ?>" required>
                             <?php if (isset($field_errors['genre'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['genre']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0 fw-semibold">Gambar Sampul</h5>
                                </div>
                                <div class="card-body text-center">
                                    <img id="coverPreview" src="../../assets/book_images/default_cover.png" class="img-fluid img-preview mb-3" alt="Preview Sampul">
                                    <label for="gambar" class="form-label btn btn-sm btn-outline-primary w-100">
                                        <i class="bi bi-upload me-2"></i> Unggah Gambar
                                    </label>
                                    <input type="file" class="form-control d-none <?php echo isset($field_errors['gambar']) ? 'is-invalid' : ''; ?>" id="gambar" name="gambar" accept=".jpg,.jpeg,.png" onchange="previewFile()">
                                    <div class="form-text mt-1">Format: JPG, JPEG, PNG. Maks 2MB.</div>
                                    <?php if (isset($field_errors['gambar'])): ?>
                                        <div class="invalid-feedback d-block text-center mt-2"><?php echo htmlspecialchars($field_errors['gambar']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-plus-lg me-2"></i>Tambah Buku</button>
                            <a href="list_buku.php" class="btn btn-outline-secondary btn-lg ms-2"><i class="bi bi-x-lg me-2"></i>Batal</a>
                        </div>
                </form>

                <!-- Scroll Boundary Footer -->
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
            </div> <!-- End content -->
        </div> <!-- End d-flex -->

    <script src="../../assets/bootstrap.js/bootstrap.bundle.min.js"></script>
    <script>
        function previewFile() {
            const preview = document.getElementById('coverPreview');
            const fileInput = document.getElementById('gambar');
            const file = fileInput.files[0];
            const reader = new FileReader();

            reader.addEventListener("load", function () {
                preview.src = reader.result;
            }, false);

            if (file) {
                // Basic client-side validation for file type (optional, server-side is key)
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (allowedTypes.includes(file.type)) {
                    reader.readAsDataURL(file);
                } else {
                    alert("Format file tidak valid. Harap pilih JPG, JPEG, atau PNG.");
                    preview.src = '../../assets/book_images/default_cover.png';
                    fileInput.value = ''; // Clear the invalid file selection
                }
            } else {
                 preview.src = '../../assets/book_images/default_cover.png';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss success/error messages after a few seconds
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    new bootstrap.Alert(alert).close();
                }, 5000); // 5 seconds
            });
        });
    </script>
</body>
</html>

