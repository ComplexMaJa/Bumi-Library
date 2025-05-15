<?php
require_once 'config/koneksi.php';
check_login();

$username = sanitize($_SESSION['username']);
$role = sanitize($_SESSION['role']);

$error_message = '';
if (isset($_GET['error'])) {
    $error_message = sanitize($_GET['error']);
}

$success_message = '';
if (isset($_GET['success'])) {
    $success_message = sanitize($_GET['success']);
}

$books = [];
if ($role === 'user') {
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? trim(mysqli_real_escape_string($koneksi, $_GET['search'])) : '';

    $sql_count = "SELECT COUNT(*) as total FROM buku";
    if (!empty($search)) {
        $sql_count .= " WHERE judul LIKE ?";
    }

    $total_books = 0;
    if ($stmt_count = mysqli_prepare($koneksi, $sql_count)) {
        if (!empty($search)) {
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt_count, "s", $search_param);
        }
        mysqli_stmt_execute($stmt_count);
        $result_count = mysqli_stmt_get_result($stmt_count);
        if ($row_count = mysqli_fetch_assoc($result_count)) {
            $total_books = $row_count['total'];
        }
        mysqli_stmt_close($stmt_count);
    }

    $total_pages = ceil($total_books / $limit);
    $page = min($page, max(1, $total_pages));
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM buku";
    if (!empty($search)) {
        $sql .= " WHERE judul LIKE ?";
    }
    $sql .= " ORDER BY judul ASC LIMIT ? OFFSET ?";

    if ($stmt = mysqli_prepare($koneksi, $sql)) {
        if (!empty($search)) {
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "sii", $search_param, $limit, $offset);
        } else {
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $books = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bumi Library <3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            background-color: #f8f9fa;
        }

        .content {
            flex: 1;
            padding: 20px;
        }
        .navbar-custom {
             background-color: #ffffff;
             border-bottom: 1px solid #dee2e6;
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="d-flex">
        <nav class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px; min-height: 100vh;">
            <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <i class="bi bi-book-half me-2" style="font-size: 1.5rem;"></i>
                <span class="fs-4">Bumi Library <3</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a class="nav-link active text-white" href="dashboard.php"><i class="bi bi-house-door-fill me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="pages/buku/list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
                </li>
                <?php if ($role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="pages/buku/tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="pages/user/list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link text-white" href="pages/user/tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="pages/peminjaman/pinjam_buku.php"><i class="bi bi-journal-arrow-down me-2"></i> Pinjam Buku</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="pages/peminjaman/daftar_pinjaman.php"><i class="bi bi-journal-bookmark-fill me-2"></i> Buku Dipinjam</a>
                </li>
                <?php endif; ?>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong><?php echo sanitize($_SESSION['username']); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="content animate__animated animate__fadeIn">
            <nav class="navbar navbar-expand-lg navbar-custom mb-4 shadow-sm">
                <div class="container-fluid">
                    <span class="navbar-brand">Selamat Datang, <?php echo htmlspecialchars($username); ?> (<?php echo ucfirst(htmlspecialchars($role)); ?>)</span>
                     <a href="logout.php" class="btn btn-outline-danger ms-auto d-lg-none"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </nav>

            <div class="container-fluid">
                <h2 class="animate__animated animate__fadeInUp">Dashboard Utama</h2>
                <hr class="animate__animated animate__fadeInUp">

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <p class="lead animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">Selamat datang di sistem informasi Bumi Library <3.</p>
                <p class="animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">Gunakan menu di sebelah kiri untuk navigasi.</p>

                <div class="row mt-4 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <?php if ($role === 'admin'): ?>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card text-white bg-primary h-100">
                                <div class="card-header"><i class="bi bi-book-fill me-2"></i>Buku</div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">Kelola Buku</h5>
                                    <p class="card-text">Tambah, edit, atau hapus data buku.</p>
                                    <a href="pages/buku/list_buku.php" class="btn btn-light mt-auto"><i class="bi bi-arrow-right-circle me-2"></i>Lihat Buku</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card text-white bg-success h-100">
                                <div class="card-header"><i class="bi bi-people-fill me-2"></i>User</div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">Kelola User</h5>
                                    <p class="card-text">Tambah atau lihat data user.</p>
                                    <a href="pages/user/list_user.php" class="btn btn-light mt-auto"><i class="bi bi-arrow-right-circle me-2"></i>Lihat User</a>
                                </div>
                            </div>
                        </div>

                <?php else: ?>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card text-white bg-info h-100">
                                <div class="card-header"><i class="bi bi-search me-2"></i>Buku</div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">Lihat Buku</h5>
                                    <p class="card-text">Lihat koleksi buku yang tersedia.</p>
                                    <a href="pages/buku/list_buku.php" class="btn btn-light mt-auto"><i class="bi bi-arrow-right-circle me-2"></i>Lihat Daftar Buku</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card text-white bg-primary h-100">
                                <div class="card-header"><i class="bi bi-journal-arrow-down me-2"></i>Peminjaman</div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">Pinjam Buku</h5>
                                    <p class="card-text">Pinjam buku dari koleksi perpustakaan.</p>
                                    <a href="pages/peminjaman/pinjam_buku.php" class="btn btn-light mt-auto"><i class="bi bi-arrow-right-circle me-2"></i>Pinjam Buku</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-4 mb-4">
                            <div class="card text-white bg-success h-100">
                                <div class="card-header"><i class="bi bi-journal-bookmark-fill me-2"></i>Buku Dipinjam</div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">Buku Saya</h5>
                                    <p class="card-text">Lihat dan kelola buku yang sedang Anda pinjam.</p>
                                    <a href="pages/peminjaman/daftar_pinjaman.php" class="btn btn-light mt-auto"><i class="bi bi-arrow-right-circle me-2"></i>Lihat Pinjaman</a>
                                </div>
                            </div>
                        </div>

                <?php endif; ?>
                </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>