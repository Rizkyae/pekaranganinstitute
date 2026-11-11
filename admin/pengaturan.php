<?php
/**
 * Pengaturan Sistem - Pekarangan Institute
 * Formulir berkelompok untuk mengelola identitas web, teks beranda & tentang, kontak,
 * info rekening pembayaran, batas minimum donasi, alokasi dana sosial/operasional,
 * dan catatan transparansi publik.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'pengaturan';
$judul_halaman = 'Pengaturan Sistem';

$daftar_kunci = [
    'nama_situs', 'tagline', 'hero_judul', 'hero_teks',
    'tentang_teks', 'whatsapp', 'email', 'alamat',
    'info_pembayaran', 'donasi_minimum',
    'alokasi_operasional', 'alokasi_sosial', 'catatan_transparansi'
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cek_csrf()) {
        $errors['umum'] = 'Token keamanan kedaluwarsa atau permintaan tidak valid. Silakan coba lagi.';
    } else {
        $nilai_kirim = [];
        foreach ($daftar_kunci as $kunci) {
            $nilai_kirim[$kunci] = trim($_POST[$kunci] ?? '');
        }

        // Validasi Identitas
        if ($nilai_kirim['nama_situs'] === '') {
            $errors['nama_situs'] = 'Nama situs tidak boleh kosong.';
        }

        // Validasi Kontak
        if ($nilai_kirim['email'] !== '' && !filter_var($nilai_kirim['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format alamat email tidak valid.';
        }

        // Validasi Donasi Minimum
        if (!is_numeric($nilai_kirim['donasi_minimum']) || (int)$nilai_kirim['donasi_minimum'] < 1000) {
            $errors['donasi_minimum'] = 'Nominal donasi minimum harus berupa angka dan minimal Rp 1.000.';
        }

        // Validasi Alokasi Dana (Wajib berjumlah 100%)
        $operasional = (int)$nilai_kirim['alokasi_operasional'];
        $sosial = (int)$nilai_kirim['alokasi_sosial'];

        if ($operasional < 0 || $sosial < 0 || ($operasional + $sosial !== 100)) {
            $errors['alokasi'] = 'Total persentase alokasi operasional (' . $operasional . '%) dan dana sosial (' . $sosial . '%) harus tepat berjumlah 100%.';
        }

        if (empty($errors)) {
            try {
                $sukses = pengaturan_simpan_banyak($nilai_kirim);
                if ($sukses) {
                    pesan_flash('hijau', 'Pengaturan sistem berhasil diperbarui dan langsung diterapkan pada halaman publik.');
                    alihkan('admin/pengaturan.php');
                } else {
                    $errors['umum'] = 'Gagal menyimpan pengaturan ke Firebase RTDB.';
                }
            } catch (Throwable $e) {
                $errors['umum'] = pesan_error_db('Gagal menyimpan pengaturan', $e);
            }
        }
    }
}

// Ambil nilai pengaturan saat ini dari database RTDB
$pengaturan = pengaturan_semua();

// Ambil nilai form (jika ada input kiriman yang error, gunakan kiriman tersebut)
function ambil_val(string $kunci, array $pengaturan, array $post): string
{
    if (isset($post[$kunci])) {
        return (string)$post[$kunci];
    }
    return (string)($pengaturan[$kunci] ?? '');
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<form action="<?= url('admin/pengaturan.php') ?>" method="POST" id="form-pengaturan" novalidate>
    <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">

    <div class="admin-halaman-header">
        <div>
            <h1 class="admin-halaman-judul">Pengaturan Sistem</h1>
            <p class="admin-halaman-subjudul">Sesuaikan konfigurasi situs, informasi kontak, rekening, dan parameter transparansi.</p>
        </div>
        <div class="admin-halaman-aksi">
            <a href="<?= url('admin/tes-koneksi.php') ?>" class="tombol tombol-sekunder" title="Uji koneksi ke Firebase RTDB">
                <span>Uji Koneksi Firebase</span>
            </a>
            <a href="<?= url() ?>" target="_blank" rel="noopener" class="tombol tombol-sekunder" title="Buka website pengguna di tab baru untuk melihat perubahan">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                <span>Lihat Web Pengguna</span>
            </a>
            <button type="submit" class="tombol tombol-utama" id="tombol-simpan-atas">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Simpan Pengaturan</span>
            </button>
        </div>
    </div>

    <!-- Banner Pintasan & Petunjuk Cepat -->
    <div style="background:var(--biru-muda);border:1px solid #C2DCFA;border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;font-size:0.875rem;color:var(--biru-tua);">
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <span>Ubah data di bawah lalu klik <strong>Simpan Pengaturan</strong> (atau tekan <code>Ctrl + S</code>). Perubahan langsung aktif di web pengguna.</span>
        </div>
        <a href="<?= url() ?>" target="_blank" rel="noopener" style="color:var(--biru);font-weight:600;text-decoration:underline;">
            Buka Web Pengguna &rarr;
        </a>
    </div>

    <?php if (!empty($errors['umum'])): ?>
        <div class="kotak-error" role="alert" style="margin-bottom: 1.5rem;">
            <?= e($errors['umum']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors['alokasi'])): ?>
        <div class="kotak-error" role="alert" style="margin-bottom: 1.5rem;">
            <?= e($errors['alokasi']) ?>
        </div>
    <?php endif; ?>

    <!-- Kelompok 1: Identitas Situs -->
    <div class="admin-panel">
        <div class="admin-panel-kepala">
            <h2 class="admin-panel-judul">1. Identitas Situs</h2>
        </div>
        <div class="admin-panel-badan">
            <div class="admin-form-baris">
                <div class="admin-form-grup">
                    <label for="nama_situs">Nama Situs / Inisiatif <span class="wajib">*</span></label>
                    <input 
                        type="text" 
                        id="nama_situs" 
                        name="nama_situs" 
                        class="admin-input" 
                        value="<?= e(ambil_val('nama_situs', $pengaturan, $_POST)) ?>" 
                        required
                    >
                    <?php if (!empty($errors['nama_situs'])): ?>
                        <div class="pesan-error"><?= e($errors['nama_situs']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="admin-form-grup">
                    <label for="tagline">Tagline Situs</label>
                    <input 
                        type="text" 
                        id="tagline" 
                        name="tagline" 
                        class="admin-input" 
                        value="<?= e(ambil_val('tagline', $pengaturan, $_POST)) ?>" 
                        placeholder="Berbelanja, Berbagi, Berdampak"
                    >
                </div>
            </div>
        </div>
    </div>

    <!-- Kelompok 2: Teks Beranda & Tentang -->
    <div class="admin-panel">
        <div class="admin-panel-kepala">
            <h2 class="admin-panel-judul">2. Konten Beranda & Halaman Tentang</h2>
        </div>
        <div class="admin-panel-badan">
            <div class="admin-form-grup">
                <label for="hero_judul">Judul Utama Beranda (Hero)</label>
                <input 
                    type="text" 
                    id="hero_judul" 
                    name="hero_judul" 
                    class="admin-input" 
                    value="<?= e(ambil_val('hero_judul', $pengaturan, $_POST)) ?>"
                >
            </div>

            <div class="admin-form-grup">
                <label for="hero_teks">Teks Deskripsi Beranda (Hero)</label>
                <textarea 
                    id="hero_teks" 
                    name="hero_teks" 
                    rows="3" 
                    class="admin-textarea"
                ><?= e(ambil_val('hero_teks', $pengaturan, $_POST)) ?></textarea>
            </div>

            <div class="admin-form-grup">
                <label for="tentang_teks">Ringkasan Profil Halaman Tentang</label>
                <textarea 
                    id="tentang_teks" 
                    name="tentang_teks" 
                    rows="5" 
                    class="admin-textarea"
                ><?= e(ambil_val('tentang_teks', $pengaturan, $_POST)) ?></textarea>
                <div class="petunjuk" style="margin-top: 0.5rem;">Untuk pengelolaan lengkap seluruh teks, foto hero, tiga langkah, tiga pilar, dan logo, gunakan halaman <a href="<?= url('admin/konten.php') ?>" style="font-weight:600;color:var(--biru);text-decoration:underline;">Konten Halaman Publik</a>.</div>
            </div>
        </div>
    </div>

    <!-- Kelompok 3: Kontak & Alamat -->
    <div class="admin-panel">
        <div class="admin-panel-kepala">
            <h2 class="admin-panel-judul">3. Kontak & Alamat Sekretariat</h2>
        </div>
        <div class="admin-panel-badan">
            <div class="admin-form-baris">
                <div class="admin-form-grup">
                    <label for="whatsapp">Nomor WhatsApp Pengelola</label>
                    <input 
                        type="text" 
                        id="whatsapp" 
                        name="whatsapp" 
                        class="admin-input" 
                        value="<?= e(ambil_val('whatsapp', $pengaturan, $_POST)) ?>" 
                        placeholder="Contoh: 081234567890"
                    >
                </div>

                <div class="admin-form-grup">
                    <label for="email">Alamat Email Resmi</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="admin-input" 
                        value="<?= e(ambil_val('email', $pengaturan, $_POST)) ?>" 
                        placeholder="kontak@pekarangan.org"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <div class="pesan-error"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-form-grup">
                <label for="alamat">Alamat Lengkap Kantor / Sekretariat</label>
                <textarea 
                    id="alamat" 
                    name="alamat" 
                    rows="3" 
                    class="admin-textarea"
                ><?= e(ambil_val('alamat', $pengaturan, $_POST)) ?></textarea>
            </div>
        </div>
    </div>

    <!-- Kelompok 4: Pembayaran & Donasi Minimum -->
    <div class="admin-panel">
        <div class="admin-panel-kepala">
            <h2 class="admin-panel-judul">4. Rekening Pembayaran & Donasi</h2>
        </div>
        <div class="admin-panel-badan">
            <div class="admin-form-grup">
                <label for="info_pembayaran">Informasi Rekening Bank / QRIS (Teks)</label>
                <textarea 
                    id="info_pembayaran" 
                    name="info_pembayaran" 
                    rows="3" 
                    class="admin-textarea" 
                    placeholder="Contoh: Bank Mandiri: 123-00-1234567-8 a.n. Pekarangan Institute"
                ><?= e(ambil_val('info_pembayaran', $pengaturan, $_POST)) ?></textarea>
                <div class="petunjuk">Informasi ini ditampilkan kepada pemesan produk dan donatur sebagai panduan transfer manual.</div>
            </div>

            <div class="admin-form-grup" style="max-width: 320px;">
                <label for="donasi_minimum">Nominal Donasi Minimum (Rp) <span class="wajib">*</span></label>
                <input 
                    type="number" 
                    id="donasi_minimum" 
                    name="donasi_minimum" 
                    class="admin-input" 
                    value="<?= e(ambil_val('donasi_minimum', $pengaturan, $_POST)) ?>" 
                    min="1000" 
                    step="1000" 
                    required
                >
                <?php if (!empty($errors['donasi_minimum'])): ?>
                    <div class="pesan-error"><?= e($errors['donasi_minimum']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Kelompok 5: Alokasi Dana & Transparansi -->
    <div class="admin-panel">
        <div class="admin-panel-kepala">
            <h2 class="admin-panel-judul">5. Alokasi Dana & Catatan Transparansi</h2>
        </div>
        <div class="admin-panel-badan">
            <!-- Peringatan Wajib Sesuai Prompt P3 & AGENTS.md -->
            <div class="kotak-peringatan" role="note" style="margin-bottom: 1.5rem;">
                <strong>Perhatian:</strong> Isi hanya angka yang sudah dikonfirmasi mitra. Bagian ini tampil di halaman publik (Halaman Transparansi). Total alokasi operasional dan sosial harus tepat 100%.
            </div>

            <div class="admin-form-baris">
                <div class="admin-form-grup">
                    <label for="alokasi_operasional">Alokasi Operasional (%) <span class="wajib">*</span></label>
                    <input 
                        type="number" 
                        id="alokasi_operasional" 
                        name="alokasi_operasional" 
                        class="admin-input" 
                        value="<?= e(ambil_val('alokasi_operasional', $pengaturan, $_POST)) ?>" 
                        min="0" 
                        max="100" 
                        required
                    >
                </div>

                <div class="admin-form-grup">
                    <label for="alokasi_sosial">Alokasi Dana Sosial (%) <span class="wajib">*</span></label>
                    <input 
                        type="number" 
                        id="alokasi_sosial" 
                        name="alokasi_sosial" 
                        class="admin-input" 
                        value="<?= e(ambil_val('alokasi_sosial', $pengaturan, $_POST)) ?>" 
                        min="0" 
                        max="100" 
                        required
                    >
                </div>
            </div>

            <!-- Live Status Total Alokasi -->
            <div id="status-total-alokasi" style="margin-top:-0.5rem;margin-bottom:1.25rem;font-size:0.875rem;font-weight:600;"></div>

            <div class="admin-form-grup">
                <label for="catatan_transparansi">Catatan Tambahan Transparansi Dana</label>
                <textarea 
                    id="catatan_transparansi" 
                    name="catatan_transparansi" 
                    rows="3" 
                    class="admin-textarea"
                ><?= e(ambil_val('catatan_transparansi', $pengaturan, $_POST)) ?></textarea>
                <div class="petunjuk">Catatan disclaimer atau rincian kebijakan transparansi untuk publik.</div>
            </div>
        </div>
    </div>

    <!-- Bilah Aksi Simpan Bawah -->
    <div class="admin-aksi-simpan-wadah">
        <div class="admin-aksi-simpan-info">
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <span class="lencana lencana-hijau">Langsung Aktif</span>
                <strong style="color:var(--teks);font-size:0.9375rem;">Simpan Perubahan Pengaturan</strong>
            </div>
            <p style="color:var(--teks-redup);font-size:0.8125rem;margin-top:0.25rem;">
                Perubahan nama situs, kontak, teks beranda, dan parameter lainnya akan langsung diperbarui di seluruh halaman website publik.
            </p>
        </div>
        <div class="admin-aksi-simpan-tombol">
            <a href="<?= url() ?>" target="_blank" rel="noopener" class="tombol tombol-sekunder">
                Lihat Web Pengguna &rarr;
            </a>
            <button type="submit" class="tombol tombol-utama" style="padding:0.75rem 1.75rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Simpan Semua Pengaturan</span>
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
