<?php
/**
 * Aksi Pemrosesan Status Pesanan - Pekarangan Institute (Firebase RTDB)
 * Mengontrol alur status pesanan (state machine) yang valid dan
 * mengelola pemotongan serta pengembalian stok produk via pembaruan bersyarat (ETag).
 * Tanpa fitur hapus data.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !cek_csrf()) {
    pesan_flash('merah', 'Permintaan tidak valid atau token keamanan kedaluwarsa.');
    alihkan('admin/pesanan.php');
}

$id = trim((string)($_POST['id'] ?? ''));
$status_baru = trim($_POST['status_baru'] ?? '');

if ($id === '' || !in_array($status_baru, ['dibayar', 'diproses', 'dikirim', 'selesai', 'batal'], true)) {
    pesan_flash('merah', 'Parameter pembaruan status pesanan tidak valid.');
    alihkan('admin/pesanan.php');
}

try {
    $pesanan = pesanan_ambil($id);
    if (!$pesanan) {
        pesan_flash('merah', 'Data pesanan tidak ditemukan.');
        alihkan('admin/pesanan.php');
    }

    $status_lama = $pesanan['status'] ?? 'menunggu';

    // Peta transisi status yang sah sesuai aturan bisnis
    $transisi_sah = [
        'menunggu' => ['dibayar', 'batal'],
        'dibayar'  => ['diproses', 'batal'],
        'diproses' => ['dikirim'],
        'dikirim'  => ['selesai'],
        'selesai'  => [],
        'batal'    => []
    ];

    if (!in_array($status_baru, $transisi_sah[$status_lama] ?? [], true)) {
        pesan_flash('merah', 'Transisi status dari "' . $status_lama . '" ke "' . $status_baru . '" tidak diizinkan.');
        alihkan('admin/pesanan.php');
    }

    // Eksekusi pembaruan status dan stok bersyarat ETag
    $sukses = pesanan_ubah_status($id, $status_baru);

    if ($sukses) {
        $kode_pesanan = $pesanan['kode'] ?? $id;
        $jumlah_pesan = (int)($pesanan['jumlah'] ?? 0);

        if ($status_lama === 'menunggu' && $status_baru === 'dibayar') {
            pesan_flash('hijau', 'Status pesanan ' . $kode_pesanan . ' berhasil diubah menjadi Dibayar. Stok produk dipotong ' . $jumlah_pesan . ' item secara aman via ETag.');
        } elseif ($status_lama === 'dibayar' && $status_baru === 'batal') {
            pesan_flash('hijau', 'Pesanan ' . $kode_pesanan . ' dibatalkan. Stok produk sebanyak ' . $jumlah_pesan . ' item telah dikembalikan ke inventaris.');
        } else {
            $label_status = match ($status_baru) {
                'diproses' => 'Diproses',
                'dikirim'  => 'Dikirim',
                'selesai'  => 'Selesai',
                'batal'    => 'Dibatalkan',
                default    => $status_baru,
            };
            pesan_flash('hijau', 'Status pesanan ' . $kode_pesanan . ' berhasil diubah menjadi: ' . $label_status . '.');
        }
    } else {
        pesan_flash('merah', 'Gagal memperbarui status pesanan. Kemungkinan stok produk tidak mencukupi atau terjadi konflik data.');
    }
} catch (Throwable $e) {
    pesan_flash('merah', pesan_error_db('Terjadi kesalahan sistem saat memproses pesanan', $e));
}

alihkan('admin/pesanan.php');
