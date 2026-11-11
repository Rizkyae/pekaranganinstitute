<?php
/**
 * Manajemen Produk - Pekarangan Institute
 * Menampilkan daftar produk dengan pencarian, filter status, paginasi 10 item,
 * pratinjau foto, status publik, dan tombol aksi.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'produk';
$judul_halaman = 'Kelola Produk';

// Filter & Pencarian
$cari = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$halaman_ke = max(1, (int)($_GET['halaman'] ?? 1));
$per_halaman = 10;

// Ambil semua data produk dan pesanan dari RTDB
$semua_produk = produk_semua();
$semua_pesanan = pesanan_semua();

// Hitung frekuensi pesanan per produk
$pesanan_per_produk = [];
foreach ($semua_pesanan as $pes) {
    $pid = (string)($pes['produk_id'] ?? '');
    $pesanan_per_produk[$pid] = ($pesanan_per_produk[$pid] ?? 0) + 1;
}

// Saring data berdasarkan filter dan pencarian
$hasil_saring = [];
foreach ($semua_produk as $p) {
    $id = (string)($p['id'] ?? '');
    $p['jumlah_pesanan'] = $pesanan_per_produk[$id] ?? 0;

    if ($filter_status !== '' && in_array($filter_status, ['tampil', 'draf', 'arsip'], true)) {
        if (($p['status'] ?? '') !== $filter_status) {
            continue;
        }
    }

    if ($cari !== '') {
        $cari_lower = mb_strtolower($cari);
        $nama_lower = mb_strtolower((string)($p['nama'] ?? ''));
        $kat_lower = mb_strtolower((string)($p['kategori'] ?? ''));
        $pen_lower = mb_strtolower((string)($p['penjual'] ?? ''));
        if (!str_contains($nama_lower, $cari_lower) && !str_contains($kat_lower, $cari_lower) && !str_contains($pen_lower, $cari_lower)) {
            continue;
        }
    }

    $hasil_saring[] = $p;
}

$total_item = count($hasil_saring);

// Siapkan paginasi
$url_format = url('admin/produk.php') . '?' . http_build_query(array_merge($_GET, ['halaman' => '%d']));
$paginasi = paginasi($total_item, $per_halaman, $halaman_ke, $url_format);

// Ambil potongan item halaman saat ini
$daftar_produk = array_slice($hasil_saring, $paginasi['offset'], $per_halaman);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <h1 class="admin-halaman-judul">Katalog Produk</h1>
        <p class="admin-halaman-subjudul">Kelola data komoditas pangan, herbal, dan hasil pekarangan mitra.</p>
    </div>
    <div class="admin-halaman-aksi">
        <a href="<?= url('admin/produk-form.php') ?>" class="tombol tombol-utama">+ Tambah Produk</a>
    </div>
</div>

<!-- Filter & Pencarian Bar -->
<div class="admin-filter-bar">
    <form action="<?= url('admin/produk.php') ?>" method="GET">
        <input 
            type="search" 
            name="q" 
            class="admin-input admin-input-cari" 
            placeholder="Cari nama produk, kategori, atau penjual..." 
            value="<?= e($cari) ?>"
        >

        <select name="status" class="admin-select" style="min-width: 150px; width: auto;">
            <option value="">Semua Status</option>
            <option value="tampil" <?= $filter_status === 'tampil' ? 'selected' : '' ?>>Tampil di Publik</option>
            <option value="draf" <?= $filter_status === 'draf' ? 'selected' : '' ?>>Draf (Belum Tampil)</option>
            <option value="arsip" <?= $filter_status === 'arsip' ? 'selected' : '' ?>>Diarsipkan</option>
        </select>

        <button type="submit" class="tombol tombol-sekunder">Filter</button>
        <?php if ($cari !== '' || $filter_status !== ''): ?>
            <a href="<?= url('admin/produk.php') ?>" class="tombol-aksi tombol-aksi-sekunder" style="padding: 0.65rem 1rem;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel Data Produk -->
<?php if (empty($daftar_produk)): ?>
    <div class="admin-panel" style="padding: 3rem 1.5rem; text-align: center;">
        <p style="color: var(--teks-redup); margin-bottom: 1.25rem;">Belum ada produk. Tambah produk pertama Anda.</p>
        <a href="<?= url('admin/produk-form.php') ?>" class="tombol tombol-utama">+ Tambah Produk</a>
    </div>
<?php else: ?>
    <div class="admin-tabel-wadah">
        <table class="admin-tabel">
            <thead>
                <tr>
                    <th style="width: 60px;">Foto</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th style="text-align: right; min-width: 220px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar_produk as $item): ?>
                    <tr>
                        <!-- Foto -->
                        <td data-label="Foto">
                            <?php if (!empty($item['foto']) && file_exists(__DIR__ . '/../uploads/' . $item['foto'])): ?>
                                <img src="<?= url('uploads/' . e($item['foto'])) ?>" alt="<?= e($item['nama']) ?>" class="admin-thumb-produk">
                            <?php else: ?>
                                <div class="admin-thumb-kosong">[Foto]</div>
                            <?php endif; ?>
                        </td>

                        <!-- Nama Produk -->
                        <td data-label="Nama Produk">
                            <strong style="color: var(--teks);"><?= e($item['nama']) ?></strong>
                            <div style="font-size: 0.8125rem; color: var(--teks-redup);">
                                Slug: <code><?= e($item['slug']) ?></code>
                            </div>
                        </td>

                        <!-- Kategori -->
                        <td data-label="Kategori"><?= e($item['kategori']) ?></td>

                        <!-- Harga -->
                        <td data-label="Harga"><strong><?= rupiah($item['harga']) ?></strong></td>

                        <!-- Stok -->
                        <td data-label="Stok">
                            <div style="display:inline-flex;align-items:center;gap:0.35rem;">
                                <form action="<?= url('admin/produk-aksi.php') ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                    <input type="hidden" name="aksi" value="ubah_stok_cepat">
                                    <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                    <input type="hidden" name="perubahan" value="-1">
                                    <button type="submit" class="tombol-aksi tombol-aksi-sekunder" style="padding:0.2rem 0.5rem;font-weight:bold;line-height:1;" title="Kurangi 1 stok" <?= (int)$item['stok'] <= 0 ? 'disabled' : '' ?>>-</button>
                                </form>
                                <strong style="min-width:1.5rem;text-align:center;"><?= (int)$item['stok'] ?></strong>
                                <form action="<?= url('admin/produk-aksi.php') ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                    <input type="hidden" name="aksi" value="ubah_stok_cepat">
                                    <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                    <input type="hidden" name="perubahan" value="1">
                                    <button type="submit" class="tombol-aksi tombol-aksi-sekunder" style="padding:0.2rem 0.5rem;font-weight:bold;line-height:1;" title="Tambah 1 stok">+</button>
                                </form>
                            </div>
                        </td>

                        <!-- Status -->
                        <td data-label="Status">
                            <span class="badge-status status-<?= e($item['status']) ?>">
                                <?= $item['status'] === 'tampil' ? 'Tampil' : ($item['status'] === 'draf' ? 'Draf' : 'Arsip') ?>
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td data-label="Aksi" style="text-align: right;">
                            <div class="admin-aksi-grup" style="justify-content: flex-end;">
                                <!-- Ubah -->
                                <a href="<?= url('admin/produk-form.php?id=' . urlencode((string)$item['id'])) ?>" class="tombol-aksi tombol-aksi-ubah">
                                    Ubah
                                </a>

                                <!-- Cepat Ubah Status: Tampilkan / Jadikan Draf -->
                                <?php if ($item['status'] === 'draf' || $item['status'] === 'arsip'): ?>
                                    <form action="<?= url('admin/produk-aksi.php') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="aksi" value="ubah_status">
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <input type="hidden" name="status_baru" value="tampil">
                                        <button type="submit" class="tombol-aksi tombol-aksi-sekunder">Tampilkan</button>
                                    </form>
                                <?php elseif ($item['status'] === 'tampil'): ?>
                                    <form action="<?= url('admin/produk-aksi.php') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="aksi" value="ubah_status">
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <input type="hidden" name="status_baru" value="draf">
                                        <button type="submit" class="tombol-aksi tombol-aksi-sekunder">Jadikan Draf</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Arsipkan jika belum diarsip -->
                                <?php if ($item['status'] !== 'arsip'): ?>
                                    <form action="<?= url('admin/produk-aksi.php') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="aksi" value="ubah_status">
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <input type="hidden" name="status_baru" value="arsip">
                                        <button type="submit" class="tombol-aksi tombol-aksi-sekunder">Arsipkan</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Hapus (Hanya bila belum pernah dipesan) -->
                                <?php if ((int)$item['jumlah_pesanan'] > 0): ?>
                                    <button 
                                        type="button" 
                                        class="tombol-aksi tombol-aksi-sekunder" 
                                        style="opacity: 0.6; cursor: not-allowed;" 
                                        title="Produk pernah dipesan, gunakan Arsipkan" 
                                        onclick="alert('Produk ini pernah dipesan (<?= (int)$item['jumlah_pesanan'] ?> pesanan) dan tidak dapat dihapus demi integritas data transaksi. Silakan gunakan tombol Arsipkan.');"
                                    >
                                        Hapus
                                    </button>
                                <?php else: ?>
                                    <form 
                                        action="<?= url('admin/produk-aksi.php') ?>" 
                                        method="POST" 
                                        class="form-konfirmasi" 
                                        data-pesan="Apakah Anda yakin ingin menghapus produk '<?= e($item['nama']) ?>'? Tindakan ini tidak dapat dibatalkan."
                                        style="display:inline;"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <button type="submit" class="tombol-aksi tombol-aksi-hapus">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginasi -->
    <?php if ($paginasi['total_halaman'] > 1): ?>
        <nav class="paginasi" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;" aria-label="Navigasi Halaman Produk">
            <div style="font-size: 0.875rem; color: var(--teks-redup);">
                Halaman <?= $paginasi['halaman'] ?> dari <?= $paginasi['total_halaman'] ?> (Total <?= $total_item ?> produk)
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <?php if ($paginasi['ada_sebelum']): ?>
                    <a href="<?= e($paginasi['url_sebelum']) ?>" class="tombol-aksi tombol-aksi-sekunder">&larr; Sebelumnya</a>
                <?php endif; ?>
                <?php if ($paginasi['ada_berikut']): ?>
                    <a href="<?= e($paginasi['url_berikut']) ?>" class="tombol-aksi tombol-aksi-sekunder">Berikutnya &rarr;</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
