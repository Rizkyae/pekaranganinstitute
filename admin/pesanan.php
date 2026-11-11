<?php
/**
 * Manajemen Pesanan Produk - Pekarangan Institute
 * Menampilkan daftar transaksi pesanan, filter status, pencarian kode/pemesan,
 * modal rincian alamat dan kontak, tombol WhatsApp langsung, dan tombol transisi status.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'pesanan';
$judul_halaman = 'Kelola Pesanan';

// Filter & Pencarian
$cari = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$halaman_ke = max(1, (int)($_GET['halaman'] ?? 1));
$per_halaman = 10;

// Ambil semua produk untuk lookup stok saat ini
$semua_produk = produk_semua();
$peta_produk = [];
foreach ($semua_produk as $prod) {
    $peta_produk[(string)($prod['id'] ?? '')] = $prod;
}

// Ambil semua pesanan dari RTDB
$semua_pesanan = pesanan_semua();
$hasil_saring = [];

foreach ($semua_pesanan as $p) {
    $pid = (string)($p['produk_id'] ?? '');
    $p['stok_produk_saat_ini'] = (int)($peta_produk[$pid]['stok'] ?? 0);

    if ($filter_status !== '' && in_array($filter_status, ['menunggu', 'dibayar', 'diproses', 'dikirim', 'selesai', 'batal'], true)) {
        if (($p['status'] ?? '') !== $filter_status) {
            continue;
        }
    }

    if ($cari !== '') {
        $cari_lower = mb_strtolower($cari);
        $kode_lower = mb_strtolower((string)($p['kode'] ?? ''));
        $nama_lower = mb_strtolower((string)($p['nama_pemesan'] ?? ''));
        $wa_lower = mb_strtolower((string)($p['whatsapp'] ?? ''));
        $prod_lower = mb_strtolower((string)($p['nama_produk'] ?? ''));
        if (!str_contains($kode_lower, $cari_lower) && !str_contains($nama_lower, $cari_lower) && !str_contains($wa_lower, $cari_lower) && !str_contains($prod_lower, $cari_lower)) {
            continue;
        }
    }

    $hasil_saring[] = $p;
}

$total_item = count($hasil_saring);
$url_format = url('admin/pesanan.php') . '?' . http_build_query(array_merge($_GET, ['halaman' => '%d']));
$paginasi = paginasi($total_item, $per_halaman, $halaman_ke, $url_format);

$daftar_pesanan = array_slice($hasil_saring, $paginasi['offset'], $per_halaman);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <h1 class="admin-halaman-judul">Kelola Pesanan</h1>
        <p class="admin-halaman-subjudul">Pantau transaksi pesanan produk, mutasi pembayaran, dan pengiriman barang ke pembeli.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="admin-filter-bar">
    <form action="<?= url('admin/pesanan.php') ?>" method="GET">
        <input 
            type="search" 
            name="q" 
            class="admin-input admin-input-cari" 
            placeholder="Cari kode (ORD-...), nama pemesan, no WA, produk..." 
            value="<?= e($cari) ?>"
        >

        <select name="status" class="admin-select" style="min-width: 160px; width: auto;">
            <option value="">Semua Status</option>
            <option value="menunggu" <?= $filter_status === 'menunggu' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
            <option value="dibayar" <?= $filter_status === 'dibayar' ? 'selected' : '' ?>>Sudah Dibayar</option>
            <option value="diproses" <?= $filter_status === 'diproses' ? 'selected' : '' ?>>Sedang Diproses</option>
            <option value="dikirim" <?= $filter_status === 'dikirim' ? 'selected' : '' ?>>Sedang Dikirim</option>
            <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
            <option value="batal" <?= $filter_status === 'batal' ? 'selected' : '' ?>>Dibatalkan</option>
        </select>

        <button type="submit" class="tombol tombol-sekunder">Filter</button>
        <?php if ($cari !== '' || $filter_status !== ''): ?>
            <a href="<?= url('admin/pesanan.php') ?>" class="tombol-aksi tombol-aksi-sekunder" style="padding: 0.65rem 1rem;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel Pesanan -->
<?php if (empty($daftar_pesanan)): ?>
    <div class="admin-panel" style="padding: 3rem 1.5rem; text-align: center;">
        <p style="color: var(--teks-redup);">Tidak ada transaksi pesanan yang sesuai dengan kriteria filter.</p>
    </div>
<?php else: ?>
    <div class="admin-tabel-wadah">
        <table class="admin-tabel">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pemesan</th>
                    <th>Produk & Jumlah</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th style="text-align: right; min-width: 220px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar_pesanan as $pes): ?>
                    <tr>
                        <!-- Kode -->
                        <td data-label="Kode">
                            <strong><?= e($pes['kode']) ?></strong>
                        </td>

                        <!-- Pemesan -->
                        <td data-label="Pemesan">
                            <strong><?= e($pes['nama_pemesan']) ?></strong>
                            <div style="font-size:0.8125rem;color:var(--teks-redup);">WA: <?= e($pes['whatsapp']) ?></div>
                        </td>

                        <!-- Produk -->
                        <td data-label="Produk & Jumlah">
                            <?= e($pes['nama_produk']) ?>
                            <div style="font-size:0.8125rem;color:var(--teks-redup);">
                                <?= (int)$pes['jumlah'] ?> pcs &times; <?= rupiah($pes['harga_satuan']) ?>
                            </div>
                        </td>

                        <!-- Total -->
                        <td data-label="Total">
                            <strong style="color: var(--biru);"><?= rupiah($pes['total']) ?></strong>
                        </td>

                        <!-- Tanggal -->
                        <td data-label="Tanggal">
                            <?= tanggal_id($pes['dibuat_pada']) ?>
                        </td>

                        <!-- Status -->
                        <td data-label="Status">
                            <span class="badge-status status-<?= e($pes['status']) ?>"><?= e($pes['status']) ?></span>
                        </td>

                        <!-- Aksi -->
                        <td data-label="Aksi" style="text-align: right;">
                            <div class="admin-aksi-grup" style="justify-content: flex-end;">
                                <!-- Rincian Modal -->
                                <button type="button" class="tombol-aksi tombol-aksi-ubah" data-buka-modal="modal-pesanan-<?= e($pes['id']) ?>">
                                    Rincian
                                </button>

                                <!-- Transisi Cepat Berdasarkan Status Saat Ini -->
                                <?php if ($pes['status'] === 'menunggu'): ?>
                                    <form 
                                        action="<?= url('admin/pesanan-aksi.php') ?>" 
                                        method="POST" 
                                        class="form-konfirmasi" 
                                        data-pesan="Tandai pesanan <?= e($pes['kode']) ?> sebagai SUDAH DIBAYAR? Tindakan ini akan memotong stok produk sebanyak <?= (int)$pes['jumlah'] ?> item."
                                        style="display:inline;"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="id" value="<?= e($pes['id']) ?>">
                                        <input type="hidden" name="status_baru" value="dibayar">
                                        <button type="submit" class="tombol-aksi" style="background:var(--hijau-muda);color:var(--hijau);border-color:rgba(30,158,90,0.2);">
                                            Tandai Dibayar
                                        </button>
                                    </form>
                                <?php elseif ($pes['status'] === 'dibayar'): ?>
                                    <form action="<?= url('admin/pesanan-aksi.php') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="id" value="<?= e($pes['id']) ?>">
                                        <input type="hidden" name="status_baru" value="diproses">
                                        <button type="submit" class="tombol-aksi tombol-aksi-sekunder">Proses</button>
                                    </form>
                                <?php elseif ($pes['status'] === 'diproses'): ?>
                                    <form action="<?= url('admin/pesanan-aksi.php') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="id" value="<?= e($pes['id']) ?>">
                                        <input type="hidden" name="status_baru" value="dikirim">
                                        <button type="submit" class="tombol-aksi tombol-aksi-sekunder">Kirim</button>
                                    </form>
                                <?php elseif ($pes['status'] === 'dikirim'): ?>
                                    <form action="<?= url('admin/pesanan-aksi.php') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="id" value="<?= e($pes['id']) ?>">
                                        <input type="hidden" name="status_baru" value="selesai">
                                        <button type="submit" class="tombol-aksi" style="background:var(--hijau-muda);color:var(--hijau);border-color:rgba(30,158,90,0.2);">Selesai</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Batalkan (Tersedia untuk status menunggu atau dibayar) -->
                                <?php if ($pes['status'] === 'menunggu' || $pes['status'] === 'dibayar'): ?>
                                    <?php
                                    $pesan_batal = ($pes['status'] === 'dibayar')
                                        ? "Pesanan sudah dibayar. Membatalkannya akan MENGEMBALIKAN stok sebanyak " . (int)$pes['jumlah'] . " item ke produk. Apakah Anda yakin?"
                                        : "Apakah Anda yakin ingin membatalkan pesanan ini?";
                                    ?>
                                    <form 
                                        action="<?= url('admin/pesanan-aksi.php') ?>" 
                                        method="POST" 
                                        class="form-konfirmasi" 
                                        data-pesan="<?= e($pesan_batal) ?>"
                                        style="display:inline;"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="id" value="<?= e($pes['id']) ?>">
                                        <input type="hidden" name="status_baru" value="batal">
                                        <button type="submit" class="tombol-aksi tombol-aksi-hapus">Batalkan</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Rincian Lengkap Pesanan -->
                    <div class="modal-overlay" id="modal-pesanan-<?= e($pes['id']) ?>">
                        <div class="modal-wadah">
                            <div class="modal-kepala">
                                <h3 style="font-size:1.125rem;font-weight:700;">Rincian Pesanan: <?= e($pes['kode']) ?></h3>
                                <button type="button" class="modal-tutup" data-tutup-modal aria-label="Tutup modal">&times;</button>
                            </div>
                            <div class="modal-badan">
                                <dl class="daftar-detail">
                                    <dt>Kode Pesanan</dt>
                                    <dd>
                                        <strong><?= e($pes['kode']) ?></strong>
                                    </dd>

                                    <dt>Nama Produk</dt>
                                    <dd>
                                        <?= e($pes['nama_produk']) ?>
                                        <div style="font-size:0.8125rem;color:var(--teks-redup);">
                                            Stok produk saat ini: <?= (int)($pes['stok_produk_saat_ini'] ?? 0) ?> pcs
                                        </div>
                                    </dd>

                                    <dt>Jumlah Pesan</dt>
                                    <dd><strong><?= (int)$pes['jumlah'] ?> pcs</strong></dd>

                                    <dt>Harga Satuan</dt>
                                    <dd><?= rupiah($pes['harga_satuan']) ?></dd>

                                    <dt>Total Tagihan</dt>
                                    <dd><strong style="font-size:1.25rem;color:var(--biru);"><?= rupiah($pes['total']) ?></strong></dd>

                                    <dt>Nama Pemesan</dt>
                                    <dd><?= e($pes['nama_pemesan']) ?></dd>

                                    <dt>Kontak WhatsApp</dt>
                                    <dd>
                                        <?= e($pes['whatsapp']) ?>
                                        <div style="margin-top:0.375rem;">
                                            <?php
                                            $pesan_wa_pesan = "Halo " . $pes['nama_pemesan'] . ", kami dari Pekarangan Institute mengonfirmasi pesanan Anda dengan kode " . $pes['kode'] . " (" . $pes['nama_produk'] . " sebanyak " . (int)$pes['jumlah'] . " pcs, total " . rupiah($pes['total']) . "). Terima kasih.";
                                            ?>
                                            <a href="<?= tautan_wa($pes['whatsapp'], $pesan_wa_pesan) ?>" target="_blank" rel="noopener" class="tombol tombol-sekunder" style="font-size:0.8125rem;padding:0.4rem 0.75rem;">
                                                Hubungi Pemesan via WhatsApp
                                            </a>
                                        </div>
                                    </dd>

                                    <dt>Alamat Lengkap</dt>
                                    <dd style="white-space:pre-wrap;"><?= e($pes['alamat']) ?></dd>

                                    <dt>Catatan Pemesan</dt>
                                    <dd style="font-style:italic;color:var(--teks-redup);">
                                        <?= !empty($pes['catatan']) ? nl2br(e($pes['catatan'])) : '(Tidak ada catatan khusus)' ?>
                                    </dd>

                                    <dt>Status Saat Ini</dt>
                                    <dd>
                                        <span class="badge-status status-<?= e($pes['status']) ?>"><?= e($pes['status']) ?></span>
                                    </dd>

                                    <dt>Waktu Pesanan</dt>
                                    <dd><?= tanggal_id($pes['dibuat_pada']) ?> (<?= date('H:i', strtotime($pes['dibuat_pada'])) ?> WIB)</dd>
                                </dl>

                                <div style="display:flex;gap:0.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--garis);justify-content:flex-end;">
                                    <button type="button" class="tombol tombol-sekunder" data-tutup-modal>Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginasi -->
    <?php if ($paginasi['total_halaman'] > 1): ?>
        <nav class="paginasi" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;" aria-label="Navigasi Halaman Pesanan">
            <div style="font-size: 0.875rem; color: var(--teks-redup);">
                Halaman <?= $paginasi['halaman'] ?> dari <?= $paginasi['total_halaman'] ?> (Total <?= $total_item ?> pesanan)
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
