<?php
/**
 * Formulir Tambah / Ubah Program Sosial - Pekarangan Institute
 * Satu form untuk membuat atau mengedit program sosial.
 * Melindungi privasi penerima manfaat dengan peringatan tetap, menampilkan
 * realisasi dana donasi (read-only), dan validasi server yang aman.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'program';

$id = trim((string)($_GET['id'] ?? ''));
$adalah_edit = ($id !== '');
$judul_halaman = $adalah_edit ? 'Ubah Program Sosial' : 'Buat Program Sosial Baru';

$errors = $_SESSION['form_errors'] ?? [];
$input_sesi = $_SESSION['form_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_input']);

$program = null;
$progres_data = ['target' => 0, 'terkumpul' => 0, 'persen' => 0, 'jumlah_donatur' => 0];

if ($adalah_edit) {
    $program = program_ambil($id);
    if (!$program) {
        pesan_flash('merah', 'Program tidak ditemukan.');
        alihkan('admin/program.php');
    }
    $progres_data = progres_program($id);
}

$judul = $input_sesi['judul'] ?? ($program['judul'] ?? '');
$slug = $input_sesi['slug'] ?? ($program['slug'] ?? '');
$ringkasan = $input_sesi['ringkasan'] ?? ($program['ringkasan'] ?? '');
$cerita = $input_sesi['cerita'] ?? ($program['cerita'] ?? '');
$target_dana = $input_sesi['target_dana'] ?? ($program['target_dana'] ?? '');
$tenggat = $input_sesi['tenggat'] ?? ($program['tenggat'] ?? '');
$lokasi = $input_sesi['lokasi'] ?? ($program['lokasi'] ?? '');
$penerima_manfaat = $input_sesi['penerima_manfaat'] ?? ($program['penerima_manfaat'] ?? '');
$status = $input_sesi['status'] ?? ($program['status'] ?? 'aktif');
$foto_lama = $program['foto_sampul'] ?? null;

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <h1 class="admin-halaman-judul"><?= e($judul_halaman) ?></h1>
        <p class="admin-halaman-subjudul">
            <?= $adalah_edit ? 'Perbarui data program dan informasi penyaluran manfaat.' : 'Inisiasi program sosial baru untuk kemandirian komunitas pekarangan.' ?>
        </p>
    </div>
    <div class="admin-halaman-aksi">
        <a href="<?= url('admin/program.php') ?>" class="tombol-aksi tombol-aksi-sekunder">&larr; Kembali ke Daftar</a>
    </div>
</div>

<!-- Peringatan Tetap Privasi Penerima Manfaat Sesuai AGENTS.md -->
<div class="kotak-peringatan" role="alert" style="margin-bottom: 1.5rem;">
    <strong>Perhatian Penting:</strong> Jangan menulis nama atau mengunggah foto penerima manfaat (terutama anak-anak) tanpa persetujuan tertulis. Gunakan sebutan kelompok umum (misal: Kelompok Warga Komunitas atau Komunitas Petani Binaan).
</div>

<?php if ($adalah_edit): ?>
    <!-- Kotak Informasi Donasi Terkumpul (Read-Only) -->
    <div class="kotak-info" style="margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
        <div>
            <strong>Realisasi Donasi Berhasil:</strong> 
            <span style="font-size: 1.125rem; font-weight: 700; color: var(--biru); margin-left: 0.25rem;">
                <?= rupiah($progres_data['terkumpul']) ?>
            </span>
            <span style="color: var(--teks-redup); font-size: 0.875rem;">
                (<?= $progres_data['persen'] ?>% dari target <?= rupiah($progres_data['target']) ?>)
            </span>
        </div>
        <div style="font-size: 0.8125rem; color: var(--teks-redup);">
            Dihitung otomatis dari <?= $progres_data['jumlah_donatur'] ?> transaksi donasi berhasil (tidak bisa diedit manual).
        </div>
    </div>
<?php endif; ?>

<div class="admin-panel">
    <div class="admin-panel-badan">
        <form action="<?= url('admin/program-aksi.php') ?>" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
            <input type="hidden" name="aksi" value="<?= $adalah_edit ? 'ubah' : 'tambah' ?>">
            <?php if ($adalah_edit): ?>
                <input type="hidden" name="id" value="<?= e($program['id']) ?>">
            <?php endif; ?>

            <div class="admin-form-baris">
                <!-- Judul Program -->
                <div class="admin-form-grup" style="flex: 2;">
                    <label for="judul">Judul Program <span class="wajib">*</span></label>
                    <input 
                        type="text" 
                        id="judul" 
                        name="judul" 
                        class="admin-input" 
                        value="<?= e($judul) ?>" 
                        required 
                        data-sumber-slug
                        placeholder="Contoh: Pengembangan Bibit Sayur Pekarangan Mandiri"
                    >
                    <?php if (!empty($errors['judul'])): ?>
                        <div class="pesan-error"><?= e($errors['judul']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Slug URL -->
                <div class="admin-form-grup" style="flex: 1;">
                    <label for="slug">Slug URL <span class="wajib">*</span></label>
                    <input 
                        type="text" 
                        id="slug" 
                        name="slug" 
                        class="admin-input" 
                        value="<?= e($slug) ?>" 
                        required 
                        data-target-slug
                        placeholder="pengembangan-bibit-sayur-pekarangan-mandiri"
                    >
                    <?php if (!empty($errors['slug'])): ?>
                        <div class="pesan-error"><?= e($errors['slug']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-form-baris">
                <!-- Target Dana -->
                <div class="admin-form-grup">
                    <label for="target_dana">Target Dana (Rp) <span class="wajib">*</span></label>
                    <input 
                        type="number" 
                        id="target_dana" 
                        name="target_dana" 
                        class="admin-input" 
                        value="<?= e((string)$target_dana) ?>" 
                        min="10000" 
                        step="10000" 
                        required 
                        placeholder="Contoh: 15000000"
                    >
                    <?php if (!empty($errors['target_dana'])): ?>
                        <div class="pesan-error"><?= e($errors['target_dana']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Tenggat Waktu (Opsional) -->
                <div class="admin-form-grup">
                    <label for="tenggat">Batas Waktu / Tenggat Donasi (Opsional)</label>
                    <input 
                        type="date" 
                        id="tenggat" 
                        name="tenggat" 
                        class="admin-input" 
                        value="<?= e($tenggat) ?>"
                    >
                    <div class="petunjuk">Kosongkan jika program berkelanjutan tanpa batas waktu.</div>
                    <?php if (!empty($errors['tenggat'])): ?>
                        <div class="pesan-error"><?= e($errors['tenggat']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Status Program -->
                <div class="admin-form-grup">
                    <label for="status">Status Program <span class="wajib">*</span></label>
                    <select id="status" name="status" class="admin-select" required>
                        <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif (Tampil & Menerima Donasi)</option>
                        <option value="draf" <?= $status === 'draf' ? 'selected' : '' ?>>Draf (Simpan sementara, belum tampil)</option>
                        <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai (Target tercapai, ditutup)</option>
                    </select>
                    <?php if (!empty($errors['status'])): ?>
                        <div class="pesan-error"><?= e($errors['status']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-form-baris">
                <!-- Lokasi Program -->
                <div class="admin-form-grup">
                    <label for="lokasi">Lokasi Pelaksanaan <span class="wajib">*</span></label>
                    <input 
                        type="text" 
                        id="lokasi" 
                        name="lokasi" 
                        class="admin-input" 
                        value="<?= e($lokasi) ?>" 
                        required 
                        placeholder="Contoh: Desa Sukamaju, Kabupaten Garut"
                    >
                    <?php if (!empty($errors['lokasi'])): ?>
                        <div class="pesan-error"><?= e($errors['lokasi']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Penerima Manfaat (Deskripsi Umum) -->
                <div class="admin-form-grup">
                    <label for="penerima_manfaat">Sasaran Penerima Manfaat (Umum) <span class="wajib">*</span></label>
                    <input 
                        type="text" 
                        id="penerima_manfaat" 
                        name="penerima_manfaat" 
                        class="admin-input" 
                        value="<?= e($penerima_manfaat) ?>" 
                        required 
                        placeholder="Contoh: 30 Keluarga Petani Pekarangan Mandiri"
                    >
                    <?php if (!empty($errors['penerima_manfaat'])): ?>
                        <div class="pesan-error"><?= e($errors['penerima_manfaat']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ringkasan Program -->
            <div class="admin-form-grup">
                <label for="ringkasan">Ringkasan Singkat (Maksimal 255 Karakter) <span class="wajib">*</span></label>
                <input 
                    type="text" 
                    id="ringkasan" 
                    name="ringkasan" 
                    class="admin-input" 
                    maxlength="255" 
                    value="<?= e($ringkasan) ?>" 
                    required 
                    placeholder="Penjelasan singkat latar belakang dan tujuan utama program..."
                >
                <?php if (!empty($errors['ringkasan'])): ?>
                    <div class="pesan-error"><?= e($errors['ringkasan']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Cerita Lengkap Program -->
            <div class="admin-form-grup">
                <label for="cerita">Cerita Lengkap Program & Rencana Penyaluran <span class="wajib">*</span></label>
                <textarea 
                    id="cerita" 
                    name="cerita" 
                    rows="8" 
                    class="admin-textarea" 
                    required 
                    placeholder="Tuliskan latar belakang masalah, urgensi bantuan, dan rincian alokasi penyaluran dana..."
                ><?= e($cerita) ?></textarea>
                <?php if (!empty($errors['cerita'])): ?>
                    <div class="pesan-error"><?= e($errors['cerita']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Foto Sampul -->
            <div class="admin-form-grup">
                <label for="foto_sampul">Foto Sampul Program (JPG, PNG, atau WebP, Maks. 2 MB)</label>
                <input 
                    type="file" 
                    id="foto_sampul" 
                    name="foto_sampul" 
                    class="admin-input" 
                    accept="image/jpeg,image/png,image/webp" 
                    data-pratinjau="pratinjau-sampul"
                >
                <div class="petunjuk">Gunakan foto suasana lahan, kebun komunitas, atau aktivitas penanaman yang tidak menampilkan wajah anak secara dekat tanpa izin.</div>
                <?php if (!empty($errors['foto_sampul'])): ?>
                    <div class="pesan-error"><?= e($errors['foto_sampul']) ?></div>
                <?php endif; ?>

                <div class="admin-pratinjau-wadah" style="<?= !empty($foto_lama) ? 'display:flex;' : 'display:none;' ?>">
                    <img 
                        id="pratinjau-sampul" 
                        src="<?= !empty($foto_lama) ? url('uploads/' . e($foto_lama)) : '#' ?>" 
                        alt="Pratinjau Sampul" 
                        class="admin-pratinjau-img" 
                        style="<?= !empty($foto_lama) ? '' : 'display:none;' ?>"
                    >
                    <?php if (!empty($foto_lama)): ?>
                        <div style="font-size:0.8125rem;color:var(--teks-redup);">
                            Foto saat ini: <code><?= e($foto_lama) ?></code>
                        </div>
                        <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8125rem;cursor:pointer;margin-top:0.35rem;color:var(--merah);">
                            <input type="checkbox" name="hapus_foto" value="1"> Hapus foto sampul program saat ini
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:flex;gap:1rem;margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--garis);">
                <button type="submit" class="tombol tombol-utama">
                    <?= $adalah_edit ? 'Simpan Perubahan Program' : 'Buat Program' ?>
                </button>
                <a href="<?= url('admin/program.php') ?>" class="tombol tombol-sekunder">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
