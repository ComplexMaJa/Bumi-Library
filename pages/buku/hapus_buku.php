<?php
session_start();
include '../../config/koneksi.php'; // Path is correct

// Role check and redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_buku = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($id_buku === false || $id_buku === null) {
        $_SESSION['error_message'] = "ID buku tidak valid.";
        header("Location: list_buku.php");
        exit;
    }

    // Fetch book details to get the image filename before attempting to delete the record
    $stmt_select = $koneksi->prepare("SELECT gambar_buku FROM buku WHERE id_buku = ?");
    if (!$stmt_select) {
        // Log detailed error for admin, generic for user
        error_log("Prepare failed for SELECT gambar_buku: (" . $koneksi->errno . ") " . $koneksi->error);
        $_SESSION['error_message'] = "Terjadi kesalahan dalam memproses permintaan Anda (kode: HDBP-S1).";
        header("Location: list_buku.php");
        exit;
    }
    $stmt_select->bind_param("i", $id_buku);
    if (!$stmt_select->execute()) {
        error_log("Execute failed for SELECT gambar_buku: (" . $stmt_select->errno . ") " . $stmt_select->error);
        $_SESSION['error_message'] = "Terjadi kesalahan dalam memproses permintaan Anda (kode: HDBP-E1).";
        $stmt_select->close();
        $koneksi->close();
        header("Location: list_buku.php");
        exit;
    }
    $result_select = $stmt_select->get_result();
    $book = $result_select->fetch_assoc();
    $stmt_select->close();

    if ($book) {
        $gambar_buku_to_delete = $book['gambar_buku'];

        $koneksi->begin_transaction();

        try {
            // Delete the book record from the database
            $sql_delete = "DELETE FROM buku WHERE id_buku = ?";
            $stmt_delete = $koneksi->prepare($sql_delete);
            if (!$stmt_delete) {
                throw new Exception("Gagal menyiapkan statement hapus (kode: HDBP-P2): " . $koneksi->error);
            }
            $stmt_delete->bind_param("i", $id_buku);

            if (!$stmt_delete->execute()) {
                throw new Exception("Gagal mengeksekusi statement hapus (kode: HDBP-E2): " . $stmt_delete->error);
            }

            if ($stmt_delete->affected_rows > 0) {
                // If deletion from DB was successful, attempt to delete the image file
                if (!empty($gambar_buku_to_delete) && $gambar_buku_to_delete !== 'default.png') { // Assuming 'default.png' is a placeholder
                    $image_path = "../../assets/book_images/" . $gambar_buku_to_delete;
                    if (file_exists($image_path)) {
                        if (!unlink($image_path)) {
                            // Failed to delete image file, but book record is deleted.
                            // Log this error. The main operation is considered successful.
                            error_log("Gagal menghapus file gambar: " . $image_path . " untuk buku ID: " . $id_buku);
                            $_SESSION['warning_message'] = "Buku berhasil dihapus dari database, tetapi file gambar terkait gagal dihapus dari server.";
                        }
                    }
                }
                // Set success message only if no warning was set prior
                if (!isset($_SESSION['warning_message'])) {
                    $_SESSION['success_message'] = "Buku berhasil dihapus.";
                }
                $koneksi->commit();
            } else {
                // No rows affected, book might have been deleted by another process or ID was valid but not found
                throw new Exception("Buku tidak ditemukan atau sudah dihapus sebelumnya.");
            }
            $stmt_delete->close();

        } catch (Exception $e) {
            $koneksi->rollback();
            // Log detailed error for admin, generic for user
            error_log("Exception during book deletion (ID: $id_buku): " . $e->getMessage());
            $_SESSION['error_message'] = "Gagal menghapus buku: Terjadi kesalahan internal (kode: HDBP-EX). " . $e->getMessage();
        }

    } else {
        $_SESSION['error_message'] = "Buku dengan ID " . htmlspecialchars($id_buku) . " tidak ditemukan.";
    }

    if ($koneksi->close()) {
        // Connection closed successfully
    } else {
        // Failed to close connection, log if necessary
        error_log("Gagal menutup koneksi database di hapus_buku.php");
    }
    header("Location: list_buku.php");
    exit;

} else {
    $_SESSION['error_message'] = "Permintaan tidak valid: ID buku tidak disediakan.";
    header("Location: list_buku.php");
    exit;
}
?>
