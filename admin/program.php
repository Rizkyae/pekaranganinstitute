<?php
/**
 * Manajemen Program Sosial - Pekarangan Institute
 * Menampilkan daftar program sosial, target dana, capaian donasi berhasil,
 * persentase progres, tenggat waktu, dan aksi pengubahan status.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'program';
$judul_halaman = 'Kelola Program Sosial';

// Filter Status & Pencarian
$cari = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$halaman_ke = max(1, (int)($_GET['halaman'] ?? 1));
$per_halaman = 10;

// Ambil data program dan donasi dari RTDB
$semua_program = program_semua();
$semua_donasi = donasi_semua();

// Hitung ringkasan donasi per program
$donasi_info_per_program = [];
foreach ($semua_donasi as $d) {
    $pid = (string)($d['program_id'] ?? '');
    if (!isset($donasi_info_per_program[$pid])) {
        $donasi_info_per_program[$pid] = ['jumlah_donasi' => 0, 'total_terkumpul' => 0];
    }
    $donasi_info_per_program[$pid]['jumlah_donasi']++;
    if (($d['status'] ?? '') === 'berhasil') {
        $donasi_info_per_program[$pid]['total_terkumpul'] += (int)($d['jumlah'] ?? 0);
    }
}

// Saring program berdasarkan filter dan pencarian
$hasil_saring = [];
foreach ($semua_program as $p) {
    $id = (string)($p['id'] ?? '');
    $info = $donasi_info_per_program[$id] ?? ['jumlah_donasi' => 0, 'total_terkumpul' => 0];
    $p['jumlah_donasi'] = $info['jumlah_donasi'];
    $p['total_terkumpul'] = $info['total_terkumpul'];

    if ($filter_status !== '' && in_array($filter_status, ['aktif', 'draf', 'selesai'], true)) {
        if (($p['status'] ?? '') !== $filter_status) {
            continue;
        }
    }

    if ($cari !== '') {
        $cari_lower = mb_strtolower($cari);
        $judul_lower = mb_strtolower((string)($p['judul'] ?? ''));
        $lok_lower = mb_strtolower((string)($p['lokasi'] ?? ''));
        $pen_lower = mb_strtolower((string)($p['penerima_manfaat'] ?? ''));
        if (!str_contains($judul_lower, $cari_lower) && !str_contains($lok_lower, $cari_lower) && !str_contains($pen_lower, $cari_lower)) {
            continue;
        }
    }

    $hasil_saring[] = $p;
}

$total_item = count($hasil_saring);

// Siapkan paginasi
$url_format = url('admin/program.php') . '?' . http_build_query(array_merge($_GET, ['halaman' => '%d']));
$paginasi = paginasi($total_item, $per_halaman, $halaman_ke, $url_format);

// Ambil potongan halaman saat ini
$daftar_program = array_slice($hasil_saring, $paginasi['offset'], $per_halaman);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <h1 class="admin-halaman-judul">Program Sosial</h1>
        <p class="admin-halaman-subjudul">Kelola program pemberdayaan dan penyaluran dana kebaikan komunitas.</p>
    </div>
    <div class="admin-halaman-aksi">
        <a href="<?= url('admin/program-form.php') ?>" class="tombol tombol-utama">+ Buat Program Baru</a>
    </div>
</div>

<!-- Filter Bar -->
<div class="admin-filter-bar">
    <form action="<?= url('admin/program.php') ?>" method="GET">
        <input 
            type="search" 
            name="q" 
            class="admin-input admin-input-cari" 
            placeholder="Cari judul program, lokasi, atau penerima manfaat..." 
            value="<?= e($cari) ?>"
        >

        <select name="status" class="admin-select" style="min-width: 150px; width: auto;">
            <option value="">Semua Status</option>
            <option value="aktif" <?= $filter_status === 'aktif' ? 'selected' : '' ?>>Aktif (Menerima Donasi)</option>
            <option value="draf" <?= $filter_status === 'draf' ? 'selected' : '' ?>>Draf (Belum Tampil)</option>
            <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : '' ?>>Selesai (Ditutup)</option>
        </select>

        <button type="submit" class="tombol tombol-sekunder">Filter</button>
        <?php if ($cari !== '' || $filter_status !== ''): ?>
            <a href="<?= url('admin/program.php') ?>" class="tombol-aksi tombol-aksi-sekunder" style="padding: 0.65rem 1rem;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel Data Program -->
<?php if (empty($daftar_program)): ?>
    <div class="admin-panel" style="padding: 3rem 1.5rem; text-align: center;">
        <p style="color: var(--teks-redup); margin-bottom: 1.25rem;">Belum ada program sosial. Buat program pertama Anda.</p>
        <a href="<?= url('admin/program-form.php') ?>" class="tombol tombol-utama">+ Buat Program Baru</a>
    </div>
<?php else: ?>
    <div class="admin-tabel-wadah">
        <table class="admin-tabel">
            <thead>
                <tr>
                    <th>Judul Program</th>
                    <th>Target Dana</th>
                    <th>Terkumpul</th>
                    <th>Progres</th>
                    <th>Tenggat</th>
                    <th>Status</th>
                    <th style="text-align: right; min-width: 220px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar_program as $item): ?>
                    <?php
                    $target = (int)$item['target_dana'];
                    $terkumpul = (int)$item['total_terkumpul'];
                    $persen = $target > 0 ? min(100, (int)round(($terkumpul / $target) * 100)) : 0;
                    ?>
                    <tr>
                        <!-- Judul -->
                        <td data-label="Judul Program">
                            <strong style="color: var(--teks);"><?= e($item['judul']) ?></strong>
                            <div style="font-size: 0.8125rem; color: var(--teks-redup);">
                                Lokasi: <?= e($item['lokasi']) ?> &bull; <?= (int)$item['jumlah_donasi'] ?> kali donasi
                            </div>
                        </td>

                        <!-- Target Dana -->
                        <td data-label="Target Dana"><?= rupiah($target) ?></td>

                        <!-- Terkumpul -->
                        <td data-label="Terkumpul">
                            <strong style="color: var(--biru);"><?= rupiah($terkumpul) ?></strong>
                        </td>

                        <!-- Progres -->
                        <td data-label="Progres">
                            <div style="display:flex;align-items:center;gap:0.5rem;min-width:110px;">
                                <div style="flex:1;background:var(--garis);height:8px;border-radius:4px;overflow:hidden;">
                                    <div style="width:<?= $persen ?>%;background:var(--biru);height:100%;"></div>
                                </div>
                                <span style="font-size:0.8125rem;font-weight:600;"><?= $persen ?>%</span>
                            </div>
                        </td>

                        <!-- Tenggat -->
                        <td data-label="Tenggat">
                            <?= !empty($item['tenggat']) ? tanggal_id($item['tenggat']) : 'Tanpa batas' ?>
                        </td>

                        <!-- Status -->
                        <td data-label="Status">
                            <span class="badge-status status-<?= e($item['status']) ?>">
                                <?= $item['status'] === 'aktif' ? 'Aktif' : ($item['status'] === 'draf' ? 'Draf' : 'Selesai') ?>
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td data-label="Aksi" style="text-align: right;">
                            <div class="admin-aksi-grup" style="justify-content: flex-end;">
                                <a href="<?= url('admin/program-form.php?id=' . urlencode((string)$item['id'])) ?>" class="tombol-aksi tombol-aksi-ubah">
                                    Ubah
                                </a>

                                <!-- Cepat Ubah Status: Aktifkan / Selesaikan -->
                                <?php if ($item['status'] === 'draf'): ?>
                                    <form action="<?= url('admin/program-aksi.php') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="aksi" value="ubah_status">
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <input type="hidden" name="status_baru" value="aktif">
                                        <button type="submit" class="tombol-aksi tombol-aksi-sekunder">Aktifkan</button>
                                    </form>
                                <?php elseif ($item['status'] === 'aktif'): ?>
                                    <form action="<?= url('admin/program-aksi.php') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                        <input type="hidden" name="aksi" value="ubah_status">
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <input type="hidden" name="status_baru" value="selesai">
                                        <button type="submit" class="tombol-aksi tombol-aksi-sekunder">Selesaikan</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Hapus (Hanya jika belum punya riwayat donasi) -->
                                <?php if ((int)$item['jumlah_donasi'] > 0): ?>
                                    <button 
                                        type="button" 
                                        class="tombol-aksi tombol-aksi-sekunder" 
                                        style="opacity:0.6; cursor:not-allowed;" 
                                        title="Program memiliki riwayat donasi, tidak bisa dihapus"
                                        onclick="alert('Program ini sudah memiliki riwayat <?= (int)$item['jumlah_donasi'] ?> donasi dan tidak dapat dihapus demi akuntabilitas laporan publik. Silakan ubah statusnya menjadi Selesai.');"
                                    >
                                        Hapus
                                    </button>
                                <?php else: ?>
                                    <form 
                                        action="<?= url('admin/program-aksi.php') ?>" 
                                        method="POST" 
                                        class="form-konfirmasi" 
                                        data-pesan="Apakah Anda yakin ingin menghapus program '<?= e($item['judul']) ?>'? Tindakan ini tidak dapat dibatalkan."
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
        <nav class="paginasi" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;" aria-label="Navigasi Halaman Program">
            <div style="font-size: 0.875rem; color: var(--teks-redup);">
                Halaman <?= $paginasi['halaman'] ?> dari <?= $paginasi['total_halaman'] ?> (Total <?= $total_item ?> program)
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
