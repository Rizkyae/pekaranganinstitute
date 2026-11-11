<?php
/**
 * Manajemen Laporan Transparansi - Pekarangan Institute
 * Menampilkan daftar laporan akuntabilitas berkala, status publikasi,
 * unduhan dokumen berkas, dan aksi kelola.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'laporan';
$judul_halaman = 'Kelola Laporan Transparansi';

// Ambil daftar semua laporan dari RTDB
$daftar_laporan = laporan_semua();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <h1 class="admin-halaman-judul">Laporan Transparansi</h1>
        <p class="admin-halaman-subjudul">Publikasikan dokumen pertanggungjawaban dana publik untuk mitra dan masyarakat.</p>
    </div>
    <div class="admin-halaman-aksi">
        <a href="<?= url('admin/laporan-form.php') ?>" class="tombol tombol-utama">+ Unggah Laporan Baru</a>
    </div>
</div>

<?php if (empty($daftar_laporan)): ?>
    <div class="admin-panel" style="padding: 3rem 1.5rem; text-align: center;">
        <p style="color: var(--teks-redup); margin-bottom: 1.25rem;">Belum ada dokumen laporan transparansi yang diunggah.</p>
        <a href="<?= url('admin/laporan-form.php') ?>" class="tombol tombol-utama">+ Unggah Laporan Baru</a>
    </div>
<?php else: ?>
    <div class="admin-tabel-wadah">
        <table class="admin-tabel">
            <thead>
                <tr>
                    <th>Judul Laporan</th>
                    <th>Periode</th>
                    <th>Ringkasan</th>
                    <th>Berkas Dokumen</th>
                    <th>Status</th>
                    <th style="text-align: right; min-width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar_laporan as $lap): ?>
                    <tr>
                        <!-- Judul -->
                        <td data-label="Judul Laporan">
                            <strong style="color: var(--teks);"><?= e($lap['judul']) ?></strong>
                        </td>

                        <!-- Periode -->
                        <td data-label="Periode"><?= e($lap['periode']) ?></td>

                        <!-- Ringkasan -->
                        <td data-label="Ringkasan" style="max-width: 320px;">
                            <div style="font-size:0.875rem;color:var(--teks-redup);line-height:1.4;">
                                <?= e(mb_strimwidth($lap['ringkasan'], 0, 120, '...')) ?>
                            </div>
                        </td>

                        <!-- Berkas Dokumen -->
                        <td data-label="Berkas Dokumen">
                            <?php if (!empty($lap['berkas'])): ?>
                                <a href="<?= url('uploads/' . e($lap['berkas'])) ?>" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:0.35rem;font-weight:600;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    <span>Buka Dokumen &nearr;</span>
                                </a>
                            <?php else: ?>
                                <span style="color:var(--teks-redup);">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td data-label="Status">
                            <span class="badge-status status-<?= e($lap['status']) ?>">
                                <?= $lap['status'] === 'tampil' ? 'Tampil' : 'Draf' ?>
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td data-label="Aksi" style="text-align: right;">
                            <div class="admin-aksi-grup" style="justify-content: flex-end;">
                                <a href="<?= url('admin/laporan-form.php?id=' . urlencode((string)$lap['id'])) ?>" class="tombol-aksi tombol-aksi-ubah">
                                    Ubah
                                </a>

                                <form 
                                    action="<?= url('admin/laporan-aksi.php') ?>" 
                                    method="POST" 
                                    class="form-konfirmasi" 
                                    data-pesan="Apakah Anda yakin ingin menghapus laporan '<?= e($lap['judul']) ?>'? Berkas dokumen di server akan ikut dihapus."
                                    style="display:inline;"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= e($lap['id']) ?>">
                                    <button type="submit" class="tombol-aksi tombol-aksi-hapus">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
