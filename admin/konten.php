<?php
/**
 * Kelola Konten Halaman Publik - Pekarangan Institute
 * Satu halaman terpusat untuk mengelola teks, foto, dan logo halaman publik
 * (Beranda, Tentang, Footer, Logo Mitra & Instansi).
 * Teks bawaan tersimpan di kode sebagai cadangan agar situs tidak pernah kosong.
 */

require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();

$menu_aktif = 'konten';
$judul_halaman = 'Konten Halaman Publik';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (permintaan_post_melebihi_batas()) {
        $errors['umum'] = 'Ukuran permintaan melampaui batas post_max_size pada PHP. Kecilkan total berkas unggahan atau sesuaikan konfigurasi server.';
    } elseif (!cek_csrf()) {
        $errors['umum'] = 'Token keamanan kedaluwarsa atau permintaan tidak valid. Silakan coba lagi.';
    } else {
        $aksi = trim($_POST['aksi'] ?? '');

        // 1. Aksi Kembalikan ke Bawaan per Bagian
        if (str_starts_with($aksi, 'reset_')) {
            $bagian = substr($aksi, 6);
            if (in_array($bagian, ['beranda', 'tentang', 'footer', 'logo'], true)) {
                $sukses = konten_reset_bagian($bagian);
                if ($sukses) {
                    $nama_label = match ($bagian) {
                        'beranda' => 'Konten Beranda',
                        'tentang' => 'Konten Halaman Tentang',
                        'footer'  => 'Konten Footer',
                        'logo'    => 'Logo Mitra & Instansi',
                        default   => 'Konten'
                    };
                    pesan_flash('hijau', $nama_label . ' berhasil dikembalikan ke pengaturan bawaan.');
                    alihkan('admin/konten.php');
                } else {
                    $errors['umum'] = 'Gagal mengembalikan konten ke bawaan.';
                }
            } else {
                $errors['umum'] = 'Bagian konten tidak dikenali.';
            }
        }

        // 2. Aksi Simpan Konten
        if ($aksi === 'simpan' || $aksi === 'simpan_beranda' || $aksi === 'simpan_tentang' || $aksi === 'simpan_footer' || $aksi === 'simpan_logo') {
            $data_simpan = [];
            $file_baru_diunggah = [];
            $file_lama_dihapus = [];
            $konten_saat_ini = konten_semua();

            // --- A. Logo Mitra & Instansi ---
            if ($aksi === 'simpan' || $aksi === 'simpan_logo') {
                // Logo Mitra
                $logo_mitra_lama = $konten_saat_ini['logo_mitra'];
                if (!empty($_POST['hapus_logo_mitra']) && empty($_FILES['logo_mitra']['name'])) {
                    if (!empty($logo_mitra_lama)) $file_lama_dihapus[] = $logo_mitra_lama;
                    $data_simpan['logo_mitra'] = '';
                } elseif (!empty($_FILES['logo_mitra']['name'])) {
                    $unggah = unggah_berkas($_FILES['logo_mitra'], 'logo');
                    if (!$unggah['sukses']) {
                        $errors['logo_mitra'] = $unggah['pesan'];
                    } else {
                        $file_baru_diunggah[] = $unggah['nama'];
                        if (!empty($logo_mitra_lama)) $file_lama_dihapus[] = $logo_mitra_lama;
                        $data_simpan['logo_mitra'] = $unggah['nama'];
                    }
                }

                // Logo Instansi
                $logo_instansi_lama = $konten_saat_ini['logo_instansi'];
                if (!empty($_POST['hapus_logo_instansi']) && empty($_FILES['logo_instansi']['name'])) {
                    if (!empty($logo_instansi_lama)) $file_lama_dihapus[] = $logo_instansi_lama;
                    $data_simpan['logo_instansi'] = '';
                } elseif (!empty($_FILES['logo_instansi']['name'])) {
                    $unggah = unggah_berkas($_FILES['logo_instansi'], 'logo');
                    if (!$unggah['sukses']) {
                        $errors['logo_instansi'] = $unggah['pesan'];
                    } else {
                        $file_baru_diunggah[] = $unggah['nama'];
                        if (!empty($logo_instansi_lama)) $file_lama_dihapus[] = $logo_instansi_lama;
                        $data_simpan['logo_instansi'] = $unggah['nama'];
                    }
                }
            }

            // --- B. Beranda: Hero & Langkah ---
            if ($aksi === 'simpan' || $aksi === 'simpan_beranda') {
                if (isset($_POST['hero_judul'])) $data_simpan['hero_judul'] = trim($_POST['hero_judul']);
                if (isset($_POST['hero_teks'])) $data_simpan['hero_teks'] = trim($_POST['hero_teks']);

                // Foto Hero
                $hero_foto_lama = $konten_saat_ini['hero_foto'];
                if (!empty($_POST['hapus_hero_foto']) && empty($_FILES['hero_foto']['name'])) {
                    if (!empty($hero_foto_lama)) $file_lama_dihapus[] = $hero_foto_lama;
                    $data_simpan['hero_foto'] = '';
                } elseif (!empty($_FILES['hero_foto']['name'])) {
                    $unggah = unggah_berkas($_FILES['hero_foto'], 'gambar');
                    if (!$unggah['sukses']) {
                        $errors['hero_foto'] = $unggah['pesan'];
                    } else {
                        $file_baru_diunggah[] = $unggah['nama'];
                        if (!empty($hero_foto_lama)) $file_lama_dihapus[] = $hero_foto_lama;
                        $data_simpan['hero_foto'] = $unggah['nama'];
                    }
                }

                // Tiga Langkah
                for ($i = 1; $i <= 3; $i++) {
                    if (isset($_POST["langkah_{$i}_judul"])) $data_simpan["langkah_{$i}_judul"] = trim($_POST["langkah_{$i}_judul"]);
                    if (isset($_POST["langkah_{$i}_teks"])) $data_simpan["langkah_{$i}_teks"] = trim($_POST["langkah_{$i}_teks"]);
                }
            }

            // --- C. Halaman Tentang ---
            if ($aksi === 'simpan' || $aksi === 'simpan_tentang') {
                if (isset($_POST['tentang_teks'])) $data_simpan['tentang_teks'] = trim($_POST['tentang_teks']);

                // Foto Tentang
                $tentang_foto_lama = $konten_saat_ini['tentang_foto'];
                if (!empty($_POST['hapus_tentang_foto']) && empty($_FILES['tentang_foto']['name'])) {
                    if (!empty($tentang_foto_lama)) $file_lama_dihapus[] = $tentang_foto_lama;
                    $data_simpan['tentang_foto'] = '';
                } elseif (!empty($_FILES['tentang_foto']['name'])) {
                    $unggah = unggah_berkas($_FILES['tentang_foto'], 'gambar');
                    if (!$unggah['sukses']) {
                        $errors['tentang_foto'] = $unggah['pesan'];
                    } else {
                        $file_baru_diunggah[] = $unggah['nama'];
                        if (!empty($tentang_foto_lama)) $file_lama_dihapus[] = $tentang_foto_lama;
                        $data_simpan['tentang_foto'] = $unggah['nama'];
                    }
                }

                // Tiga Pilar
                for ($i = 1; $i <= 3; $i++) {
                    if (isset($_POST["pilar_{$i}_judul"])) $data_simpan["pilar_{$i}_judul"] = trim($_POST["pilar_{$i}_judul"]);
                    if (isset($_POST["pilar_{$i}_teks"])) $data_simpan["pilar_{$i}_teks"] = trim($_POST["pilar_{$i}_teks"]);
                }
            }

            // --- D. Footer ---
            if ($aksi === 'simpan' || $aksi === 'simpan_footer') {
                if (isset($_POST['footer_teks'])) $data_simpan['footer_teks'] = trim($_POST['footer_teks']);
            }

            if (empty($errors)) {
                try {
                    $sukses = pengaturan_simpan_banyak($data_simpan);
                    if ($sukses) {
                        $bersih = true;
                        $pengaturan_tersimpan = pengaturan_semua();
                        $media_tersimpan = array_intersect_key($pengaturan_tersimpan, array_flip(['logo_mitra', 'logo_instansi', 'hero_foto', 'tentang_foto']));
                        foreach (array_unique($file_lama_dihapus) as $nama) {
                            if (!in_array($nama, $media_tersimpan, true) && !hapus_berkas_unggahan((string)$nama)) {
                                $bersih = false;
                            }
                        }
                        pesan_flash(
                            $bersih ? 'hijau' : 'kuning',
                            'Perubahan konten halaman publik berhasil disimpan dan langsung tayang.' . ($bersih ? '' : ' Namun, ada berkas lama yang gagal dibersihkan dari server.')
                        );
                        alihkan('admin/konten.php');
                    } else {
                        foreach (array_unique($file_baru_diunggah) as $nama) {
                            hapus_berkas_unggahan((string)$nama);
                        }
                        $errors['umum'] = 'Gagal menyimpan konten ke database Firebase.';
                    }
                } catch (Throwable $e) {
                    foreach (array_unique($file_baru_diunggah) as $nama) {
                        hapus_berkas_unggahan((string)$nama);
                    }
                    $errors['umum'] = pesan_error_db('Gagal menyimpan konten', $e);
                }
            } else {
                foreach (array_unique($file_baru_diunggah) as $nama) {
                    hapus_berkas_unggahan((string)$nama);
                }
            }
        }
    }
}

// Ambil seluruh konten saat ini dari RTDB (dengan fallback otomatis ke bawaan)
$konten = konten_semua();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<form action="<?= url('admin/konten.php') ?>" method="POST" enctype="multipart/form-data" novalidate id="form-konten">
    <input type="hidden" name="csrf_token" value="<?= token_csrf() ?>">

    <div class="admin-halaman-header">
        <div>
            <h1 class="admin-halaman-judul">Konten Halaman Publik</h1>
            <p class="admin-halaman-subjudul">Kelola teks narasi, foto sampul, dan logo mitra pada halaman publik.</p>
        </div>
        <div class="admin-halaman-aksi">
            <a href="<?= url() ?>" target="_blank" rel="noopener" class="tombol tombol-sekunder" title="Pratinjau situs publik di tab baru">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                <span>Lihat Web Publik</span>
            </a>
            <button type="submit" name="aksi" value="simpan" class="tombol tombol-utama">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Simpan Semua Perubahan</span>
            </button>
        </div>
    </div>

    <!-- Banner Info Pengaturan Lain -->
    <div style="background:var(--biru-muda);border:1px solid #C2DCFA;border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;font-size:0.875rem;color:var(--biru-tua);">
        <div>
            <strong>Catatan Pengelolaan:</strong> Kontak, rekening pembayaran, donasi minimum, dan alokasi dana tetap dikelola di <a href="<?= url('admin/pengaturan.php') ?>" style="color:var(--biru);font-weight:600;text-decoration:underline;">Pengaturan Sistem</a>.
        </div>
    </div>

    <?php if (!empty($errors['umum'])): ?>
        <div class="kotak-error" role="alert" style="margin-bottom: 1.5rem;">
            <?= e($errors['umum']) ?>
        </div>
    <?php endif; ?>

    <!-- =====================================================================
         BAGIAN 1: LOGO MITRA & LOGO INSTANSI
         ===================================================================== -->
    <div class="admin-panel" style="margin-bottom: 2rem;">
        <div class="admin-panel-kepala" style="display:flex;justify-content:space-between;align-items:center;">
            <h2 class="admin-panel-judul">1. Logo Mitra & Logo Instansi</h2>
            <button 
                type="submit" 
                name="aksi" 
                value="reset_logo" 
                class="tombol-aksi tombol-aksi-sekunder form-konfirmasi" 
                data-pesan="Kembalikan logo ke berkas bawaan dan hapus logo kustom?"
                style="padding:0.4rem 0.75rem;font-size:0.8125rem;"
            >
                Kembalikan Logo ke Bawaan
            </button>
        </div>
        <div class="admin-panel-badan">
            <div class="admin-form-baris">
                <!-- Logo Mitra -->
                <div class="admin-form-grup">
                    <label for="logo_mitra">Logo Mitra (PNG, SVG, atau WebP, Maks. 2 MB)</label>
                    <input 
                        type="file" 
                        id="logo_mitra" 
                        name="logo_mitra" 
                        class="admin-input" 
                        accept="image/png,image/webp,image/svg+xml" 
                        data-pratinjau="pratinjau-logo-mitra"
                    >
                    <div class="petunjuk">Logo utama Pekarangan Institute di bilah atas dan samping.</div>
                    <?php if (!empty($errors['logo_mitra'])): ?>
                        <div class="pesan-error"><?= e($errors['logo_mitra']) ?></div>
                    <?php endif; ?>

                    <?php 
                    $ada_mitra = !empty($konten['logo_mitra']) && file_exists(__DIR__ . '/../uploads/' . $konten['logo_mitra']); 
                    ?>
                    <div class="admin-pratinjau-wadah" style="<?= $ada_mitra ? 'display:flex;' : 'display:none;' ?>">
                        <img 
                            id="pratinjau-logo-mitra" 
                            src="<?= $ada_mitra ? url('uploads/' . e($konten['logo_mitra'])) : '#' ?>" 
                            alt="Pratinjau Logo Mitra" 
                            class="admin-pratinjau-img" 
                            style="max-height: 48px; background: #fff; padding: 4px; border: 1px solid var(--garis); border-radius: 4px; <?= $ada_mitra ? '' : 'display:none;' ?>"
                        >
                        <?php if ($ada_mitra): ?>
                            <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8125rem;cursor:pointer;margin-top:0.35rem;color:var(--merah);">
                                <input type="checkbox" name="hapus_logo_mitra" value="1"> Hapus logo mitra kustom
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Logo Instansi -->
                <div class="admin-form-grup">
                    <label for="logo_instansi">Logo Instansi Pendamping (PNG, SVG, atau WebP, Maks. 2 MB)</label>
                    <input 
                        type="file" 
                        id="logo_instansi" 
                        name="logo_instansi" 
                        class="admin-input" 
                        accept="image/png,image/webp,image/svg+xml" 
                        data-pratinjau="pratinjau-logo-instansi"
                    >
                    <div class="petunjuk">Logo instansi/lembaga yang ditampilkan berdampingan dengan logo mitra.</div>
                    <?php if (!empty($errors['logo_instansi'])): ?>
                        <div class="pesan-error"><?= e($errors['logo_instansi']) ?></div>
                    <?php endif; ?>

                    <?php 
                    $ada_instansi = !empty($konten['logo_instansi']) && file_exists(__DIR__ . '/../uploads/' . $konten['logo_instansi']); 
                    ?>
                    <div class="admin-pratinjau-wadah" style="<?= $ada_instansi ? 'display:flex;' : 'display:none;' ?>">
                        <img 
                            id="pratinjau-logo-instansi" 
                            src="<?= $ada_instansi ? url('uploads/' . e($konten['logo_instansi'])) : '#' ?>" 
                            alt="Pratinjau Logo Instansi" 
                            class="admin-pratinjau-img" 
                            style="max-height: 48px; background: #fff; padding: 4px; border: 1px solid var(--garis); border-radius: 4px; <?= $ada_instansi ? '' : 'display:none;' ?>"
                        >
                        <?php if ($ada_instansi): ?>
                            <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8125rem;cursor:pointer;margin-top:0.35rem;color:var(--merah);">
                                <input type="checkbox" name="hapus_logo_instansi" value="1"> Hapus logo instansi kustom
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         BAGIAN 2: KONTEN BERANDA (HERO & TIGA LANGKAH)
         ===================================================================== -->
    <div class="admin-panel" style="margin-bottom: 2rem;">
        <div class="admin-panel-kepala" style="display:flex;justify-content:space-between;align-items:center;">
            <h2 class="admin-panel-judul">2. Konten Beranda (Hero & Tiga Langkah)</h2>
            <button 
                type="submit" 
                name="aksi" 
                value="reset_beranda" 
                class="tombol-aksi tombol-aksi-sekunder form-konfirmasi" 
                data-pesan="Kembalikan semua teks dan foto Beranda ke bawaan?"
                style="padding:0.4rem 0.75rem;font-size:0.8125rem;"
            >
                Kembalikan Beranda ke Bawaan
            </button>
        </div>
        <div class="admin-panel-badan">
            <!-- Hero Judul & Teks -->
            <div class="admin-form-grup">
                <label for="hero_judul">Judul Utama Beranda (Hero)</label>
                <input 
                    type="text" 
                    id="hero_judul" 
                    name="hero_judul" 
                    class="admin-input" 
                    value="<?= e($konten['hero_judul']) ?>"
                >
            </div>

            <div class="admin-form-grup">
                <label for="hero_teks">Teks Deskripsi Beranda (Hero)</label>
                <textarea 
                    id="hero_teks" 
                    name="hero_teks" 
                    rows="3" 
                    class="admin-textarea"
                ><?= e($konten['hero_teks']) ?></textarea>
            </div>

            <!-- Foto Hero -->
            <div class="admin-form-grup">
                <label for="hero_foto">Foto Hero Beranda (JPG, PNG, atau WebP, Maks. 2 MB)</label>
                <input 
                    type="file" 
                    id="hero_foto" 
                    name="hero_foto" 
                    class="admin-input" 
                    accept="image/jpeg,image/png,image/webp" 
                    data-pratinjau="pratinjau-hero"
                >
                <div class="petunjuk">Foto suasana kebun atau produk pekarangan untuk tampilan banner utama. Kosongkan untuk menampilkan placeholder [Foto Pekarangan].</div>
                <?php if (!empty($errors['hero_foto'])): ?>
                    <div class="pesan-error"><?= e($errors['hero_foto']) ?></div>
                <?php endif; ?>

                <?php 
                $ada_hero_foto = !empty($konten['hero_foto']) && file_exists(__DIR__ . '/../uploads/' . $konten['hero_foto']); 
                ?>
                <div class="admin-pratinjau-wadah" style="<?= $ada_hero_foto ? 'display:flex;' : 'display:none;' ?>">
                    <img 
                        id="pratinjau-hero" 
                        src="<?= $ada_hero_foto ? url('uploads/' . e($konten['hero_foto'])) : '#' ?>" 
                        alt="Pratinjau Foto Hero" 
                        class="admin-pratinjau-img" 
                        style="max-height: 120px; border-radius: var(--radius-sm); <?= $ada_hero_foto ? '' : 'display:none;' ?>"
                    >
                    <?php if ($ada_hero_foto): ?>
                        <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8125rem;cursor:pointer;margin-top:0.35rem;color:var(--merah);">
                            <input type="checkbox" name="hapus_hero_foto" value="1"> Hapus foto hero (kembali ke placeholder)
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <hr style="border:0;border-top:1px dashed var(--garis);margin:1.5rem 0;">

            <h3 style="font-size:1rem;color:var(--teks);margin-bottom:1rem;">Tiga Langkah Kerja (Bagaimana Kami Bekerja)</h3>

            <!-- Langkah 1 -->
            <div class="admin-form-baris" style="margin-bottom:1rem;">
                <div class="admin-form-grup" style="flex:1;">
                    <label for="langkah_1_judul">Judul Langkah 1</label>
                    <input type="text" id="langkah_1_judul" name="langkah_1_judul" class="admin-input" value="<?= e($konten['langkah_1_judul']) ?>">
                </div>
                <div class="admin-form-grup" style="flex:2;">
                    <label for="langkah_1_teks">Teks Deskripsi Langkah 1</label>
                    <input type="text" id="langkah_1_teks" name="langkah_1_teks" class="admin-input" value="<?= e($konten['langkah_1_teks']) ?>">
                </div>
            </div>

            <!-- Langkah 2 -->
            <div class="admin-form-baris" style="margin-bottom:1rem;">
                <div class="admin-form-grup" style="flex:1;">
                    <label for="langkah_2_judul">Judul Langkah 2</label>
                    <input type="text" id="langkah_2_judul" name="langkah_2_judul" class="admin-input" value="<?= e($konten['langkah_2_judul']) ?>">
                </div>
                <div class="admin-form-grup" style="flex:2;">
                    <label for="langkah_2_teks">Teks Deskripsi Langkah 2</label>
                    <input type="text" id="langkah_2_teks" name="langkah_2_teks" class="admin-input" value="<?= e($konten['langkah_2_teks']) ?>">
                </div>
            </div>

            <!-- Langkah 3 -->
            <div class="admin-form-baris">
                <div class="admin-form-grup" style="flex:1;">
                    <label for="langkah_3_judul">Judul Langkah 3</label>
                    <input type="text" id="langkah_3_judul" name="langkah_3_judul" class="admin-input" value="<?= e($konten['langkah_3_judul']) ?>">
                </div>
                <div class="admin-form-grup" style="flex:2;">
                    <label for="langkah_3_teks">Teks Deskripsi Langkah 3</label>
                    <input type="text" id="langkah_3_teks" name="langkah_3_teks" class="admin-input" value="<?= e($konten['langkah_3_teks']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         BAGIAN 3: KONTEN HALAMAN TENTANG (DESKRIPSI, FOTO, TIGA PILAR)
         ===================================================================== -->
    <div class="admin-panel" style="margin-bottom: 2rem;">
        <div class="admin-panel-kepala" style="display:flex;justify-content:space-between;align-items:center;">
            <h2 class="admin-panel-judul">3. Halaman Tentang Kami (Deskripsi, Foto, Tiga Pilar)</h2>
            <button 
                type="submit" 
                name="aksi" 
                value="reset_tentang" 
                class="tombol-aksi tombol-aksi-sekunder form-konfirmasi" 
                data-pesan="Kembalikan semua teks dan foto halaman Tentang ke bawaan?"
                style="padding:0.4rem 0.75rem;font-size:0.8125rem;"
            >
                Kembalikan Tentang ke Bawaan
            </button>
        </div>
        <div class="admin-panel-badan">
            <!-- Deskripsi Tentang -->
            <div class="admin-form-grup">
                <label for="tentang_teks">Narasi Profil & Inisiatif Kami</label>
                <textarea 
                    id="tentang_teks" 
                    name="tentang_teks" 
                    rows="6" 
                    class="admin-textarea"
                ><?= e($konten['tentang_teks']) ?></textarea>
                <div class="petunjuk">Gunakan paragraf baru untuk memisahkan alinea.</div>
            </div>

            <!-- Foto Tentang -->
            <div class="admin-form-grup">
                <label for="tentang_foto">Foto Halaman Tentang (JPG, PNG, atau WebP, Maks. 2 MB)</label>
                <input 
                    type="file" 
                    id="tentang_foto" 
                    name="tentang_foto" 
                    class="admin-input" 
                    accept="image/jpeg,image/png,image/webp" 
                    data-pratinjau="pratinjau-tentang"
                >
                <div class="petunjuk">Foto pendukung dokumentasi komunitas atau pekarangan (opsional).</div>
                <?php if (!empty($errors['tentang_foto'])): ?>
                    <div class="pesan-error"><?= e($errors['tentang_foto']) ?></div>
                <?php endif; ?>

                <?php 
                $ada_tentang_foto = !empty($konten['tentang_foto']) && file_exists(__DIR__ . '/../uploads/' . $konten['tentang_foto']); 
                ?>
                <div class="admin-pratinjau-wadah" style="<?= $ada_tentang_foto ? 'display:flex;' : 'display:none;' ?>">
                    <img 
                        id="pratinjau-tentang" 
                        src="<?= $ada_tentang_foto ? url('uploads/' . e($konten['tentang_foto'])) : '#' ?>" 
                        alt="Pratinjau Foto Tentang" 
                        class="admin-pratinjau-img" 
                        style="max-height: 120px; border-radius: var(--radius-sm); <?= $ada_tentang_foto ? '' : 'display:none;' ?>"
                    >
                    <?php if ($ada_tentang_foto): ?>
                        <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8125rem;cursor:pointer;margin-top:0.35rem;color:var(--merah);">
                            <input type="checkbox" name="hapus_tentang_foto" value="1"> Hapus foto halaman tentang
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <hr style="border:0;border-top:1px dashed var(--garis);margin:1.5rem 0;">

            <h3 style="font-size:1rem;color:var(--teks);margin-bottom:1rem;">Tiga Pilar Utama</h3>

            <!-- Pilar 1 -->
            <div class="admin-form-baris" style="margin-bottom:1rem;">
                <div class="admin-form-grup" style="flex:1;">
                    <label for="pilar_1_judul">Judul Pilar 1</label>
                    <input type="text" id="pilar_1_judul" name="pilar_1_judul" class="admin-input" value="<?= e($konten['pilar_1_judul']) ?>">
                </div>
                <div class="admin-form-grup" style="flex:2;">
                    <label for="pilar_1_teks">Teks Deskripsi Pilar 1</label>
                    <input type="text" id="pilar_1_teks" name="pilar_1_teks" class="admin-input" value="<?= e($konten['pilar_1_teks']) ?>">
                </div>
            </div>

            <!-- Pilar 2 -->
            <div class="admin-form-baris" style="margin-bottom:1rem;">
                <div class="admin-form-grup" style="flex:1;">
                    <label for="pilar_2_judul">Judul Pilar 2</label>
                    <input type="text" id="pilar_2_judul" name="pilar_2_judul" class="admin-input" value="<?= e($konten['pilar_2_judul']) ?>">
                </div>
                <div class="admin-form-grup" style="flex:2;">
                    <label for="pilar_2_teks">Teks Deskripsi Pilar 2</label>
                    <input type="text" id="pilar_2_teks" name="pilar_2_teks" class="admin-input" value="<?= e($konten['pilar_2_teks']) ?>">
                </div>
            </div>

            <!-- Pilar 3 -->
            <div class="admin-form-baris">
                <div class="admin-form-grup" style="flex:1;">
                    <label for="pilar_3_judul">Judul Pilar 3</label>
                    <input type="text" id="pilar_3_judul" name="pilar_3_judul" class="admin-input" value="<?= e($konten['pilar_3_judul']) ?>">
                </div>
                <div class="admin-form-grup" style="flex:2;">
                    <label for="pilar_3_teks">Teks Deskripsi Pilar 3</label>
                    <input type="text" id="pilar_3_teks" name="pilar_3_teks" class="admin-input" value="<?= e($konten['pilar_3_teks']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         BAGIAN 4: KONTEN FOOTER
         ===================================================================== -->
    <div class="admin-panel" style="margin-bottom: 2rem;">
        <div class="admin-panel-kepala" style="display:flex;justify-content:space-between;align-items:center;">
            <h2 class="admin-panel-judul">4. Konten Footer Publik</h2>
            <button 
                type="submit" 
                name="aksi" 
                value="reset_footer" 
                class="tombol-aksi tombol-aksi-sekunder form-konfirmasi" 
                data-pesan="Kembalikan teks footer ke bawaan?"
                style="padding:0.4rem 0.75rem;font-size:0.8125rem;"
            >
                Kembalikan Footer ke Bawaan
            </button>
        </div>
        <div class="admin-panel-badan">
            <div class="admin-form-grup">
                <label for="footer_teks">Teks Singkat Footer (Di Bawah Tagline)</label>
                <textarea 
                    id="footer_teks" 
                    name="footer_teks" 
                    rows="2" 
                    class="admin-textarea"
                ><?= e($konten['footer_teks']) ?></textarea>
                <div class="petunjuk">Penjelasan singkat misi dan gerakan pekarangan di bagian bawah semua halaman publik.</div>
            </div>
        </div>
    </div>

    <!-- Bilah Aksi Simpan Bawah -->
    <div class="admin-aksi-simpan-wadah">
        <div class="admin-aksi-simpan-info">
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <span class="lencana lencana-hijau">Langsung Aktif</span>
                <strong style="color:var(--teks);font-size:0.9375rem;">Simpan Perubahan Konten</strong>
            </div>
            <p style="color:var(--teks-redup);font-size:0.8125rem;margin-top:0.25rem;">
                Perubahan judul, narasi, logo, dan foto akan langsung diterapkan pada seluruh halaman publik.
            </p>
        </div>
        <div class="admin-aksi-simpan-tombol">
            <a href="<?= url() ?>" target="_blank" rel="noopener" class="tombol tombol-sekunder">
                Lihat Web Publik &rarr;
            </a>
            <button type="submit" name="aksi" value="simpan" class="tombol tombol-utama" style="padding:0.75rem 1.75rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Simpan Semua Konten</span>
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
