<?php
/**
 * Kumpulan Fungsi Pembantu (Helper) Pekarangan Institute
 * Fungsi sederhana, singkat, dan terhindar dari ketergantungan berlebih.
 */

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/auth.php';

/**
 * Mencegah XSS dengan mengubah karakter khusus ke entitas HTML
 */
function e(?string $nilai): string
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka ke mata uang Rupiah
 */
function rupiah(int|float|null $angka): string
{
    return 'Rp ' . number_format((float)($angka ?? 0), 0, ',', '.');
}

/**
 * Menghasilkan URL lengkap berbasis BASE_URL
 */
function url(string $jalur = ''): string
{
    $basis = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if (preg_match('/\A(?:localhost|127\.0\.0\.1|\[::1\])(?::\d+)?\z/', $host)) {
        $skema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $akar_skrip = dirname(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/')));
        if (basename($akar_skrip) === 'admin') {
            $akar_skrip = dirname($akar_skrip);
        }
        $akar_skrip = str_replace('\\', '/', $akar_skrip);
        $akar_skrip = rtrim($akar_skrip, '/.');
        $basis = $skema . '://' . $host . ($akar_skrip !== '' ? $akar_skrip : '');
    }
    $bersih = ltrim($jalur, '/');
    return $bersih === '' ? ($basis ?: '/') : $basis . '/' . $bersih;
}

/**
 * Mengalihkan halaman ke URL internal yang aman
 */
function alihkan(string $ke): void
{
    // Cegah redirect keluar domain (open redirect)
    // Abaikan jika menuju skema eksternal (http, https, //, javascript)
    if (preg_match('#^(?:[a-z]+:)?//#i', $ke) || str_starts_with($ke, 'javascript:')) {
        $ke = 'index.php';
    }

    $basis = rtrim(url(), '/');
    if ($basis !== '' && str_starts_with($ke, $basis . '/')) {
        header('Location: ' . $ke);
    } else {
        header('Location: ' . url($ke));
    }
    exit;
}

/**
 * Format pesan error database aman yang menyembunyikan detail teknis di mode produksi
 */
function pesan_error_db(string $pesan_aman, Throwable $e): string
{
    if (defined('APP_ENV') && APP_ENV === 'lokal') {
        return $pesan_aman . ': ' . $e->getMessage();
    }
    return $pesan_aman . '. Silakan coba lagi atau hubungi pengelola sistem.';
}

/**
 * Menyimpan atau mengambil pesan kilas (flash message) dari sesi
 */
function pesan_flash(?string $tipe = null, ?string $pesan = null): ?array
{
    mulai_sesi_aman();
    if ($tipe !== null && $pesan !== null) {
        $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
        return null;
    }
    if (isset($_SESSION['flash'])) {
        $data = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $data;
    }
    return null;
}

/**
 * Membuat atau mengambil token CSRF dari sesi
 */
function token_csrf(): string
{
    mulai_sesi_aman();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memvalidasi token CSRF dari kiriman form
 */
function cek_csrf(?string $token = null): bool
{
    mulai_sesi_aman();
    $token_sesi = $_SESSION['csrf_token'] ?? '';
    $token_kiriman = $token ?? $_POST['csrf_token'] ?? '';
    return !empty($token_sesi) && hash_equals($token_sesi, (string)$token_kiriman);
}

/**
 * Membuat slug teks yang aman untuk URL
 */
function buat_slug(string $teks): string
{
    $slug = preg_replace('~[^\pL\d]+~u', '-', $teks);
    $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug) ?: $slug;
    $slug = preg_replace('~[^-\w]+~', '', $slug);
    $slug = trim($slug, '-');
    $slug = preg_replace('~-+~', '-', $slug);
    $slug = strtolower($slug);
    return empty($slug) ? 'item-' . time() : $slug;
}

/**
 * Membuat kode unik untuk pesanan (ORD) atau donasi (DON)
 */
function buat_kode(string $awalan): string
{
    $tanggal = date('Ymd');
    $karakter = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $acak = '';
    $panjang = strlen($karakter);
    for ($i = 0; $i < 4; $i++) {
        $acak .= $karakter[random_int(0, $panjang - 1)];
    }
    return sprintf('%s-%s-%s', strtoupper($awalan), $tanggal, $acak);
}

/**
 * Mengambil nilai pengaturan berdasarkan kunci dengan cache memori lokal
 */
function ambil_pengaturan(string $kunci, ?string $bawaan = null): ?string
{
    return pengaturan_ambil($kunci, $bawaan);
}

/**
 * Mengunggah berkas dengan validasi ukuran, MIME, dan ekstensi
 */
function unggah_berkas(array $berkas, string $kategori = 'gambar'): array
{
    $kode_error = (int)($berkas['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($kode_error !== UPLOAD_ERR_OK) {
        $pesan_error = match ($kode_error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas melampaui batas unggah server. Coba berkas yang lebih kecil.',
            UPLOAD_ERR_PARTIAL => 'Berkas hanya terunggah sebagian. Silakan coba lagi.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada berkas yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara unggahan tidak tersedia di server.',
            UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis berkas unggahan ke disk.',
            UPLOAD_ERR_EXTENSION => 'Unggahan dihentikan oleh konfigurasi ekstensi PHP.',
            default => 'Terjadi kesalahan saat mengunggah berkas.',
        };
        return ['sukses' => false, 'nama' => null, 'pesan' => $pesan_error];
    }

    if ($kategori === 'logo') {
        $aturan = [
            'max'          => BATAS_UNGGAH_GAMBAR,
            'label_max'    => '2 MB',
            'mime'         => ['image/png', 'image/webp', 'image/svg+xml', 'image/svg', 'text/xml', 'text/plain'],
            'ext'          => ['png', 'webp', 'svg'],
            'label_format' => 'PNG, SVG, atau WebP'
        ];
    } elseif ($kategori === 'gambar') {
        $aturan = [
            'max'          => BATAS_UNGGAH_GAMBAR,
            'label_max'    => '2 MB',
            'mime'         => ['image/jpeg', 'image/png', 'image/webp'],
            'ext'          => ['jpg', 'jpeg', 'png', 'webp'],
            'label_format' => 'JPG, PNG, atau WebP'
        ];
    } else {
        $aturan = [
            'max'          => BATAS_UNGGAH_BERKAS,
            'label_max'    => '5 MB',
            'mime'         => ['application/pdf', 'image/jpeg', 'image/png'],
            'ext'          => ['pdf', 'jpg', 'jpeg', 'png'],
            'label_format' => 'PDF, JPG, atau PNG'
        ];
    }

    if (empty($berkas['tmp_name']) || !is_uploaded_file($berkas['tmp_name'])) {
        return ['sukses' => false, 'nama' => null, 'pesan' => 'Berkas unggahan tidak dapat diverifikasi. Silakan pilih ulang berkas.'];
    }

    if (!isset($berkas['size']) || (int)$berkas['size'] <= 0) {
        return ['sukses' => false, 'nama' => null, 'pesan' => 'Berkas kosong atau ukuran berkas tidak dapat dibaca.'];
    }

    if ((int)$berkas['size'] > $aturan['max']) {
        return ['sukses' => false, 'nama' => null, 'pesan' => 'Ukuran berkas melebihi batas maksimal (' . $aturan['label_max'] . ').'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return ['sukses' => false, 'nama' => null, 'pesan' => 'Server tidak dapat memeriksa tipe berkas. Silakan hubungi pengelola sistem.'];
    }
    $tipe_mime = finfo_file($finfo, $berkas['tmp_name']);

    if ($tipe_mime === false) {
        return ['sukses' => false, 'nama' => null, 'pesan' => 'Tipe berkas tidak dapat dibaca. Silakan pilih berkas lain.'];
    }

    $ekstensi = strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION));
    if (!in_array($tipe_mime, $aturan['mime'], true) || !in_array($ekstensi, $aturan['ext'], true)) {
        return ['sukses' => false, 'nama' => null, 'pesan' => 'Format berkas tidak diizinkan. Hanya menerima berkas bertipe ' . $aturan['label_format'] . '.'];
    }

    // Untuk kategori gambar atau logo non-SVG, pastikan berkas benar-benar gambar yang valid
    if ($kategori === 'gambar' || ($kategori === 'logo' && in_array($ekstensi, ['png', 'webp'], true))) {
        $info_gambar = @getimagesize($berkas['tmp_name']);
        if ($info_gambar === false || empty($info_gambar[0]) || empty($info_gambar[1])) {
            return ['sukses' => false, 'nama' => null, 'pesan' => 'Berkas bukan gambar yang valid.'];
        }
    } elseif ($kategori === 'logo' && $ekstensi === 'svg') {
        $konten_svg = file_get_contents($berkas['tmp_name']);
        if ($konten_svg === false || stripos($konten_svg, '<svg') === false) {
            return ['sukses' => false, 'nama' => null, 'pesan' => 'Berkas SVG tidak valid atau tidak memiliki tag <svg>.'];
        }
    }

    $nama_baru = bin2hex(random_bytes(16)) . '.' . $ekstensi;
    $tujuan = __DIR__ . '/../uploads/' . $nama_baru;

    if (!move_uploaded_file($berkas['tmp_name'], $tujuan)) {
        error_log('Gagal menyimpan berkas unggahan ke folder uploads.');
        return ['sukses' => false, 'nama' => null, 'pesan' => 'Gagal menyimpan berkas ke server.'];
    }

    return ['sukses' => true, 'nama' => $nama_baru, 'pesan' => 'Berkas berhasil diunggah.'];
}

/**
 * Menghapus berkas unggahan berdasarkan nama file saja.
 */
function hapus_berkas_unggahan(string $nama): bool
{
    if ($nama === '' || basename($nama) !== $nama || !preg_match('/\A[a-zA-Z0-9._-]+\z/', $nama)) {
        return false;
    }

    $jalur = __DIR__ . '/../uploads/' . $nama;
    if (!file_exists($jalur)) {
        return true;
    }
    if (!is_file($jalur) || !unlink($jalur)) {
        error_log('Gagal menghapus berkas unggahan: ' . $nama);
        return false;
    }

    return true;
}

/**
 * Mendeteksi permintaan POST yang ditolak PHP karena melampaui post_max_size.
 */
function permintaan_post_melebihi_batas(): bool
{
    $panjang = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    $batas_ini = trim((string)ini_get('post_max_size'));
    if ($panjang <= 0 || $batas_ini === '' || $batas_ini === '0') {
        return false;
    }

    if (!preg_match('/\A(\d+(?:\.\d+)?)\s*([KMG])?B?\z/i', $batas_ini, $cocok)) {
        return false;
    }

    $pengali = match (strtoupper($cocok[2] ?? '')) {
        'K' => 1024,
        'M' => 1024 ** 2,
        'G' => 1024 ** 3,
        default => 1,
    };

    return $panjang > (float)$cocok[1] * $pengali;
}

/**
 * Menghitung data paginasi sederhana
 */
function paginasi(int $total_item, int $per_halaman, int $halaman_saat_ini, string $url_format): array
{
    $total_halaman = max(1, (int)ceil($total_item / $per_halaman));
    $halaman = max(1, min($halaman_saat_ini, $total_halaman));
    $offset = ($halaman - 1) * $per_halaman;

    return [
        'total_item'    => $total_item,
        'total_halaman' => $total_halaman,
        'halaman'       => $halaman,
        'offset'        => $offset,
        'ada_sebelum'   => $halaman > 1,
        'ada_berikut'   => $halaman < $total_halaman,
        'url_sebelum'   => sprintf($url_format, $halaman - 1),
        'url_berikut'   => sprintf($url_format, $halaman + 1),
    ];
}

/**
 * Memvalidasi format nomor WhatsApp Indonesia
 * Mendukung format: 08xxx, 628xxx, +628xxx (panjang digit 10-15 angka)
 */
function validasi_nomor_wa(?string $nomor): bool
{
    if (empty($nomor)) {
        return false;
    }
    // Bersihkan karakter pemisah seperti spasi, titik, tanda hubung
    $bersih = preg_replace('/[\s\.\-]+/', '', trim($nomor));
    return (bool)preg_match('/^(?:\+62|62|0)8[1-9][0-9]{7,11}$/', $bersih);
}

/**
 * Menghasilkan tautan wa.me dengan teks pesan terenkode rapi
 */
function tautan_wa(?string $nomor, string $pesan = ''): string
{
    $nomor_bersih = preg_replace('/[^0-9]/', '', (string)$nomor);
    if (str_starts_with($nomor_bersih, '0')) {
        $nomor_bersih = '62' . substr($nomor_bersih, 1);
    }

    // Jika nomor tidak valid atau berupa placeholder contoh yang tidak ada angkanya
    $target = (strlen($nomor_bersih) >= 10) ? $nomor_bersih : '';
    $kueri = !empty($pesan) ? '?text=' . rawurlencode($pesan) : '';

    return 'https://wa.me/' . $target . $kueri;
}

/**
 * Format tanggal ke bahasa Indonesia sederhana (contoh: 25 Oktober 2026)
 */
function tanggal_id(?string $tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }
    $waktu = strtotime($tanggal);
    if (!$waktu) {
        return $tanggal;
    }
    $bulan_id = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = (int)date('j', $waktu);
    $bln = $bulan_id[(int)date('n', $waktu)] ?? date('m', $waktu);
    $thn = date('Y', $waktu);
    return $tgl . ' ' . $bln . ' ' . $thn;
}

/**
 * Menghasilkan URL logo mitra jika berkas ada, atau string kosong jika belum diunggah
 */
function url_logo_mitra(): string
{
    $kustom = ambil_pengaturan('logo_mitra', '');
    if (!empty($kustom) && file_exists(__DIR__ . '/../uploads/' . $kustom)) {
        return url('uploads/' . $kustom);
    }
    if (file_exists(__DIR__ . '/../assets/img/logo-pekarangan.jpeg')) {
        return url('assets/img/logo-pekarangan.jpeg');
    }
    return '';
}

/**
 * Menghasilkan URL logo instansi jika berkas ada, atau string kosong jika belum diunggah
 */
function url_logo_instansi(): string
{
    $kustom = ambil_pengaturan('logo_instansi', '');
    if (!empty($kustom) && file_exists(__DIR__ . '/../uploads/' . $kustom)) {
        return url('uploads/' . $kustom);
    }
    if (file_exists(__DIR__ . '/../assets/img/logo-instansi.png')) {
        return url('assets/img/logo-instansi.png');
    }
    return '';
}
