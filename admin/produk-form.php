<?php
/**
 * Formulir Tambah / Ubah Produk - Pekarangan Institute
 * Satu form untuk tambah dan edit data produk.
 * Dilengkapi slug otomatis, datalist saran kategori, pratinjau foto, validasi server,
 * dan indikator status publik.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'produk';

// Cek apakah mode Edit atau Tambah
$id = trim((string)($_GET['id'] ?? ''));
$adalah_edit = ($id !== '');
$judul_halaman = $adalah_edit ? 'Ubah Produk' : 'Tambah Produk Baru';

// Ambil error atau nilai kembalian dari sesi jika terjadi kesalahan validasi
$errors = $_SESSION['form_errors'] ?? [];
$input_sesi = $_SESSION['form_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_input']);

$produk = null;
if ($adalah_edit) {
    $produk = produk_ambil($id);
    if (!$produk) {
        pesan_flash('merah', 'Produk tidak ditemukan.');
        alihkan('admin/produk.php');
    }
}

// Nilai bawaan formulir
$nama = $input_sesi['nama'] ?? ($produk['nama'] ?? '');
$slug = $input_sesi['slug'] ?? ($produk['slug'] ?? '');
$kategori = $input_sesi['kategori'] ?? ($produk['kategori'] ?? '');
$harga = $input_sesi['harga'] ?? ($produk['harga'] ?? '');
$stok = $input_sesi['stok'] ?? ($produk['stok'] ?? '0');
$deskripsi_singkat = $input_sesi['deskripsi_singkat'] ?? ($produk['deskripsi_singkat'] ?? '');
$deskripsi = $input_sesi['deskripsi'] ?? ($produk['deskripsi'] ?? '');
$penjual = $input_sesi['penjual'] ?? ($produk['penjual'] ?? '');
$status = $input_sesi['status'] ?? ($produk['status'] ?? 'draf');
$foto_lama = $produk['foto'] ?? null;

// Ambil daftar kategori yang ada untuk saran datalist
$daftar_kategori = [];
$semua_produk = produk_semua();
foreach ($semua_produk as $p) {
    $kat = trim((string)($p['kategori'] ?? ''));
    if ($kat !== '' && !in_array($kat, $daftar_kategori, true)) {
        $daftar_kategori[] = $kat;
    }
}
sort($daftar_kategori);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-halaman-header">
    <div>
        <h1 class="admin-halaman-judul"><?= e($judul_halaman) ?></h1>
        <p class="admin-halaman-subjudul">
            <?= $adalah_edit ? 'Perbarui informasi produk mitra binaan.' : 'Tambahkan produk baru ke dalam katalog sistem.' ?>
        </p>
    </div>
    <div class="admin-halaman-aksi">
        <a href="<?= url('admin/produk.php') ?>" class="tombol-aksi tombol-aksi-sekunder">&larr; Kembali ke Daftar</a>
    </div>
</div>

<!-- Indikator Status Publik -->
<div>
    <?php if ($status === 'tampil'): ?>
        <span class="indikator-status-publik indikator-tampil">
            ● Status: Tampil di publik (dapat dilihat dan dipesan pelanggan)
        </span>
    <?php elseif ($status === 'draf'): ?>
        <span class="indikator-status-publik indikator-draf">
            ● Status: Draf (belum tampil di halaman publik)
        </span>
    <?php else: ?>
        <span class="indikator-status-publik indikator-arsip">
            ● Status: Diarsipkan (tidak tampil di publik)
        </span>
    <?php endif; ?>
</div>

<div class="admin-panel">
    <div class="admin-panel-badan">
        <form action="<?= url('admin/produk-aksi.php') ?>" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">
            <input type="hidden" name="aksi" value="<?= $adalah_edit ? 'ubah' : 'tambah' ?>">
            <?php if ($adalah_edit): ?>
                <input type="hidden" name="id" value="<?= e($produk['id']) ?>">
            <?php endif; ?>

            <div class="admin-form-baris">
                <!-- Nama Produk -->
                <div class="admin-form-grup" style="flex: 2;">
                    <label for="nama">Nama Produk <span class="wajib">*</span></label>
                    <input 
                        type="text" 
                        id="nama" 
                        name="nama" 
                        class="admin-input" 
                        value="<?= e($nama) ?>" 
                        required 
                        data-sumber-slug
                        placeholder="Contoh: Kopi Robusta Pekarangan 250g"
                    >
                    <?php if (!empty($errors['nama'])): ?>
                        <div class="pesan-error"><?= e($errors['nama']) ?></div>
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
                        placeholder="kopi-robusta-pekarangan-250g"
                    >
                    <div class="petunjuk">Otomatis dari nama, bisa diubah manual (huruf kecil & tanda minus).</div>
                    <?php if (!empty($errors['slug'])): ?>
                        <div class="pesan-error"><?= e($errors['slug']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-form-baris">
                <!-- Kategori -->
                <div class="admin-form-grup">
                    <label for="kategori">Kategori <span class="wajib">*</span></label>
                    <input 
                        type="text" 
                        id="kategori" 
                        name="kategori" 
                        list="kategori-saran" 
                        class="admin-input" 
                        value="<?= e($kategori) ?>" 
                        required 
                        placeholder="Pilih atau ketik kategori baru"
                    >
                    <datalist id="kategori-saran">
                        <?php foreach ($daftar_kategori as $kat): ?>
                            <option value="<?= e($kat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <?php if (!empty($errors['kategori'])): ?>
                        <div class="pesan-error"><?= e($errors['kategori']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Harga -->
                <div class="admin-form-grup">
                    <label for="harga">Harga (Rp) <span class="wajib">*</span></label>
                    <input 
                        type="number" 
                        id="harga" 
                        name="harga" 
                        class="admin-input" 
                        value="<?= e((string)$harga) ?>" 
                        min="0" 
                        step="500" 
                        required 
                        placeholder="Contoh: 35000"
                    >
                    <?php if (!empty($errors['harga'])): ?>
                        <div class="pesan-error"><?= e($errors['harga']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Stok -->
                <div class="admin-form-grup">
                    <label for="stok">Stok Tersedia <span class="wajib">*</span></label>
                    <input 
                        type="number" 
                        id="stok" 
                        name="stok" 
                        class="admin-input" 
                        value="<?= e((string)$stok) ?>" 
                        min="0" 
                        required 
                        placeholder="Contoh: 15"
                    >
                    <?php if (!empty($errors['stok'])): ?>
                        <div class="pesan-error"><?= e($errors['stok']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Deskripsi Singkat -->
            <div class="admin-form-grup">
                <label for="deskripsi_singkat">Deskripsi Singkat (Maksimal 160 Karakter) <span class="wajib">*</span></label>
                <input 
                    type="text" 
                    id="deskripsi_singkat" 
                    name="deskripsi_singkat" 
                    class="admin-input" 
                    maxlength="160" 
                    value="<?= e($deskripsi_singkat) ?>" 
                    required 
                    placeholder="Ringkasan singkat untuk tampilan kartu produk di katalog"
                >
                <?php if (!empty($errors['deskripsi_singkat'])): ?>
                    <div class="pesan-error"><?= e($errors['deskripsi_singkat']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Deskripsi Lengkap -->
            <div class="admin-form-grup">
                <label for="deskripsi">Deskripsi Lengkap Produk <span class="wajib">*</span></label>
                <textarea 
                    id="deskripsi" 
                    name="deskripsi" 
                    rows="6" 
                    class="admin-textarea" 
                    required 
                    placeholder="Informasi detail bahan, cara konsumsi/penyimpanan, dan latar belakang mitra..."
                ><?= e($deskripsi) ?></textarea>
                <?php if (!empty($errors['deskripsi'])): ?>
                    <div class="pesan-error"><?= e($errors['deskripsi']) ?></div>
                <?php endif; ?>
            </div>

            <div class="admin-form-baris">
                <!-- Penjual / Mitra Komunitas -->
                <div class="admin-form-grup">
                    <label for="penjual">Kelompok Tani / Mitra Penjual (Opsional)</label>
                    <input 
                        type="text" 
                        id="penjual" 
                        name="penjual" 
                        class="admin-input" 
                        value="<?= e($penjual) ?>" 
                        placeholder="Contoh: Kelompok Wanita Tani Sukamaju"
                    >
                    <?php if (!empty($errors['penjual'])): ?>
                        <div class="pesan-error"><?= e($errors['penjual']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Status Produk -->
                <div class="admin-form-grup">
                    <label for="status">Status Produk <span class="wajib">*</span></label>
                    <select id="status" name="status" class="admin-select" required>
                        <option value="draf" <?= $status === 'draf' ? 'selected' : '' ?>>Draf (Simpan sementara, belum tampil)</option>
                        <option value="tampil" <?= $status === 'tampil' ? 'selected' : '' ?>>Tampil (Siap dipublikasikan dan dipesan)</option>
                        <option value="arsip" <?= $status === 'arsip' ? 'selected' : '' ?>>Arsip (Sembunyikan dari katalog)</option>
                    </select>
                    <?php if (!empty($errors['status'])): ?>
                        <div class="pesan-error"><?= e($errors['status']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Unggah Foto Produk -->
            <div class="admin-form-grup">
                <label for="foto">Foto Produk (Format: JPG, PNG, atau WebP, Maks. 2 MB)</label>
                <input 
                    type="file" 
                    id="foto" 
                    name="foto" 
                    class="admin-input" 
                    accept="image/jpeg,image/png,image/webp" 
                    data-pratinjau="pratinjau-foto"
                >
                <div class="petunjuk">Biarkan kosong jika tidak ingin mengubah foto yang sudah ada.</div>
                <?php if (!empty($errors['foto'])): ?>
                    <div class="pesan-error"><?= e($errors['foto']) ?></div>
                <?php endif; ?>

                <!-- Wadah Pratinjau Foto -->
                <div class="admin-pratinjau-wadah" style="<?= !empty($foto_lama) ? 'display:flex;' : 'display:none;' ?>">
                    <img 
                        id="pratinjau-foto" 
                        src="<?= !empty($foto_lama) ? url('uploads/' . e($foto_lama)) : '#' ?>" 
                        alt="Pratinjau Foto" 
                        class="admin-pratinjau-img" 
                        style="<?= !empty($foto_lama) ? '' : 'display:none;' ?>"
                    >
                    <?php if (!empty($foto_lama)): ?>
                        <div style="font-size:0.8125rem;color:var(--teks-redup);">
                            Foto saat ini: <code><?= e($foto_lama) ?></code>
                        </div>
                        <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8125rem;cursor:pointer;margin-top:0.35rem;color:var(--merah);">
                            <input type="checkbox" name="hapus_foto" value="1"> Hapus foto produk saat ini
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tombol Aksi Form -->
            <div style="display:flex;gap:1rem;margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--garis);">
                <button type="submit" class="tombol tombol-utama">
                    <?= $adalah_edit ? 'Simpan Perubahan' : 'Tambah Produk' ?>
                </button>
                <a href="<?= url('admin/produk.php') ?>" class="tombol tombol-sekunder">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
