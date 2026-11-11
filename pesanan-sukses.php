<?php
/**
 * Halaman Pesanan Berhasil - Pekarangan Institute
 * Menampilkan ringkasan pesanan, kode referensi ORD, petunjuk pembayaran, dan konfirmasi WhatsApp.
 * Melindungi privasi pemesan: tidak menampilkan nomor WhatsApp dan alamat lengkap.
 */

require_once __DIR__ . '/includes/fungsi.php';

$kode = trim($_GET['kode'] ?? '');

$pesanan = null;
if ($kode !== '') {
    $pesanan = pesanan_by_kode($kode);
}

// Jika kode tidak ditemukan, tampilkan 404 ramah
if (!$pesanan) {
    http_response_code(404);
    $judul_halaman = 'Pesanan Tidak Ditemukan';
    $menu_aktif = 'produk';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="kontainer">
        <div class="kotak-404">
            <h1>404</h1>
            <h2>Pesanan Tidak Ditemukan</h2>
            <p>Maaf, kode pesanan yang Anda tuju tidak tercatat dalam sistem kami. Pastikan tautan yang Anda gunakan sudah benar.</p>
            <div>
                <a href="<?= url('produk.php') ?>" class="tombol tombol-utama">&larr; Kembali ke Katalog Produk</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$judul_halaman = 'Pesanan Diterima - ' . $pesanan['kode'];
$menu_aktif = 'produk';
$deskripsi_halaman = 'Pesanan Anda telah kami terima dan menunggu verifikasi pembayaran.';

$info_pembayaran = ambil_pengaturan('info_pembayaran', '[Nama Bank] [Nomor Rekening] a.n. Pekarangan Institute');
$nomor_admin_wa  = ambil_pengaturan('whatsapp', '');

// Buat template teks untuk konfirmasi via WhatsApp
$pesan_wa = sprintf(
    "Halo Admin Pekarangan Institute, saya ingin mengonfirmasi pembayaran untuk pesanan:\n\nKode Pesanan: %s\nProduk: %s\nJumlah: %d item\nTotal: %s\n\nMohon petunjuk verifikasi pembayarannya. Terima kasih!",
    $pesanan['kode'],
    $pesanan['nama_produk'],
    (int)$pesanan['jumlah'],
    rupiah($pesanan['total'])
);

$tautan_konfirmasi_wa = tautan_wa($nomor_admin_wa, $pesan_wa);

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <div class="kartu-sukses">
        <div class="sukses-ikon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <div class="teks-tengah jarak-bawah-lg">
            <h1 class="sukses-judul">Pesanan Berhasil Dibuat</h1>
            <p class="teks-redup">
                Terima kasih! Pesanan Anda telah tercatat di sistem kami dan sedang menunggu proses verifikasi pembayaran.
            </p>
        </div>

        <!-- Kode Pesanan Unik -->
        <div class="kode-kotak">
            <div class="kode-kotak-label">Kode Pesanan Anda</div>
            <div class="kode-kotak-nilai"><?= e($pesanan['kode']) ?></div>
            <span class="teks-xs teks-redup blok jarak-atas-xs">
                Simpan kode ini sebagai bukti dan referensi konfirmasi pembayaran Anda.
            </span>
        </div>

        <!-- Banner Verifikasi Manual Sesuai Aturan Bisnis -->
        <div class="banner-peringatan" role="note">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <div>
                <strong>Pembayaran diverifikasi manual oleh admin</strong>
                <p class="jarak-atas-xs teks-sm">
                    Stok produk akan berkurang secara otomatis setelah status pembayaran Anda diverifikasi oleh tim kami.
                </p>
            </div>
        </div>

        <!-- Ringkasan Pesanan (Tanpa PII Alamat/WA Lengkap) -->
        <h3 class="teks-lg jarak-atas-lg jarak-bawah-sm">Ringkasan Pesanan</h3>
        <table class="tabel-ringkasan" aria-label="Rincian Pembelian">
            <thead>
                <tr>
                    <th scope="col">Keterangan</th>
                    <th scope="col" class="teks-kanan">Rincian</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Produk</td>
                    <td><strong><?= e($pesanan['nama_produk']) ?></strong></td>
                </tr>
                <tr>
                    <td>Harga Satuan</td>
                    <td><?= rupiah($pesanan['harga_satuan']) ?></td>
                </tr>
                <tr>
                    <td>Jumlah</td>
                    <td><?= (int)$pesanan['jumlah'] ?> item</td>
                </tr>
                <tr>
                    <td>Status Pesanan</td>
                    <td>
                        <span class="lencana lencana-kuning">Menunggu Pembayaran</span>
                    </td>
                </tr>
                <tr class="baris-total">
                    <td>Total Tagihan</td>
                    <td class="teks-biru"><?= rupiah($pesanan['total']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Instruksi Pembayaran Bank -->
        <div class="kotak-instruksi-bank">
            <h4>Instruksi Pembayaran Transfer:</h4>
            <p class="jarak-bawah-sm">
                Silakan lakukan transfer sebesar <strong><?= rupiah($pesanan['total']) ?></strong> ke rekening resmi berikut:
            </p>
            <div class="kotak-rekening">
                <?= nl2br(e($info_pembayaran)) ?>
            </div>
            <p class="teks-xs teks-redup jarak-nol">
                Sertakan kode <strong><?= e($pesanan['kode']) ?></strong> pada berita transfer jika memungkinkan.
            </p>
        </div>

        <!-- Tombol Aksi Konfirmasi WhatsApp -->
        <div class="grup-aksi-sukses">
            <a href="<?= e($tautan_konfirmasi_wa) ?>" target="_blank" rel="noopener noreferrer" class="tombol tombol-whatsapp tombol-blok">
                Konfirmasi via WhatsApp
            </a>

            <div class="grup-tombol grup-tombol-tengah jarak-atas-sm">
                <a href="<?= url('produk.php') ?>" class="tombol tombol-sekunder">
                    Lanjut Belanja
                </a>
                <a href="<?= url() ?>" class="tombol tombol-sekunder">
                    Halaman Utama
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
