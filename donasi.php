<?php
/**
 * Formulir Donasi Program Sosial - Pekarangan Institute
 * Mendukung pemilihan program aktif, pilihan nominal cepat (preset) dan isian bebas,
 * opsi anonimitas, validasi nomor WhatsApp, CSRF, dan honeypot anti-spam.
 */

require_once __DIR__ . '/includes/fungsi.php';

$judul_halaman = 'Donasi Program Sosial';
$menu_aktif = 'donasi';
$deskripsi_halaman = 'Salurkan kepedulian Anda untuk mendukung program ketahanan pangan dan pemberdayaan komunitas pekarangan.';

// Ambil batas nominal donasi minimum dari pengaturan
$donasi_minimum_raw = ambil_pengaturan('donasi_minimum', '10000');
$donasi_minimum = max(1000, (int)$donasi_minimum_raw);

// Ambil daftar program yang berstatus 'aktif' dari RTDB
$daftar_program_aktif = program_semua(true);

// Cek apakah ada parameter program dari query string (?program=slug)
$slug_pilihan = trim($_GET['program'] ?? '');
$program_id_terpilih = '';
if ($slug_pilihan !== '') {
    foreach ($daftar_program_aktif as $p) {
        if (($p['slug'] ?? '') === $slug_pilihan) {
            $program_id_terpilih = (string)$p['id'];
            break;
        }
    }
}

$errors = [];
$nominal = '';
$nama_donatur = '';
$whatsapp = '';
$anonim = 0;
$pesan = '';

// Proses pengiriman donasi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Cek Honeypot
    if (!empty($_POST['website'])) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    // 2. Cek CSRF
    if (!cek_csrf()) {
        $errors[] = 'Sesi formulir telah kedaluwarsa atau tidak valid. Silakan muat ulang halaman.';
    }

    // 3. Ambil data formulir
    $program_id_input = trim((string)($_POST['program_id'] ?? ''));
    $nominal_input    = trim($_POST['nominal'] ?? '');
    $nama_donatur     = trim($_POST['nama_donatur'] ?? '');
    $whatsapp         = trim($_POST['whatsapp'] ?? '');
    $anonim           = !empty($_POST['anonim']) ? 1 : 0;
    $pesan            = trim($_POST['pesan'] ?? '');

    $program_id_terpilih = $program_id_input;

    // 4. Validasi server
    // Validasi program
    $program_valid = null;
    foreach ($daftar_program_aktif as $p) {
        if ((string)$p['id'] === $program_id_input) {
            $program_valid = $p;
            break;
        }
    }

    if (!$program_valid) {
        $errors['program_id'] = 'Silakan pilih program sosial aktif yang valid.';
    }

    // Validasi nominal donasi
    // Bersihkan format titik atau koma jika pengguna mengetik 50.000
    $nominal_bersih = preg_replace('/[^0-9]/', '', $nominal_input);
    if ($nominal_bersih === '' || !filter_var($nominal_bersih, FILTER_VALIDATE_INT)) {
        $errors['nominal'] = 'Silakan masukkan nominal donasi yang valid.';
        $nominal = $nominal_input;
    } else {
        $nominal_angka = (int)$nominal_bersih;
        if ($nominal_angka < $donasi_minimum) {
            $errors['nominal'] = 'Nominal donasi minimal ' . rupiah($donasi_minimum) . '.';
        }
        $nominal = $nominal_angka;
    }

    // Validasi nama donatur
    if ($nama_donatur === '') {
        $errors['nama_donatur'] = 'Nama lengkap donatur wajib diisi.';
    } elseif (mb_strlen($nama_donatur) > 150) {
        $errors['nama_donatur'] = 'Nama donatur maksimal 150 karakter.';
    }

    // Validasi WhatsApp
    if ($whatsapp === '') {
        $errors['whatsapp'] = 'Nomor WhatsApp wajib diisi.';
    } elseif (!validasi_nomor_wa($whatsapp)) {
        $errors['whatsapp'] = 'Format nomor WhatsApp tidak valid. Gunakan format Indonesia (contoh: 081234567890).';
    }

    // Validasi pesan
    if ($pesan !== '' && mb_strlen($pesan) > 500) {
        $errors['pesan'] = 'Pesan atau doa maksimal 500 karakter.';
    }

    // 5. Simpan donasi jika valid
    if (empty($errors) && $program_valid) {
        $kode = kode_donasi_unik();

        try {
            $id_donasi = donasi_buat([
                'kode'         => $kode,
                'program_id'   => (string)$program_valid['id'],
                'nama_donatur' => $nama_donatur,
                'whatsapp'     => $whatsapp,
                'jumlah'       => (int)$nominal,
                'metode_pembayaran' => 'QRIS',
                'anonim'       => $anonim,
                'pesan'        => $pesan !== '' ? $pesan : null,
                'status'       => 'menunggu',
                'is_contoh'    => (int)($program_valid['is_contoh'] ?? 0),
            ]);

            if ($id_donasi !== null) {
                alihkan('donasi-sukses.php?kode=' . urlencode($kode));
                exit;
            } else {
                $errors[] = 'Terjadi kesalahan teknis saat mencatat donasi Anda. Silakan coba kembali.';
            }
        } catch (Throwable $e) {
            $errors[] = pesan_error_db('Terjadi kesalahan teknis saat mencatat donasi Anda', $e);
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <div class="bagian-kepala lebar-maks-600">
        <h1>Berdonasi untuk Program</h1>
        <p>
            Dukungan Anda mengalir langsung ke program pemberdayaan pekarangan produktif komunitas. Seluruh donasi diverifikasi manual dan dicatat transparan.
        </p>
    </div>

    <div class="kartu kartu-form-wadah">
        <?php if (!empty($errors)): ?>
            <div class="kotak-error-form" role="alert">
                <strong>Mohon periksa kembali formulir donasi Anda:</strong>
                <ul>
                    <?php foreach ($errors as $pesan_err): ?>
                        <li><?= e($pesan_err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($daftar_program_aktif)): ?>
            <div class="kotak-kosong">
                <h3>Saat ini belum ada program aktif yang menerima donasi.</h3>
                <p>Silakan pantau kembali halaman ini atau hubungi kami melalui halaman kontak.</p>
                <div class="jarak-atas-md">
                    <a href="<?= url('program.php') ?>" class="tombol tombol-sekunder">Lihat Arsip Program</a>
                </div>
            </div>
        <?php else: ?>
            <form action="<?= url('donasi.php') ?><?= $slug_pilihan !== '' ? '?program=' . urlencode($slug_pilihan) : '' ?>" method="POST" novalidate>
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= e(token_csrf()) ?>">

                <!-- Kolom Jebakan (Honeypot) -->
                <div class="kolom-jebakan" aria-hidden="true">
                    <label for="website">Abaikan kolom ini</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <!-- 1. Pilih Program Aktif -->
                <div class="form-grup">
                    <label for="program_id" class="form-label">
                        Pilih Program Sosial <span class="tanda-wajib">*</span>
                    </label>
                    <select id="program_id" name="program_id" class="form-kontrol <?= isset($errors['program_id']) ? 'form-kontrol-error' : '' ?>" required>
                        <option value="">-- Pilih Program --</option>
                        <?php foreach ($daftar_program_aktif as $prog_item): ?>
                            <option value="<?= e((string)$prog_item['id']) ?>" <?= $program_id_terpilih === (string)$prog_item['id'] ? 'selected' : '' ?>>
                                <?= e($prog_item['judul']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['program_id'])): ?>
                        <span class="form-pesan-error"><?= e($errors['program_id']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- 2. Nominal Donasi -->
                <div class="form-grup">
                    <label class="form-label">
                        Nominal Donasi (Rp) <span class="tanda-wajib">*</span>
                    </label>

                    <!-- Tombol Cepat Nominal -->
                    <div class="nominal-pilihan" aria-label="Pilihan Cepat Nominal">
                        <button type="button" class="tombol-nominal <?= $nominal == 25000 ? 'aktif' : '' ?>" data-nominal="25000">25.000</button>
                        <button type="button" class="tombol-nominal <?= $nominal == 50000 ? 'aktif' : '' ?>" data-nominal="50000">50.000</button>
                        <button type="button" class="tombol-nominal <?= $nominal == 100000 ? 'aktif' : '' ?>" data-nominal="100000">100.000</button>
                        <button type="button" class="tombol-nominal <?= $nominal == 250000 ? 'aktif' : '' ?>" data-nominal="250000">250.000</button>
                    </div>

                    <!-- Isian Bebas Nominal -->
                    <label for="nominal" class="form-label teks-xs teks-sedang">Atau masukkan nominal lainnya:</label>
                    <input type="number" id="nominal" name="nominal" 
                           value="<?= e((string)$nominal) ?>" 
                           min="<?= $donasi_minimum ?>" step="1000"
                           class="form-kontrol <?= isset($errors['nominal']) ? 'form-kontrol-error' : '' ?>" 
                           placeholder="Contoh: <?= $donasi_minimum ?>" required>
                    <span class="form-petunjuk">Minimum donasi: <?= rupiah($donasi_minimum) ?>.</span>
                    <?php if (isset($errors['nominal'])): ?>
                        <span class="form-pesan-error"><?= e($errors['nominal']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- 3. Nama Lengkap Donatur -->
                <div class="form-grup">
                    <label for="nama_donatur" class="form-label">
                        Nama Lengkap Donatur <span class="tanda-wajib">*</span>
                    </label>
                    <input type="text" id="nama_donatur" name="nama_donatur" 
                           value="<?= e($nama_donatur) ?>" 
                           class="form-kontrol <?= isset($errors['nama_donatur']) ? 'form-kontrol-error' : '' ?>" 
                           placeholder="Nama Anda" maxlength="150" required>
                    <?php if (isset($errors['nama_donatur'])): ?>
                        <span class="form-pesan-error"><?= e($errors['nama_donatur']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- 4. Nomor WhatsApp -->
                <div class="form-grup">
                    <label for="whatsapp" class="form-label">
                        Nomor WhatsApp <span class="tanda-wajib">*</span>
                    </label>
                    <input type="tel" id="whatsapp" name="whatsapp" 
                           value="<?= e($whatsapp) ?>" 
                           class="form-kontrol <?= isset($errors['whatsapp']) ? 'form-kontrol-error' : '' ?>" 
                           placeholder="Contoh: 081234567890" maxlength="30" required>
                    <span class="form-petunjuk">Digunakan untuk konfirmasi dan pencatatan verifikasi donasi.</span>
                    <?php if (isset($errors['whatsapp'])): ?>
                        <span class="form-pesan-error"><?= e($errors['whatsapp']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- 5. Opsi Anonimitas -->
                <div class="form-grup">
                    <label class="form-centang">
                        <input type="checkbox" id="anonim" name="anonim" value="1" <?= $anonim ? 'checked' : '' ?>>
                        <span>Sembunyikan nama saya di halaman publik (Anonim)</span>
                    </label>
                </div>

                <!-- 6. Pesan / Doa Donatur (Opsional) -->
                <div class="form-grup">
                    <label for="pesan" class="form-label">Pesan / Doa Dukungan (Opsional)</label>
                    <textarea id="pesan" name="pesan" 
                              class="form-kontrol <?= isset($errors['pesan']) ? 'form-kontrol-error' : '' ?>" 
                              placeholder="Tuliskan doa atau pesan penyemangat untuk warga binaan..." 
                              rows="3" maxlength="500"><?= e($pesan) ?></textarea>
                    <?php if (isset($errors['pesan'])): ?>
                        <span class="form-pesan-error"><?= e($errors['pesan']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Tombol Submit -->
                <div class="jarak-atas-lg">
                    <button type="submit" class="tombol tombol-utama tombol-blok">
                        Lanjut Donasi &rarr;
                    </button>
                    <span class="blok teks-tengah teks-xs teks-redup jarak-atas-sm">
                        Setelah ini, Anda akan diarahkan ke QRIS dan rincian pembayaran.
                    </span>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
