<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];

$sql = "SELECT id, username, role FROM users ORDER BY username ASC";
$result = mysqli_query($koneksi, $sql);

if ($result) {
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
} else {
    die("Error fetching users: " . mysqli_error($koneksi));
}

mysqli_close($koneksi);

$success_message = isset($_GET['success']) ? sanitize($_GET['success']) : '';
$error_message = isset($_GET['error']) ? sanitize($_GET['error']) : '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Bumi Library <3</title>
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
                <?php if ($role === 'admin'): ?>
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                     <h2 class="animate__animated animate__fadeInLeft">Manajemen User</h2>
                     <a href="tambah_user.php" class="btn btn-success animate__animated animate__fadeInRight"><i class="bi bi-person-plus-fill me-2"></i>Tambah User Baru</a>
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

                <div class="table-responsive animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <table class="table table-striped table-bordered table-hover shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $index => $user): ?>
                                <tr class="animate__animated animate__fadeIn" style="animation-delay: <?php echo ($index * 0.05) + 0.3; ?>s;">
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td class="text-center">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning me-1" title="Edit"><i class="bi bi-pencil-square"></i> Edit</a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="hapus_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini: <?php echo htmlspecialchars(addslashes($user['username'])); ?>?');"><i class="bi bi-trash-fill"></i> Hapus</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-danger" disabled title="Tidak dapat menghapus diri sendiri"><i class="bi bi-trash-fill"></i> Hapus</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada data user ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

    <script src="../../assets/bootstrap.js/bootstrap.bundle.min.js"></script>
</body>
</html>
