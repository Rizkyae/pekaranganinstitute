<?php
/**
 * Aksi Pemrosesan Donasi - Pekarangan Institute (Firebase RTDB)
 * Menangani perubahan status donasi (menunggu, berhasil, gagal).
 * Tanpa fitur hapus data untuk menjaga transparansi dan akuntabilitas publik.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !cek_csrf()) {
    pesan_flash('merah', 'Permintaan tidak valid atau token keamanan kedaluwarsa.');
    alihkan('admin/donasi.php');
}

$id = trim((string)($_POST['id'] ?? ''));
$status_baru = trim($_POST['status_baru'] ?? '');

if ($id === '' || !in_array($status_baru, ['menunggu', 'berhasil', 'gagal'], true)) {
    pesan_flash('merah', 'Parameter perubahan status donasi tidak valid.');
    alihkan('admin/donasi.php');
}

try {
    $donasi = donasi_ambil($id);
    if (!$donasi) {
        pesan_flash('merah', 'Data donasi tidak ditemukan.');
        alihkan('admin/donasi.php');
    }

    $sukses = donasi_ubah_status($id, $status_baru);
    if ($sukses) {
        $label_status = match ($status_baru) {
            'berhasil' => 'Berhasil (Terkonfirmasi Masuk)',
            'gagal'    => 'Gagal / Dibatalkan',
            default    => 'Menunggu Verifikasi',
        };

        pesan_flash('hijau', 'Status donasi ' . ($donasi['kode'] ?? $id) . ' berhasil diubah menjadi: ' . $label_status . '. Capaian program otomatis diperbarui.');
    } else {
        pesan_flash('merah', 'Gagal memperbarui status donasi.');
    }
} catch (Throwable $e) {
    pesan_flash('merah', pesan_error_db('Gagal memperbarui status donasi', $e));
}

alihkan('admin/donasi.php');
