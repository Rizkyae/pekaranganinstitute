<?php
/**
 * Formulir Tambah / Ubah Laporan Transparansi - Pekarangan Institute
 * Satu form untuk membuat dan mengedit laporan berkala.
 * Mendukung unggah berkas PDF/JPG/PNG maks 5 MB dan kontrol status publikasi.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'laporan';

$id = trim((string)($_GET['id'] ?? ''));
$adalah_edit = ($id !== '');
$judul_halaman = $adalah_edit ? 'Ubah Laporan Transparansi' : 'Unggah Laporan Baru';

$errors = $_SESSION['form_errors'] ?? [];
$input_sesi = $_SESSION['form_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_input']);

$laporan = null;
if ($adalah_edit) {
    $laporan = laporan_ambil($id);
    if (!$laporan) {
        pesan_flash('merah', 'Laporan tidak ditemukan.');
        alihkan('admin/laporan.php');
    }
}

$judul = $input_sesi['judul'] ?? ($laporan['judul'] ?? '');
$periode = $input_sesi['periode'] ?? ($laporan['periode'] ?? '');
$ringkasan = $input_sesi['ringkasan'] ?? ($laporan['ringkasan'] ?? '');
$status = $input_sesi['status'] ?? ($laporan['status'] ?? 'tampil');
$berkas_lama = $laporan['berkas'] ?? null;

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <h1 class="admin-halaman-judul"><?= e($judul_halaman) ?></h1>
        <p class="admin-halaman-subjudul">
            <?= $adalah_edit ? 'Perbarui informasi dan dokumen laporan pertanggungjawaban.' : 'Unggah berkas pertanggungjawaban keuangan dan kegiatan berkala.' ?>
        </p>
    </div>
    <div class="admin-halaman-aksi">
        <a href="<?= url('admin/laporan.php') ?>" class="tombol-aksi tombol-aksi-sekunder">&larr; Kembali ke Daftar</a>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-panel-badan">
        <form action="<?= url('admin/laporan-aksi.php') ?>" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
            <input type="hidden" name="aksi" value="<?= $adalah_edit ? 'ubah' : 'tambah' ?>">
            <?php if ($adalah_edit): ?>
                <input type="hidden" name="id" value="<?= e($laporan['id']) ?>">
            <?php endif; ?>

            <!-- Judul Laporan -->
            <div class="admin-form-grup">
                <label for="judul">Judul Laporan <span class="wajib">*</span></label>
                <input 
                    type="text" 
                    id="judul" 
                    name="judul" 
                    class="admin-input" 
                    value="<?= e($judul) ?>" 
                    required 
                    placeholder="Contoh: Laporan Penyaluran Donasi & Dampak Sosial Q1 2026"
                >
                <?php if (!empty($errors['judul'])): ?>
                    <div class="pesan-error"><?= e($errors['judul']) ?></div>
                <?php endif; ?>
            </div>

            <div class="admin-form-baris">
                <!-- Periode Laporan -->
                <div class="admin-form-grup">
                    <label for="periode">Periode Laporan <span class="wajib">*</span></label>
                    <input 
                        type="text" 
                        id="periode" 
                        name="periode" 
                        class="admin-input" 
                        value="<?= e($periode) ?>" 
                        required 
                        placeholder="Contoh: Januari - Maret 2026"
                    >
                    <?php if (!empty($errors['periode'])): ?>
                        <div class="pesan-error"><?= e($errors['periode']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Status Publikasi -->
                <div class="admin-form-grup">
                    <label for="status">Status Publikasi <span class="wajib">*</span></label>
                    <select id="status" name="status" class="admin-select" required>
                        <option value="tampil" <?= $status === 'tampil' ? 'selected' : '' ?>>Tampil (Bisa diunduh publik di halaman Transparansi)</option>
                        <option value="draf" <?= $status === 'draf' ? 'selected' : '' ?>>Draf (Simpan sementara, belum tampil)</option>
                    </select>
                    <?php if (!empty($errors['status'])): ?>
                        <div class="pesan-error"><?= e($errors['status']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ringkasan Laporan -->
            <div class="admin-form-grup">
                <label for="ringkasan">Ringkasan Laporan <span class="wajib">*</span></label>
                <textarea 
                    id="ringkasan" 
                    name="ringkasan" 
                    rows="5" 
                    class="admin-textarea" 
                    required 
                    placeholder="Penjelasan ringkas mengenai capaian program, jumlah penerima manfaat, dan total alokasi dana..."
                ><?= e($ringkasan) ?></textarea>
                <?php if (!empty($errors['ringkasan'])): ?>
                    <div class="pesan-error"><?= e($errors['ringkasan']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Berkas Dokumen -->
            <div class="admin-form-grup">
                <label for="berkas">Berkas Dokumen (PDF, JPG, atau PNG, Maksimal 5 MB) <?= !$adalah_edit ? '<span class="wajib">*</span>' : '' ?></label>
                <input 
                    type="file" 
                    id="berkas" 
                    name="berkas" 
                    class="admin-input" 
                    accept="application/pdf,image/jpeg,image/png"
                    <?= !$adalah_edit ? 'required' : '' ?>
                >
                <?php if ($adalah_edit && !empty($berkas_lama)): ?>
                    <div class="petunjuk" style="margin-top:0.375rem;">
                        Berkas saat ini: <a href="<?= url('uploads/' . e($berkas_lama)) ?>" target="_blank" rel="noopener"><code><?= e($berkas_lama) ?></code></a>. Biarkan kosong jika tidak ingin mengganti berkas.
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors['berkas'])): ?>
                    <div class="pesan-error"><?= e($errors['berkas']) ?></div>
                <?php endif; ?>
            </div>

            <div style="display:flex;gap:1rem;margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--garis);">
                <button type="submit" class="tombol tombol-utama">
                    <?= $adalah_edit ? 'Simpan Perubahan Laporan' : 'Unggah Laporan' ?>
                </button>
                <a href="<?= url('admin/laporan.php') ?>" class="tombol tombol-sekunder">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
