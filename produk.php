<?php
/**
 * Katalog Produk - Pekarangan Institute
 * Menampilkan produk berstatus 'tampil' dengan fitur pencarian nama, filter kategori, pengurutan, dan paginasi.
 */

require_once __DIR__ . '/includes/fungsi.php';

$judul_halaman = 'Katalog Produk';
$menu_aktif = 'produk';
$deskripsi_halaman = 'Daftar produk berkualitas hasil budidaya pekarangan dan mitra binaan Pekarangan Institute.';

// Ambil seluruh produk yang berstatus 'tampil' dari RTDB
$semua_produk = produk_semua(true);

// Ekstrak daftar kategori unik
$daftar_kategori = [];
foreach ($semua_produk as $p) {
    $kat = trim((string)($p['kategori'] ?? ''));
    if ($kat !== '' && !in_array($kat, $daftar_kategori, true)) {
        $daftar_kategori[] = $kat;
    }
}
sort($daftar_kategori);

// Tangani parameter filter, pencarian, urutan, dan paginasi
$cari = trim($_GET['cari'] ?? $_GET['q'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$urutan = trim($_GET['urutan'] ?? 'terbaru');
$halaman_ke = max(1, (int)($_GET['halaman'] ?? 1));
$per_halaman = 9;

// Saring data
$hasil_saring = [];
foreach ($semua_produk as $p) {
    if ($kategori !== '' && in_array($kategori, $daftar_kategori, true)) {
        if (($p['kategori'] ?? '') !== $kategori) {
            continue;
        }
    }

    if ($cari !== '') {
        $cari_lower = mb_strtolower($cari);
        $nama_lower = mb_strtolower((string)($p['nama'] ?? ''));
        if (!str_contains($nama_lower, $cari_lower)) {
            continue;
        }
    }

    $hasil_saring[] = $p;
}

// Urutkan data
if ($urutan === 'harga-naik') {
    usort($hasil_saring, fn($a, $b) => ((int)($a['harga'] ?? 0)) <=> ((int)($b['harga'] ?? 0)));
} elseif ($urutan === 'harga-turun') {
    usort($hasil_saring, fn($a, $b) => ((int)($b['harga'] ?? 0)) <=> ((int)($a['harga'] ?? 0)));
} else {
    $urutan = 'terbaru';
    usort($hasil_saring, fn($a, $b) => strcmp($b['dibuat_pada'] ?? '', $a['dibuat_pada'] ?? ''));
}

$total_item = count($hasil_saring);

// Bangun format URL untuk paginasi (mempertahankan query string aktif)
$parameter_url = [];
if ($cari !== '') {
    $parameter_url['cari'] = $cari;
}
if ($kategori !== '') {
    $parameter_url['kategori'] = $kategori;
}
if ($urutan !== 'terbaru') {
    $parameter_url['urutan'] = $urutan;
}
$parameter_url['halaman'] = '%d';
$url_format = url('produk.php?' . http_build_query($parameter_url));

// Hitung data paginasi
$paginasi = paginasi($total_item, $per_halaman, $halaman_ke, $url_format);

// Ambil data produk untuk halaman saat ini
$daftar_produk = array_slice($hasil_saring, $paginasi['offset'], $per_halaman);

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <div class="bagian-kepala">
        <h1>Katalog Produk</h1>
        <p>Setiap pembelian produk mendukung langsung kesejahteraan petani pekarangan dan program sosial kami.</p>
    </div>

    <!-- Bilah Filter & Pencarian -->
    <form action="<?= url('produk.php') ?>" method="GET" class="bar-filter" role="search" aria-label="Filter Produk">
        <div class="form-grup filter-cari">
            <label for="cari" class="form-label">Cari Produk</label>
            <input type="text" id="cari" name="cari" value="<?= e($cari) ?>" class="form-kontrol" placeholder="Ketik nama produk...">
        </div>

        <div class="form-grup filter-pilih">
            <label for="kategori" class="form-label">Kategori</label>
            <select id="kategori" name="kategori" class="form-kontrol">
                <option value="">Semua Kategori</option>
                <?php foreach ($daftar_kategori as $kat): ?>
                    <option value="<?= e($kat) ?>" <?= $kategori === $kat ? 'selected' : '' ?>>
                        <?= e($kat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grup filter-pilih">
            <label for="urutan" class="form-label">Urutkan</label>
            <select id="urutan" name="urutan" class="form-kontrol">
                <option value="terbaru" <?= $urutan === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                <option value="harga-naik" <?= $urutan === 'harga-naik' ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                <option value="harga-turun" <?= $urutan === 'harga-turun' ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
            </select>
        </div>

        <div class="filter-tombol grup-tombol">
            <button type="submit" class="tombol tombol-utama">Terapkan</button>
            <?php if ($cari !== '' || $kategori !== '' || $urutan !== 'terbaru'): ?>
                <a href="<?= url('produk.php') ?>" class="tombol tombol-sekunder" title="Hapus semua filter">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Grid Produk -->
    <?php if (!empty($daftar_produk)): ?>
        <div class="grid-kartu-3">
            <?php foreach ($daftar_produk as $item): ?>
                <?php
                $habis = (int)$item['stok'] <= 0;
                ?>
                <article class="kartu">
                    <div class="foto-wadah">
                        <?php if (!empty($item['foto']) && file_exists(__DIR__ . '/uploads/' . $item['foto'])): ?>
                            <img src="<?= url('uploads/' . e($item['foto'])) ?>" alt="<?= e($item['nama']) ?>">
                        <?php else: ?>
                            <div class="foto-kosong">[Foto]</div>
                        <?php endif; ?>
                    </div>
                    <div class="kartu-badan">
                        <div class="baris-antara-wrap jarak-bawah-xs">
                            <span class="kartu-kategori"><?= e($item['kategori']) ?></span>
                        </div>

                        <h2 class="kartu-judul">
                            <a href="<?= url('produk-detail.php?slug=' . urlencode($item['slug'])) ?>">
                                <?= e($item['nama']) ?>
                            </a>
                        </h2>

                        <p class="kartu-ringkasan"><?= e($item['deskripsi_singkat']) ?></p>

                        <div class="baris-antara jarak-bawah-md">
                            <div class="kartu-harga"><?= rupiah($item['harga']) ?></div>
                            <?php if ($habis): ?>
                                <span class="lencana lencana-merah">Stok habis</span>
                            <?php else: ?>
                                <span class="teks-xs teks-redup">Stok: <?= (int)$item['stok'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="kartu-kaki">
                            <a href="<?= url('produk-detail.php?slug=' . urlencode($item['slug'])) ?>" class="tombol <?= $habis ? 'tombol-sekunder' : 'tombol-utama' ?> tombol-blok">
                                <?= $habis ? 'Lihat Detail' : 'Pesan Sekarang' ?>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Paginasi -->
        <?php if ($paginasi['total_halaman'] > 1): ?>
            <nav class="paginasi-wadah" aria-label="Paginasi Halaman Produk">
                <?php if ($paginasi['ada_sebelum']): ?>
                    <a href="<?= e($paginasi['url_sebelum']) ?>" class="paginasi-link" aria-label="Halaman Sebelumnya">&larr; Sebelumnya</a>
                <?php else: ?>
                    <span class="paginasi-link paginasi-mati" aria-disabled="true">&larr; Sebelumnya</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $paginasi['total_halaman']; $i++): ?>
                    <?php if ($i === $paginasi['halaman']): ?>
                        <span class="paginasi-link paginasi-aktif" aria-current="page"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= e(sprintf($url_format, $i)) ?>" class="paginasi-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($paginasi['ada_berikut']): ?>
                    <a href="<?= e($paginasi['url_berikut']) ?>" class="paginasi-link" aria-label="Halaman Selanjutnya">Selanjutnya &rarr;</a>
                <?php else: ?>
                    <span class="paginasi-link paginasi-mati" aria-disabled="true">Selanjutnya &rarr;</span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="kotak-kosong">
            <h3>Belum ada produk.</h3>
            <p>
                <?php if ($cari !== '' || $kategori !== ''): ?>
                    Tidak ada produk yang cocok dengan kriteria pencarian Anda. Silakan coba kata kunci atau filter lain.
                <?php else: ?>
                    Produk katalog sedang dipersiapkan oleh tim mitra.
                <?php endif; ?>
            </p>
            <?php if ($cari !== '' || $kategori !== '' || $urutan !== 'terbaru'): ?>
                <div class="jarak-atas-md">
                    <a href="<?= url('produk.php') ?>" class="tombol tombol-sekunder">Reset Pencarian</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
