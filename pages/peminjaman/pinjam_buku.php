<?php
require_once '../../config/koneksi.php';
check_login();

if ($_SESSION['role'] !== 'user') {
    header("Location: ../../dashboard.php?error=Anda tidak memiliki akses ke halaman ini");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$limit = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $limit;

$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($koneksi, $_GET['search'])) : '';

$sql_count = "SELECT COUNT(*) as total FROM buku WHERE stok > 0";
$count_params = [];
$count_types = '';
if (!empty($search)) {
    $sql_count .= " AND judul LIKE ?";
    $search_param_count = "%{$search}%";
    $count_params[] = &$search_param_count;
    $count_types .= 's';
}

$total_books = 0;
if ($stmt_count = mysqli_prepare($koneksi, $sql_count)) {
    if (!empty($search)) {
        mysqli_stmt_bind_param($stmt_count, $count_types, ...$count_params);
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $total_books = $row_count['total'];
    mysqli_stmt_close($stmt_count);
} else {
    die("Error counting books: " . mysqli_error($koneksi));
}

$total_pages = ceil($total_books / $limit);
$current_page = min($current_page, max(1, $total_pages));
$offset = ($current_page - 1) * $limit;
$offset = max(0, $offset);

$sql = "SELECT * FROM buku WHERE stok > 0";
$params = [];
$types = '';
if (!empty($search)) {
    $sql .= " AND judul LIKE ?";
    $search_param = "%{$search}%";
    $params[] = &$search_param;
    $types .= 's';
}
$sql .= " ORDER BY judul ASC LIMIT ? OFFSET ?";
$params[] = &$limit;
$params[] = &$offset;
$types .= 'ii';

$books = [];
if ($stmt = mysqli_prepare($koneksi, $sql)) {
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $books = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    die("Error fetching books: " . mysqli_error($koneksi));
}

$sql_active_loans = "SELECT COUNT(*) as total_loans FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'";
$active_loans = 0;

if ($stmt_loans = mysqli_prepare($koneksi, $sql_active_loans)) {
    mysqli_stmt_bind_param($stmt_loans, "i", $user_id);
    mysqli_stmt_execute($stmt_loans);
    $result_loans = mysqli_stmt_get_result($stmt_loans);
    $row_loans = mysqli_fetch_assoc($result_loans);
    $active_loans = $row_loans['total_loans'];
    mysqli_stmt_close($stmt_loans);
}

$success_message = isset($_GET['success']) ? sanitize($_GET['success']) : '';
$error_message = isset($_GET['error']) ? sanitize($_GET['error']) : '';

mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku - Bumi Library <3</title>
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
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .book-cover-small {
            max-width: 50px;
            max-height: 70px;
            object-fit: cover;
            margin-right: 10px;
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
                    <a class="nav-link text-white" href="../../dashboard.php">
                        <i class="bi bi-house-door-fill me-2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a class="nav-link text-white" href="../buku/list_buku.php">
                        <i class="bi bi-book-fill me-2"></i> Daftar Buku
                    </a>
                </li>
                <li>
                    <a class="nav-link active text-white" href="pinjam_buku.php">
                        <i class="bi bi-journal-arrow-down me-2"></i> Pinjam Buku
                    </a>
                </li>
                <li>
                    <a class="nav-link text-white" href="daftar_pinjaman.php">
                        <i class="bi bi-journal-bookmark-fill me-2"></i> Buku Dipinjam
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong><?php echo htmlspecialchars($username); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="content flex-grow-1 p-3 animate__animated animate__fadeIn">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="animate__animated animate__fadeInLeft">
                        <i class="bi bi-journal-arrow-down text-primary me-2"></i> Pinjam Buku
                    </h2>
                    <div class="badge bg-info text-dark p-2 animate__animated animate__fadeInRight">
                        <i class="bi bi-info-circle me-1"></i> Buku Dipinjam Saat Ini: <span class="fw-bold"><?php echo $active_loans; ?> / 3</span>
                    </div>
                </div>
                <hr class="animate__animated animate__fadeInLeft" style="animation-delay: 0.1s;">

                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="card shadow-sm mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.15s;">
                    <div class="card-header bg-warning-subtle">
                       <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Perhatian Peminjaman
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Setiap peminjaman memiliki durasi maksimal <strong>7 hari</strong>.</li>
                            <li>Anda dapat meminjam maksimal <strong>3 buku</strong> secara bersamaan.</li>
                            <li>Pastikan untuk mengembalikan buku tepat waktu untuk menghindari denda.</li>
                        </ul>
                    </div>
                </div>

                <form method="get" action="pinjam_buku.php" class="mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <div class="input-group shadow-sm">
                        <input type="text" name="search" class="form-control" placeholder="Cari buku berdasarkan judul..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="pinjam_buku.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                    <table class="table table-striped table-bordered table-hover shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Cover</th>
                                <th scope="col">Judul</th>
                                <th scope="col">Pengarang</th>
                                <th scope="col">Genre</th>
                                <th scope="col" class="text-center">Stok</th>
                                <th scope="col" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) > 0): ?>
                                <?php foreach ($books as $index => $book): ?>
                                <tr class="animate__animated animate__fadeIn" style="animation-delay: <?php echo ($index * 0.05) + 0.35; ?>s;">
                                    <th scope="row"><?php echo $offset + $index + 1; ?></th>
                                    <td>
                                        <img src="../../assets/book_images/<?php echo !empty($book['gambar']) ? htmlspecialchars($book['gambar']) : 'default-book.jpg'; ?>"
                                             alt="Cover <?php echo htmlspecialchars($book['judul']); ?>" class="img-thumbnail book-cover-small"
                                             onerror="this.onerror=null; this.src='../../assets/book_images/default-book.jpg';">
                                    </td>
                                    <td><?php echo htmlspecialchars($book['judul']); ?></td>
                                    <td><?php echo htmlspecialchars($book['pengarang']); ?></td>
                                    <td><?php echo htmlspecialchars($book['genre']); ?></td>
                                    <td class="text-center">
                                        <?php if ($book['stok'] > 5): ?>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($book['stok']); ?></span>
                                        <?php elseif ($book['stok'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($book['stok']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($active_loans >= 3): ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Anda telah mencapai batas maksimal peminjaman.">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Batas Pinjam
                                            </button>
                                        <?php elseif ($book['stok'] <= 0) : ?>
                                             <button class="btn btn-sm btn-secondary" disabled title="Stok buku habis.">
                                                <i class="bi bi-x-circle-fill me-1"></i> Stok Habis
                                            </button>
                                        <?php else: ?>
                                            <a href="proses_pinjam.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('Apakah Anda yakin ingin meminjam buku: <?php echo htmlspecialchars(addslashes($book['judul'])); ?>?');">
                                                <i class="bi bi-journal-arrow-down me-1"></i> Pinjam
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="bi bi-bookshelf fs-1 text-muted"></i><br>
                                        Tidak ada buku tersedia saat ini<?php echo !empty($search) ? ' untuk pencarian \'' . htmlspecialchars($search) . '\'' : ''; ?>.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 d-flex justify-content-center animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                    <ul class="pagination shadow-sm">
                        <?php
                        $base_url = "pinjam_buku.php?";
                        if (!empty($search)) {
                            $base_url .= "search=" . urlencode($search) . "&";
                        }
                        $base_url .= "page=";
                        ?>

                        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($current_page <= 1) ? '#' : $base_url . ($current_page - 1); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $base_url . '1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($current_page >= $total_pages) ? '#' : $base_url . ($current_page + 1); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

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
            </div>
        </div>
    </div>

    <script src="../../assets/bootstrap.js/bootstrap.bundle.min.js"></script>
</body>
</html>