<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];

$judul = $pengarang = $penerbit = $tahun_terbit = $genre = $stok = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $pengarang = trim($_POST['pengarang']);
    $penerbit = trim($_POST['penerbit']);
    $tahun_terbit = trim($_POST['tahun_terbit']);
    $genre = trim($_POST['genre']);
    $stok = trim($_POST['stok']);

    if (empty($judul)) $errors[] = "Judul wajib diisi.";
    if (empty($pengarang)) $errors[] = "Pengarang wajib diisi.";
    if (empty($penerbit)) $errors[] = "Penerbit wajib diisi.";
    if (empty($tahun_terbit)) {
        $errors[] = "Tahun terbit wajib diisi.";
    } elseif (!preg_match('/^\d{4}$/', $tahun_terbit)) {
        $errors[] = "Format tahun terbit harus 4 digit angka (YYYY).";
    }
    if (empty($genre)) $errors[] = "Genre wajib diisi.";
    if ($stok === '') {
        $errors[] = "Stok wajib diisi.";
    } elseif (!filter_var($stok, FILTER_VALIDATE_INT) || $stok < 0) {
        $errors[] = "Stok harus berupa angka non-negatif.";
    }
    
    // Handle image upload
    $gambar = null;
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $file_type = $_FILES['gambar']['type'];
        
        if(!in_array($file_type, $allowed_types)) {
            $errors[] = "File harus berformat JPG, JPEG, atau PNG.";
        } else {
            $file_size = $_FILES['gambar']['size'];
            if($file_size > 2097152) { // 2MB
                $errors[] = "Ukuran file tidak boleh lebih dari 2MB.";
            } else {
                $file_name = time() . '_' . $_FILES['gambar']['name'];
                $upload_dir = '../../assets/book_images/';
                $upload_path = $upload_dir . $file_name;
                
                if(!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $errors[] = "Gagal mengupload gambar.";
                } else {
                    $gambar = $file_name;
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, genre, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssss", $judul, $pengarang, $penerbit, $tahun_terbit, $genre, $stok, $gambar);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($koneksi);
                header("Location: list_buku.php?success=Buku berhasil ditambahkan.");
                exit();
            } else {
                $errors[] = "Gagal menambahkan buku: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Gagal menyiapkan statement: " . mysqli_error($koneksi);
        }
    }
    mysqli_close($koneksi);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Bumi Library <3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
     <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            min-height: 100vh;
            padding: 15px;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background-color: #495057;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        .img-preview {
            max-height: 200px;
            max-width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <h4 class="text-center mb-4">Perpus Muflih</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="../../dashboard.php"><i class="bi bi-house-door-fill me-2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
            </li>
            <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link active" href="tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../user/list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
            </li>
             <li class="nav-item">
                <a class="nav-link" href="../user/tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
            </li>
            <?php endif; ?>
             <li class="nav-item mt-auto">
                <a class="nav-link" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <div class="content">
        <div class="container-fluid">
            <h2>Tambah Buku Baru</h2>
            <hr>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Error:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Buku</label>
                            <input type="text" class="form-control" id="judul" name="judul" value="<?php echo sanitize($judul); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="pengarang" class="form-label">Pengarang</label>
                            <input type="text" class="form-control" id="pengarang" name="pengarang" value="<?php echo sanitize($pengarang); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="penerbit" class="form-label">Penerbit</label>
                            <input type="text" class="form-control" id="penerbit" name="penerbit" value="<?php echo sanitize($penerbit); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="tahun_terbit" class="form-label">Tahun Terbit</label>
                            <input type="number" class="form-control" id="tahun_terbit" name="tahun_terbit" placeholder="YYYY" pattern="\d{4}" value="<?php echo sanitize($tahun_terbit); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="genre" class="form-label">Genre</label>
                            <input type="text" class="form-control" id="genre" name="genre" value="<?php echo sanitize($genre); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="stok" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="stok" name="stok" min="0" value="<?php echo sanitize($stok); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Gambar Sampul</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3 text-center">
                                    <img id="coverPreview" src="../../assets/book_images/contoh.png" class="img-fluid img-preview rounded shadow-sm" alt="Preview Sampul">
                                </div>
                                <div class="mb-3">
                                    <label for="gambar" class="form-label">Unggah Gambar</label>
                                    <input type="file" class="form-control" id="gambar" name="gambar" accept=".jpg,.jpeg,.png">
                                    <div class="form-text">Format: JPG, JPEG, PNG. Maks 2MB.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Tambah Buku</button>
                    <a href="list_buku.php" class="btn btn-secondary"><i class="bi bi-x-lg me-2"></i>Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('gambar');
            const previewImage = document.getElementById('coverPreview');
            
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    previewImage.src = '../../assets/book_images/contoh.png';
                }
            });
        });
    </script>
</body>
</html>

