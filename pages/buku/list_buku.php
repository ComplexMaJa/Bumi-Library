<?php
require_once '../../config/koneksi.php';
check_login();

$role = $_SESSION['role'];

// Handle export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $role === 'admin') {
    $export_search = isset($_GET['search']) ? trim(mysqli_real_escape_string($koneksi, $_GET['search'])) : '';
    $export_search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';
    $export_genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
    $export_availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';

    $export_sql = "SELECT id, judul, pengarang, penerbit, tahun_terbit, genre, stok FROM buku WHERE 1=1";
    $export_params = [];
    $export_types = '';

    // Apply same filters as display
    if (!empty($export_search)) {
        if ($export_search_type === 'title') {
            $export_sql .= " AND judul LIKE ?";
        } elseif ($export_search_type === 'author') {
            $export_sql .= " AND pengarang LIKE ?";
        } elseif ($export_search_type === 'publisher') {
            $export_sql .= " AND penerbit LIKE ?";
        } else {
            $export_sql .= " AND (judul LIKE ? OR pengarang LIKE ? OR penerbit LIKE ?)";
        }
    }

    if (!empty($export_genre_filter)) {
        $export_sql .= " AND genre = ?";
    }

    if ($export_availability_filter === 'available') {
        $export_sql .= " AND stok > 0";
    } elseif ($export_availability_filter === 'out_of_stock') {
        $export_sql .= " AND stok = 0";
    }

    $export_sql .= " ORDER BY id ASC";

    // Bind parameters
    if (!empty($export_search)) {
        $export_search_param = "%{$export_search}%";
        if ($export_search_type === 'all') {
            $export_params[] = &$export_search_param;
            $export_params[] = &$export_search_param;
            $export_params[] = &$export_search_param;
            $export_types .= 'sss';
        } else {
            $export_params[] = &$export_search_param;
            $export_types .= 's';
        }
    }

    if (!empty($export_genre_filter)) {
        $export_params[] = &$export_genre_filter;
        $export_types .= 's';
    }

    if ($export_stmt = mysqli_prepare($koneksi, $export_sql)) {
        if (!empty($export_types)) {
            mysqli_stmt_bind_param($export_stmt, $export_types, ...$export_params);
        }
        mysqli_stmt_execute($export_stmt);
        $export_result = mysqli_stmt_get_result($export_stmt);
        $export_books = mysqli_fetch_all($export_result, MYSQLI_ASSOC);
        mysqli_stmt_close($export_stmt);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=daftar_buku_' . date('Y-m-d_H-i-s') . '.csv');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Add CSV headers
        fputcsv($output, ['ID', 'Judul', 'Pengarang', 'Penerbit', 'Tahun Terbit', 'Genre', 'Stok']);

        // Add data rows
        foreach ($export_books as $book) {
            fputcsv($output, [
                $book['id'],
                $book['judul'],
                $book['pengarang'],
                $book['penerbit'],
                $book['tahun_terbit'],
                $book['genre'],
                $book['stok']
            ]);
        }

        fclose($output);
        exit();
    }
}

$limit = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $limit;

$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($koneksi, $_GET['search'])) : '';
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';

// Sorting logic
$allowed_sort_columns = ['id', 'judul', 'pengarang', 'penerbit', 'tahun_terbit', 'genre', 'stok'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort_columns) ? $_GET['sort'] : 'id'; // Default sort by ID
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC'; // Default order ASC

// Get statistics
$stats_sql = "SELECT
    COUNT(*) as total_books,
    SUM(stok) as total_stock,
    COUNT(CASE WHEN stok > 0 THEN 1 END) as available_books,
    COUNT(CASE WHEN stok = 0 THEN 1 END) as out_of_stock,
    COUNT(DISTINCT genre) as total_genres
    FROM buku";
$stats_result = mysqli_query($koneksi, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get genres for filter
$genres_sql = "SELECT DISTINCT genre FROM buku ORDER BY genre";
$genres_result = mysqli_query($koneksi, $genres_sql);
$genres = mysqli_fetch_all($genres_result, MYSQLI_ASSOC);

$sql_count = "SELECT COUNT(*) as total FROM buku WHERE 1=1";
$count_params = [];
$count_types = '';

// Apply search filters
if (!empty($search)) {
    if ($search_type === 'title') {
        $sql_count .= " AND judul LIKE ?";
    } elseif ($search_type === 'author') {
        $sql_count .= " AND pengarang LIKE ?";
    } elseif ($search_type === 'publisher') {
        $sql_count .= " AND penerbit LIKE ?";
    } else { // 'all'
        $sql_count .= " AND (judul LIKE ? OR pengarang LIKE ? OR penerbit LIKE ?)";
    }
}

if (!empty($genre_filter)) {
    $sql_count .= " AND genre = ?";
}

if ($availability_filter === 'available') {
    $sql_count .= " AND stok > 0";
} elseif ($availability_filter === 'out_of_stock') {
    $sql_count .= " AND stok = 0";
}

// Bind parameters for count query
if (!empty($search)) {
    $search_param_count = "%{$search}%";
    if ($search_type === 'all') {
        $count_params[] = &$search_param_count;
        $count_params[] = &$search_param_count;
        $count_params[] = &$search_param_count;
        $count_types .= 'sss';
    } else {
        $count_params[] = &$search_param_count;
        $count_types .= 's';
    }
}

if (!empty($genre_filter)) {
    $count_params[] = &$genre_filter;
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
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $limit;
$offset = max(0, $offset);

$sql = "SELECT * FROM buku WHERE 1=1";
$params = [];
$types = '';

// Apply same filters as count query
if (!empty($search)) {
    if ($search_type === 'title') {
        $sql .= " AND judul LIKE ?";
    } elseif ($search_type === 'author') {
        $sql .= " AND pengarang LIKE ?";
    } elseif ($search_type === 'publisher') {
        $sql .= " AND penerbit LIKE ?";
    } else { // 'all'
        $sql .= " AND (judul LIKE ? OR pengarang LIKE ? OR penerbit LIKE ?)";
    }
}

if (!empty($genre_filter)) {
    $sql .= " AND genre = ?";
}

if ($availability_filter === 'available') {
    $sql .= " AND stok > 0";
} elseif ($availability_filter === 'out_of_stock') {
    $sql .= " AND stok = 0";
}

// Bind parameters for main query
if (!empty($search)) {
    $search_param = "%{$search}%";
    if ($search_type === 'all') {
        $params[] = &$search_param;
        $params[] = &$search_param;
        $params[] = &$search_param;
        $types .= 'sss';
    } else {
        $params[] = &$search_param;
        $types .= 's';
    }
}

if (!empty($genre_filter)) {
    $params[] = &$genre_filter;
    $types .= 's';
}
// Append ORDER BY clause
$sql .= " ORDER BY {$sort_column} {$sort_order} LIMIT ? OFFSET ?";
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

mysqli_close($koneksi);

$success_message = isset($_GET['success']) ? sanitize($_GET['success']) : '';
$error_message = isset($_GET['error']) ? sanitize($_GET['error']) : '';

function generateSortLink($columnName, $displayName, $currentSortColumn, $currentSortOrder, $currentSearch, $searchType = '', $genreFilter = '', $availabilityFilter = '') {
    $orderForLink = 'ASC';
    if ($columnName === $currentSortColumn) {
        $orderForLink = ($currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
    }

    $queryParams = [];
    $queryParams['sort'] = $columnName;
    $queryParams['order'] = $orderForLink;

    if (!empty($currentSearch)) {
        $queryParams['search'] = $currentSearch;
        $queryParams['search_type'] = $searchType;
    }

    if (!empty($genreFilter)) {
        $queryParams['genre'] = $genreFilter;
    }

    if (!empty($availabilityFilter)) {
        $queryParams['availability'] = $availabilityFilter;
    }

    // Sorting resets to page 1
    $queryParams['page'] = 1;

    $url = "list_buku.php?" . http_build_query($queryParams);

    $icon = '';
    if ($columnName === $currentSortColumn) {
        $iconClass = ($currentSortOrder === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down';
        $icon = " <i class=\"bi {$iconClass}\"></i>"; // Escaped quote for class
    }
    return "<a href=\"{$url}\" class=\"text-white text-decoration-none\">" . htmlspecialchars($displayName) . $icon . "</a>"; // Escaped quotes for href and class
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku - Bumi Library <3</title>
    <link href="../../assets/bootstrap.css/css/theme.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>    <style>
        .book-row {
            cursor: pointer;
        }
        .book-row:hover {
            background-color: rgba(0,0,0,0.05) !important;
        }
        .book-cover {
            max-height: 300px;
            object-fit: contain;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .book-cover-small {
            max-width: 50px;
            max-height: 70px;
            object-fit: cover;
            border-radius: .25rem;
        }
        .stats-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .badge-availability {
            font-size: 0.75em;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <nav class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px; min-height: 100vh;">
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
                    <a class="nav-link active text-white" href="list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
                </li>
                <?php if ($role !== 'admin'): ?>
                <li>
                    <a class="nav-link text-white" href="../peminjaman/pinjam_buku.php"><i class="bi bi-journal-arrow-down me-2"></i> Pinjam Buku</a>
                </li>
                <li>
                    <a class="nav-link text-white" href="../peminjaman/daftar_pinjaman.php"><i class="bi bi-journal-bookmark-fill me-2"></i> Buku Dipinjam</a>
                </li>
                <?php endif; ?>
                <?php if ($role === 'admin'): ?>
                <li>
                    <a class="nav-link text-white" href="tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
                </li>
                <li>
                    <a class="nav-link text-white" href="../user/list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
                </li>
                <li>
                    <a class="nav-link text-white" href="../user/tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
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
                    <?php if ($role !== 'admin'): ?>
                    <li><a class="dropdown-item" href="../user/ubah_password.php"><i class="bi bi-key-fill me-2"></i> Ubah Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>        <div class="content flex-grow-1 p-3">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                     <h2 class="animate__animated animate__fadeInLeft">
                         <i class="bi bi-books text-primary me-2"></i>Perpustakaan Digital
                     </h2>
                     <div class="d-flex gap-2">
                         <?php if ($role === 'admin'): ?>
                            <a href="tambah_buku.php" class="btn btn-success animate__animated animate__fadeInRight">
                                <i class="bi bi-plus-lg me-2"></i>Tambah Buku Baru
                            </a>
                            <button class="btn btn-primary" onclick="exportBooks()">
                                <i class="bi bi-download me-2"></i>Export Data
                            </button>
                         <?php endif; ?>
                     </div>
                </div>
                <hr>

                <!-- Statistics Cards -->
                <div class="row mb-4 animate__animated animate__fadeInUp">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card border-0 shadow-sm" style="border-left-color: #0d6efd;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted mb-2">Total Buku</h6>
                                        <h3 class="text-primary mb-0"><?php echo $stats['total_books']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-book-fill text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card border-0 shadow-sm" style="border-left-color: #198754;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted mb-2">Total Stok</h6>
                                        <h3 class="text-success mb-0"><?php echo $stats['total_stock']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-stack text-success" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card border-0 shadow-sm" style="border-left-color: #20c997;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted mb-2">Tersedia</h6>
                                        <h3 class="text-info mb-0"><?php echo $stats['available_books']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-check-circle-fill text-info" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card border-0 shadow-sm" style="border-left-color: #dc3545;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title text-muted mb-2">Habis</h6>
                                        <h3 class="text-danger mb-0"><?php echo $stats['out_of_stock']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>                <!-- Advanced Search and Filters -->
                <div class="filter-section animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                    <h5 class="mb-3"><i class="bi bi-funnel-fill me-2"></i>Pencarian & Filter Lanjutan</h5>
                    <form method="get" action="list_buku.php">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="search" class="form-label">Pencarian</label>
                                <div class="input-group">
                                    <input type="text" name="search" id="search" class="form-control"
                                           placeholder="Masukkan kata kunci..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="search_type" class="form-label">Cari berdasarkan</label>
                                <select name="search_type" id="search_type" class="form-select">
                                    <option value="all" <?php echo $search_type === 'all' ? 'selected' : ''; ?>>Semua</option>
                                    <option value="title" <?php echo $search_type === 'title' ? 'selected' : ''; ?>>Judul</option>
                                    <option value="author" <?php echo $search_type === 'author' ? 'selected' : ''; ?>>Pengarang</option>
                                    <option value="publisher" <?php echo $search_type === 'publisher' ? 'selected' : ''; ?>>Penerbit</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="genre" class="form-label">Genre</label>
                                <select name="genre" id="genre" class="form-select">
                                    <option value="">Semua Genre</option>
                                    <?php foreach ($genres as $genre): ?>
                                        <option value="<?php echo htmlspecialchars($genre['genre']); ?>"
                                                <?php echo $genre_filter === $genre['genre'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($genre['genre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="availability" class="form-label">Ketersediaan</label>
                                <select name="availability" id="availability" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="available" <?php echo $availability_filter === 'available' ? 'selected' : ''; ?>>Tersedia</option>
                                    <option value="out_of_stock" <?php echo $availability_filter === 'out_of_stock' ? 'selected' : ''; ?>>Habis</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i>Terapkan Filter
                            </button>
                            <?php if (!empty($search) || !empty($genre_filter) || !empty($availability_filter)): ?>
                                <a href="list_buku.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Reset Filter
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="table-responsive animate__animated animate__fadeInUp">
                    <table class="table table-striped table-bordered table-hover shadow-sm">                        <thead class="table-dark">
                            <tr>
                                <th><?php echo generateSortLink('id', 'ID', $sort_column, $sort_order, $search, $search_type, $genre_filter, $availability_filter); ?></th>
                                <th scope="col">Cover</th>
                                <th><?php echo generateSortLink('judul', 'Judul', $sort_column, $sort_order, $search, $search_type, $genre_filter, $availability_filter); ?></th>
                                <th><?php echo generateSortLink('pengarang', 'Pengarang', $sort_column, $sort_order, $search, $search_type, $genre_filter, $availability_filter); ?></th>
                                <th><?php echo generateSortLink('penerbit', 'Penerbit', $sort_column, $sort_order, $search, $search_type, $genre_filter, $availability_filter); ?></th>
                                <th><?php echo generateSortLink('tahun_terbit', 'Tahun', $sort_column, $sort_order, $search, $search_type, $genre_filter, $availability_filter); ?></th>
                                <th><?php echo generateSortLink('genre', 'Genre', $sort_column, $sort_order, $search, $search_type, $genre_filter, $availability_filter); ?></th>
                                <th><?php echo generateSortLink('stok', 'Stok', $sort_column, $sort_order, $search, $search_type, $genre_filter, $availability_filter); ?></th>
                                <th scope="col" class="text-center">Status</th>
                                <?php if ($role === 'admin'): ?>
                                    <th class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>                        <tbody>
                            <?php if (count($books) > 0): ?>
                                <?php foreach ($books as $index => $book): ?>
                                <tr class="animate__animated animate__fadeIn book-row" style="animation-delay: <?php echo $index * 0.05; ?>s;"
                                    data-bs-toggle="modal" data-bs-target="#bookModal"
                                    data-id="<?php echo $book['id']; ?>"
                                    data-judul="<?php echo htmlspecialchars($book['judul']); ?>"
                                    data-pengarang="<?php echo htmlspecialchars($book['pengarang']); ?>"
                                    data-penerbit="<?php echo htmlspecialchars($book['penerbit']); ?>"
                                    data-tahun="<?php echo htmlspecialchars($book['tahun_terbit']); ?>"
                                    data-genre="<?php echo htmlspecialchars($book['genre']); ?>"
                                    data-stok="<?php echo htmlspecialchars($book['stok']); ?>"
                                    data-gambar="<?php echo !empty($book['gambar']) ? htmlspecialchars($book['gambar']) : ''; ?>">
                                    <td><?php echo sanitize($book['id']); ?></td>
                                    <td>
                                        <img src="../../assets/book_images/<?php echo htmlspecialchars(!empty($book['gambar']) ? $book['gambar'] : 'contoh.png'); ?>"
                                             alt="Cover <?php echo htmlspecialchars($book['judul']); ?>"
                                             class="book-cover-small img-thumbnail"
                                             onerror="this.onerror=null; this.src='../../assets/book_images/contoh.png';">
                                    </td>
                                    <td><?php echo sanitize($book['judul']); ?></td>
                                    <td><?php echo sanitize($book['pengarang']); ?></td>
                                    <td><?php echo sanitize($book['penerbit']); ?></td>
                                    <td><?php echo sanitize($book['tahun_terbit']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo sanitize($book['genre']); ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold <?php echo $book['stok'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo sanitize($book['stok']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($book['stok'] > 0): ?>
                                            <span class="badge bg-success badge-availability">
                                                <i class="bi bi-check-circle me-1"></i>Tersedia
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-availability">
                                                <i class="bi bi-x-circle me-1"></i>Habis
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($role === 'admin'): ?>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="edit_buku.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning" title="Edit" onclick="event.stopPropagation();">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="hapus_buku.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" title="Hapus"
                                                   onclick="event.stopPropagation(); return confirm('Apakah Anda yakin ingin menghapus buku ini: <?php echo addslashes(sanitize($book['judul'])); ?>?');">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo ($role === 'admin' ? 10 : 9); ?>" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                            <p class="mb-0">Tidak ada data buku ditemukan
                                            <?php
                                            $filters = [];
                                            if (!empty($search)) $filters[] = "pencarian '{$search}'";
                                            if (!empty($genre_filter)) $filters[] = "genre '{$genre_filter}'";
                                            if (!empty($availability_filter)) $filters[] = "status '{$availability_filter}'";
                                            if (!empty($filters)) echo " untuk " . implode(", ", $filters);
                                            ?>.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 d-flex justify-content-center animate__animated animate__fadeInUp">
                    <ul class="pagination shadow-sm">                        <?php
                        $base_url = "list_buku.php?";
                        $params = [];
                        if (!empty($search)) {
                            $params[] = "search=" . urlencode($search);
                            $params[] = "search_type=" . urlencode($search_type);
                        }
                        if (!empty($genre_filter)) {
                            $params[] = "genre=" . urlencode($genre_filter);
                        }
                        if (!empty($availability_filter)) {
                            $params[] = "availability=" . urlencode($availability_filter);
                        }
                        if (!empty($sort_column)) {
                            $params[] = "sort=" . urlencode($sort_column);
                            $params[] = "order=" . urlencode($sort_order);
                        }
                        if (!empty($params)) {
                            $base_url .= implode("&", $params) . "&";
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
                <div class="mt-5 mb-3 pt-4 animate__animated animate__fadeInUp">
                    <hr class="border-2 border-primary opacity-25">
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <div class="text-muted small">
                            <i class="bi bi-book me-1"></i> Bumi Library <3
                        </div>
                        <div class="text-muted small">
                            &copy; <?php echo date('Y'); ?> | Crafted with <i class="bi bi-heart-fill text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Book Modal -->
    <div class="modal fade" id="bookModal" tabindex="-1" aria-labelledby="bookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-5" id="bookModalLabel">Detail Buku</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img id="bookCover" src="../../assets/book_images/contoh.png" alt="Cover Buku" class="img-fluid book-cover shadow-sm rounded">
                        </div>
                        <div class="col-md-8">
                            <h3 id="bookTitle" class="mb-3"></h3>
                            <table class="table">
                                <tr>
                                    <th width="35%">Pengarang</th>
                                    <td id="bookAuthor"></td>
                                </tr>
                                <tr>
                                    <th>Penerbit</th>
                                    <td id="bookPublisher"></td>
                                </tr>
                                <tr>
                                    <th>Tahun Terbit</th>
                                    <td id="bookYear"></td>
                                </tr>
                                <tr>
                                    <th>Genre</th>
                                    <td id="bookGenre"></td>
                                </tr>
                                <tr>
                                    <th>Ketersediaan</th>
                                    <td id="bookStock"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add a default book cover image
            const defaultCover = '../../assets/book_images/contoh.png';

            // Handle book modal data population
            const bookModal = document.getElementById('bookModal');
            if (bookModal) {
                bookModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;

                    // Extract book data
                    const id = button.getAttribute('data-id');
                    const judul = button.getAttribute('data-judul');
                    const pengarang = button.getAttribute('data-pengarang');
                    const penerbit = button.getAttribute('data-penerbit');
                    const tahun = button.getAttribute('data-tahun');
                    const genre = button.getAttribute('data-genre');
                    const stok = button.getAttribute('data-stok');
                    const gambar = button.getAttribute('data-gambar');

                    // Update modal content
                    document.getElementById('bookTitle').textContent = judul;
                    document.getElementById('bookAuthor').textContent = pengarang;
                    document.getElementById('bookPublisher').textContent = penerbit;
                    document.getElementById('bookYear').textContent = tahun;
                    document.getElementById('bookGenre').textContent = genre;
                    document.getElementById('bookStock').textContent = stok + ' buku tersedia';

                    // Set image
                    const coverElement = document.getElementById('bookCover');
                    if (gambar && gambar.trim() !== '') {
                        coverElement.src = '../../assets/book_images/' + gambar;
                    } else {
                        coverElement.src = defaultCover;
                    }

                    // Handle image error
                    coverElement.onerror = function() {
                        this.src = defaultCover;
                    };
                });
            }
        });

        // Export books function
        function exportBooks() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = 'list_buku.php?' + params.toString();
        }

        // Auto-submit form when filters change
        document.addEventListener('change', function(e) {
            if (e.target.matches('#search_type, #genre, #availability')) {
                const searchInput = document.getElementById('search');
                if (searchInput.value.trim() !== '' || e.target.value !== '') {
                    e.target.closest('form').submit();
                }
            }
        });
    </script>
    <script src="../../assets/bootstrap.js/bootstrap.bundle.min.js"></script>
</body>
</html>

