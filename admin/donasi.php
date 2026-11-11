<?php
/**
 * Manajemen Donasi Masyarakat - Pekarangan Institute
 * Menampilkan daftar donasi, filter status, filter program, pencarian,
 * modal rincian lengkap, tombol konfirmasi WhatsApp, dan aksi status (tanpa tombol hapus).
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'donasi';
$judul_halaman = 'Kelola Donasi';

// Filter & Pencarian
$cari = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$filter_program = trim((string)($_GET['program_id'] ?? ''));
$halaman_ke = max(1, (int)($_GET['halaman'] ?? 1));
$per_halaman = 10;

// Daftar Program untuk Filter
$daftar_program = program_semua();
usort($daftar_program, function ($a, $b) {
    return strcmp($a['judul'] ?? '', $b['judul'] ?? '');
});

// Peta program berdasarkan ID untuk lookup cepat
$peta_program = [];
foreach ($daftar_program as $prog) {
    $peta_program[(string)($prog['id'] ?? '')] = $prog;
}

// Ambil seluruh data donasi
$semua_donasi = donasi_semua();
$hasil_saring = [];

foreach ($semua_donasi as $d) {
    $pid = (string)($d['program_id'] ?? '');
    $prog = $peta_program[$pid] ?? null;
    $d['nama_program'] = $prog['judul'] ?? '-';
    $d['slug_program'] = $prog['slug'] ?? '';

    if ($filter_status !== '' && in_array($filter_status, ['menunggu', 'berhasil', 'gagal'], true)) {
        if (($d['status'] ?? '') !== $filter_status) {
            continue;
        }
    }

    if ($filter_program !== '') {
        if ($pid !== $filter_program) {
            continue;
        }
    }

    if ($cari !== '') {
        $cari_lower = mb_strtolower($cari);
        $kode_lower = mb_strtolower((string)($d['kode'] ?? ''));
        $nama_lower = mb_strtolower((string)($d['nama_donatur'] ?? ''));
        $wa_lower = mb_strtolower((string)($d['whatsapp'] ?? ''));
        if (!str_contains($kode_lower, $cari_lower) && !str_contains($nama_lower, $cari_lower) && !str_contains($wa_lower, $cari_lower)) {
            continue;
        }
    }

    $hasil_saring[] = $d;
}

$total_item = count($hasil_saring);
$url_format = url('admin/donasi.php') . '?' . http_build_query(array_merge($_GET, ['halaman' => '%d']));
$paginasi = paginasi($total_item, $per_halaman, $halaman_ke, $url_format);

$daftar_donasi = array_slice($hasil_saring, $paginasi['offset'], $per_halaman);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <h1 class="admin-halaman-judul">Kelola Donasi</h1>
        <p class="admin-halaman-subjudul">Verifikasi mutasi dana donasi masuk dan hubungi donatur via WhatsApp.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="admin-filter-bar">
    <form action="<?= url('admin/donasi.php') ?>" method="GET">
        <input 
            type="search" 
            name="q" 
            class="admin-input admin-input-cari" 
            placeholder="Cari kode (DON-...), nama donatur, atau no WA..." 
            value="<?= e($cari) ?>"
        >

        <select name="status" class="admin-select" style="min-width: 150px; width: auto;">
            <option value="">Semua Status</option>
            <option value="menunggu" <?= $filter_status === 'menunggu' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
            <option value="berhasil" <?= $filter_status === 'berhasil' ? 'selected' : '' ?>>Berhasil Masuk</option>
            <option value="gagal" <?= $filter_status === 'gagal' ? 'selected' : '' ?>>Gagal / Batal</option>
        </select>

        <select name="program_id" class="admin-select" style="min-width: 180px; width: auto;">
            <option value="">Semua Program</option>
            <?php foreach ($daftar_program as $prg): ?>
                <option value="<?= e($prg['id']) ?>" <?= (string)$filter_program === (string)$prg['id'] ? 'selected' : '' ?>>
                    <?= e($prg['judul']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="tombol tombol-sekunder">Filter</button>
        <?php if ($cari !== '' || $filter_status !== '' || $filter_program > 0): ?>
            <a href="<?= url('admin/donasi.php') ?>" class="tombol-aksi tombol-aksi-sekunder" style="padding: 0.65rem 1rem;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel Donasi -->
<?php if (empty($daftar_donasi)): ?>
    <div class="admin-panel" style="padding: 3rem 1.5rem; text-align: center;">
        <p style="color: var(--teks-redup);">Tidak ada data donasi yang sesuai dengan pencarian atau filter.</p>
    </div>
<?php else: ?>
    <div class="admin-tabel-wadah">
        <table class="admin-tabel">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Donatur</th>
                    <th>Program</th>
                    <th>Nominal</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th style="text-align: right; min-width: 200px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar_donasi as $d): ?>
                    <tr>
                        <!-- Kode -->
                        <td data-label="Kode">
                            <strong><?= e($d['kode']) ?></strong>
                        </td>

                        <!-- Donatur -->
                        <td data-label="Donatur">
                            <?php if (!empty($d['anonim'])): ?>
                                <span style="font-weight:600;">Anonim</span>
                                <div style="font-size:0.75rem;color:var(--teks-redup);">(Nama Asli: <?= e($d['nama_donatur']) ?>)</div>
                            <?php else: ?>
                                <strong><?= e($d['nama_donatur']) ?></strong>
                            <?php endif; ?>
                            <div style="font-size:0.8125rem;color:var(--teks-redup);">WA: <?= e($d['whatsapp']) ?></div>
                        </td>

                        <!-- Program -->
                        <td data-label="Program">
                            <?= e($d['nama_program'] ?? 'Program Umum') ?>
                        </td>

                        <!-- Nominal -->
                        <td data-label="Nominal">
                            <strong style="color: var(--biru);"><?= rupiah($d['jumlah']) ?></strong>
                        </td>

                        <!-- Tanggal -->
                        <td data-label="Tanggal">
                            <?= tanggal_id($d['dibuat_pada']) ?>
                        </td>

                        <!-- Status -->
                        <td data-label="Status">
                            <span class="badge-status status-<?= e($d['status']) ?>"><?= e($d['status']) ?></span>
                            <?php if (!empty($d['pembayaran_dikonfirmasi_donatur_pada']) && $d['status'] === 'menunggu'): ?>
                                <div style="font-size:0.75rem;color:var(--admin-teal-tua);margin-top:0.35rem;">
                                    Donatur mengklaim membayar via QRIS
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Aksi -->
                        <td data-label="Aksi" style="text-align: right;">
                            <div class="admin-aksi-grup" style="justify-content: flex-end;">
                                <!-- Tombol Rincian / Detail Modal -->
                                <button type="button" class="tombol-aksi tombol-aksi-ubah" data-buka-modal="modal-donasi-<?= e($d['id']) ?>">
                                    Rincian
                                </button>

                                <!-- Tandai Berhasil jika belum berhasil -->
                                <?php if ($d['status'] !== 'berhasil'): ?>
                                    <form 
                                        action="<?= url('admin/donasi-aksi.php') ?>" 
                                        method="POST" 
                                        class="form-konfirmasi" 
                                        data-pesan="Apakah dana donasi <?= rupiah($d['jumlah']) ?> dari <?= e($d['nama_donatur']) ?> sudah benar-benar masuk ke rekening? Tandai sebagai Berhasil?"
                                        style="display:inline;"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                        <input type="hidden" name="status_baru" value="berhasil">
                                        <button type="submit" class="tombol-aksi" style="background:var(--hijau-muda);color:var(--hijau);border-color:rgba(30,158,90,0.2);">
                                            Tandai Berhasil
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Tandai Gagal jika belum gagal -->
                                <?php if ($d['status'] !== 'gagal'): ?>
                                    <?php
                                    $pesan_konfirmasi = ($d['status'] === 'berhasil')
                                        ? "PERHATIAN: Donasi ini sebelumnya berstatus BERHASIL. Mengubahnya menjadi Gagal akan MENGURANGI akumulasi dana terkumpul pada program. Apakah Anda yakin?"
                                        : "Tandai donasi ini sebagai Gagal/Batal?";
                                    ?>
                                    <form 
                                        action="<?= url('admin/donasi-aksi.php') ?>" 
                                        method="POST" 
                                        class="form-konfirmasi" 
                                        data-pesan="<?= e($pesan_konfirmasi) ?>"
                                        style="display:inline;"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                        <input type="hidden" name="status_baru" value="gagal">
                                        <button type="submit" class="tombol-aksi tombol-aksi-hapus">
                                            Tandai Gagal
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Rincian Donasi -->
                    <div class="modal-overlay" id="modal-donasi-<?= e($d['id']) ?>">
                        <div class="modal-wadah">
                            <div class="modal-kepala">
                                <h3 style="font-size:1.125rem;font-weight:700;">Rincian Donasi: <?= e($d['kode']) ?></h3>
                                <button type="button" class="modal-tutup" data-tutup-modal aria-label="Tutup modal">&times;</button>
                            </div>
                            <div class="modal-badan">
                                <dl class="daftar-detail">
                                    <dt>Kode Donasi</dt>
                                    <dd>
                                        <strong><?= e($d['kode']) ?></strong>
                                    </dd>

                                    <dt>Program</dt>
                                    <dd><?= e($d['nama_program'] ?? 'Program Umum') ?></dd>

                                    <dt>Nama Asli</dt>
                                    <dd><?= e($d['nama_donatur']) ?></dd>

                                    <dt>Tampilan Publik</dt>
                                    <dd><?= !empty($d['anonim']) ? 'Hamba Allah (Anonim)' : e($d['nama_donatur']) ?></dd>

                                    <dt>WhatsApp</dt>
                                    <dd>
                                        <?= e($d['whatsapp']) ?>
                                        <div style="margin-top:0.375rem;">
                                            <?php
                                            $pesan_wa_donasi = "Halo " . $d['nama_donatur'] . ", kami dari Pekarangan Institute mengonfirmasi donasi Anda dengan kode " . $d['kode'] . " sebesar " . rupiah($d['jumlah']) . " untuk program " . ($d['nama_program'] ?? '') . ". Terima kasih atas kebaikan Anda.";
                                            ?>
                                            <a href="<?= tautan_wa($d['whatsapp'], $pesan_wa_donasi) ?>" target="_blank" rel="noopener" class="tombol tombol-sekunder" style="font-size:0.8125rem;padding:0.4rem 0.75rem;">
                                                Hubungi via WhatsApp
                                            </a>
                                        </div>
                                    </dd>

                                    <dt>Nominal Donasi</dt>
                                    <dd><strong style="font-size:1.25rem;color:var(--biru);"><?= rupiah($d['jumlah']) ?></strong></dd>

                                    <dt>Metode Pembayaran</dt>
                                    <dd><?= e($d['metode_pembayaran'] ?? 'Belum dicatat') ?></dd>

                                    <dt>Pesan / Doa</dt>
                                    <dd style="font-style:italic;color:var(--teks-redup);">
                                        <?= !empty($d['pesan']) ? nl2br(e($d['pesan'])) : '(Tidak ada pesan dari donatur)' ?>
                                    </dd>

                                    <dt>Status</dt>
                                    <dd>
                                        <span class="badge-status status-<?= e($d['status']) ?>"><?= e($d['status']) ?></span>
                                    </dd>

                                    <?php if (!empty($d['pembayaran_dikonfirmasi_donatur_pada'])): ?>
                                        <dt>Klaim pembayaran</dt>
                                        <dd>
                                            Donatur menyatakan sudah membayar via <?= e($d['metode_pembayaran'] ?? 'QRIS') ?> pada
                                            <?= e($d['pembayaran_dikonfirmasi_donatur_pada']) ?>.
                                            <strong>Verifikasi mutasi dana sebelum menandai donasi berhasil.</strong>
                                        </dd>
                                    <?php endif; ?>

                                    <dt>Waktu Donasi</dt>
                                    <dd><?= tanggal_id($d['dibuat_pada']) ?> (<?= date('H:i', strtotime($d['dibuat_pada'])) ?> WIB)</dd>
                                </dl>

                                <div style="display:flex;gap:0.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--garis);justify-content:flex-end;">
                                    <?php if ($d['status'] !== 'berhasil'): ?>
                                        <form action="<?= url('admin/donasi-aksi.php') ?>" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                            <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                            <input type="hidden" name="status_baru" value="berhasil">
                                            <button type="submit" class="tombol tombol-utama">Tandai Berhasil</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($d['status'] !== 'gagal'): ?>
                                        <form action="<?= url('admin/donasi-aksi.php') ?>" method="POST" class="form-konfirmasi" data-pesan="<?= e($pesan_konfirmasi) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                            <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                            <input type="hidden" name="status_baru" value="gagal">
                                            <button type="submit" class="tombol tombol-sekunder" style="color:var(--merah);">Tandai Gagal</button>
                                        </form>
                                    <?php endif; ?>

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
        <nav class="paginasi" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;" aria-label="Navigasi Halaman Donasi">
            <div style="font-size: 0.875rem; color: var(--teks-redup);">
                Halaman <?= $paginasi['halaman'] ?> dari <?= $paginasi['total_halaman'] ?> (Total <?= $total_item ?> donasi)
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
