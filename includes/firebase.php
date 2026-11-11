<?php
/**
 * Pustaka REST API Firebase Realtime Database
 * Pekarangan Institute
 *
 * Menggunakan cURL dan OpenSSL bawaan PHP (tanpa Composer / SDK).
 * Autentikasi menggunakan Google Service Account (OAuth2 Access Token via JWT RS256).
 */

// Muat konfigurasi jika belum tersedia
if (!defined('FIREBASE_DB_URL')) {
    $berkas_config = __DIR__ . '/config.php';
    if (file_exists($berkas_config)) {
        require_once $berkas_config;
    }
}

/**
 * Melakukan Base64 URL Safe Encode (RFC 7515)
 */
function fb_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Membaca berkas kunci JSON Service Account
 *
 * @return array|null Mengembalikan data kredensial atau null jika gagal
 */
function fb_baca_kunci(): ?array
{
    if (!defined('FIREBASE_KUNCI_PATH') || empty(FIREBASE_KUNCI_PATH)) {
        error_log('Firebase: FIREBASE_KUNCI_PATH belum ditentukan di config.php');
        return null;
    }

    $path = FIREBASE_KUNCI_PATH;
    if (!file_exists($path) && file_exists($path . '.json')) {
        $path = $path . '.json';
    }
    if (!file_exists($path) || !is_readable($path)) {
        error_log('Firebase: Berkas kunci service account tidak ditemukan atau tidak dapat dibaca di: ' . $path);
        return null;
    }

    $konten = file_get_contents($path);
    if ($konten === false) {
        error_log('Firebase: Gagal membaca isi berkas kunci: ' . $path);
        return null;
    }

    $kunci = json_decode($konten, true);
    if (!is_array($kunci) || empty($kunci['client_email']) || empty($kunci['private_key'])) {
        error_log('Firebase: Format berkas JSON kunci service account tidak valid');
        return null;
    }

    return $kunci;
}

/**
 * Mendapatkan Access Token OAuth2 untuk Firebase REST API
 * Menggunakan cache berkas di folder temporer sistem sampai mendekati waktu kedaluwarsa.
 *
 * @return string|null Mengembalikan token Bearer atau null jika otentikasi gagal
 */
function fb_token(): ?string
{
    static $token_memori = null;
    static $token_exp = 0;

    $waktu_sekarang = time();

    // 1. Periksa cache memori runtime
    if ($token_memori !== null && $waktu_sekarang < ($token_exp - 120)) {
        return $token_memori;
    }

    // 2. Periksa cache di berkas sistem (tidak dapat diakses browser)
    $berkas_cache = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pekarangan_fb_tok_' . md5(defined('FIREBASE_KUNCI_PATH') ? FIREBASE_KUNCI_PATH : 'kunci') . '.json';
    if (file_exists($berkas_cache)) {
        $data_cache = json_decode(@file_get_contents($berkas_cache), true);
        if (is_array($data_cache) && !empty($data_cache['token']) && !empty($data_cache['exp'])) {
            if ($waktu_sekarang < ($data_cache['exp'] - 120)) {
                $token_memori = $data_cache['token'];
                $token_exp = (int)$data_cache['exp'];
                return $token_memori;
            }
        }
    }

    // 3. Buat JWT baru dan minta token ke Google OAuth2
    $kunci = fb_baca_kunci();
    if (!$kunci) {
        return null;
    }

    if (!extension_loaded('openssl')) {
        error_log('Firebase: Ekstensi OpenSSL tidak aktif di PHP server');
        return null;
    }

    $header = [
        'alg' => 'RS256',
        'typ' => 'JWT',
    ];

    $payload = [
        'iss'   => $kunci['client_email'],
        'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/firebase.database',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $waktu_sekarang + 3600,
        'iat'   => $waktu_sekarang,
    ];

    $input_tanda_tangan = fb_base64url_encode(json_encode($header)) . '.' . fb_base64url_encode(json_encode($payload));
    $tanda_tangan = '';

    $res_kunci = openssl_pkey_get_private($kunci['private_key']);
    if (!$res_kunci) {
        error_log('Firebase: Format private key tidak dapat diproses oleh OpenSSL');
        return null;
    }

    $sukses_sign = openssl_sign($input_tanda_tangan, $tanda_tangan, $res_kunci, OPENSSL_ALGO_SHA256);
    if (!$sukses_sign) {
        error_log('Firebase: Gagal menandatangani klaim JWT dengan OpenSSL');
        return null;
    }

    $jwt = $input_tanda_tangan . '.' . fb_base64url_encode($tanda_tangan);

    // Minta Access Token via cURL
    if (!extension_loaded('curl')) {
        error_log('Firebase: Ekstensi cURL tidak aktif di PHP server');
        return null;
    }

    $ch = curl_init('https://oauth2.googleapis.com/token');
    $post_data = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ]);

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post_data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $balasan = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($balasan === false || $http_code !== 200) {
        error_log('Firebase: Gagal meminta access token OAuth2 (HTTP ' . $http_code . '): ' . ($curl_err ?: $balasan));
        return null;
    }

    $json_token = json_decode($balasan, true);
    if (!is_array($json_token) || empty($json_token['access_token'])) {
        error_log('Firebase: Respon access token tidak valid: ' . $balasan);
        return null;
    }

    $token = $json_token['access_token'];
    $kadaluwarsa = $waktu_sekarang + (int)($json_token['expires_in'] ?? 3600);

    // Simpan ke memori dan cache berkas
    $token_memori = $token;
    $token_exp = $kadaluwarsa;

    @file_put_contents($berkas_cache, json_encode([
        'token' => $token,
        'exp'   => $kadaluwarsa,
    ]), LOCK_EX);

    return $token;
}

/**
 * Menyusun URL lengkap Firebase RTDB REST endpoint
 */
function fb_url(string $jalur): string
{
    $basis = defined('FIREBASE_DB_URL') ? rtrim(FIREBASE_DB_URL, '/') : '';
    $bersih = trim($jalur, '/');
    return $basis . '/' . ($bersih !== '' ? $bersih . '.json' : '.json');
}

/**
 * Eksekusi panggilan HTTP REST ke Firebase menggunakan cURL
 *
 * @param string $metode GET, POST, PUT, PATCH, DELETE
 * @return array ['status' => int, 'body' => mixed, 'headers' => array, 'raw_body' => string]
 */
function fb_eksekusi_http(string $jalur, string $metode, mixed $data = null, array $header_ekstra = []): array
{
    $token = fb_token();
    if ($token === null) {
        error_log("Firebase [$metode $jalur]: Akses token tidak tersedia");
        return ['status' => 0, 'body' => null, 'headers' => [], 'raw_body' => ''];
    }

    $url = fb_url($jalur);
    $ch = curl_init($url);

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json; charset=utf-8',
        'Accept: application/json',
    ];

    foreach ($header_ekstra as $k => $v) {
        $headers[] = is_int($k) ? $v : "$k: $v";
    }

    $respon_headers = [];

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => strtoupper($metode),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HEADERFUNCTION => function ($ch, $header_line) use (&$respon_headers) {
            $len = strlen($header_line);
            $parts = explode(':', $header_line, 2);
            if (count($parts) === 2) {
                $nama = strtolower(trim($parts[0]));
                $nilai = trim($parts[1]);
                $respon_headers[$nama] = $nilai;
            }
            return $len;
        },
    ]);

    if ($data !== null && in_array(strtoupper($metode), ['POST', 'PUT', 'PATCH'], true)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $raw_body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($raw_body === false) {
        error_log("Firebase cURL Error [$metode $jalur]: $curl_err");
        return ['status' => 0, 'body' => null, 'headers' => $respon_headers, 'raw_body' => ''];
    }

    $body = json_decode($raw_body, true);

    return [
        'status'   => $status,
        'body'     => $body,
        'headers'  => $respon_headers,
        'raw_body' => $raw_body,
    ];
}

/**
 * Mengambil data dari suatu jalur RTDB (GET)
 *
 * @return mixed Mengembalikan data terdekode atau null jika kosong/gagal
 */
function fb_ambil(string $jalur): mixed
{
    $res = fb_eksekusi_http($jalur, 'GET');
    if ($res['status'] !== 200) {
        error_log("fb_ambil gagal pada jalur [$jalur] status {$res['status']}");
        return null;
    }
    return $res['body'];
}

/**
 * Menyimpan data pada suatu jalur RTDB dengan menimpa seluruh node (PUT)
 */
function fb_simpan(string $jalur, mixed $data): mixed
{
    $res = fb_eksekusi_http($jalur, 'PUT', $data);
    if ($res['status'] !== 200) {
        error_log("fb_simpan gagal pada jalur [$jalur] status {$res['status']}");
        return false;
    }
    return $res['body'];
}

/**
 * Memperbarui properti tertentu pada suatu jalur RTDB tanpa menghapus properti lain (PATCH)
 */
function fb_ubah(string $jalur, array $data): mixed
{
    $res = fb_eksekusi_http($jalur, 'PATCH', $data);
    if ($res['status'] !== 200) {
        error_log("fb_ubah gagal pada jalur [$jalur] status {$res['status']}");
        return false;
    }
    return $res['body'];
}

/**
 * Menambahkan data baru ke dalam koleksi (POST)
 * Firebase menghasilkan push ID acak unik (mis. "-Nxxx...").
 *
 * @return string|null Mengembalikan ID string hasil generate atau null jika gagal
 */
function fb_tambah(string $jalur, array $data): ?string
{
    $res = fb_eksekusi_http($jalur, 'POST', $data);
    if ($res['status'] !== 200 || !is_array($res['body']) || empty($res['body']['name'])) {
        error_log("fb_tambah gagal pada jalur [$jalur] status {$res['status']}");
        return null;
    }
    return (string)$res['body']['name'];
}

/**
 * Menghapus suatu node/jalur pada RTDB (DELETE)
 */
function fb_hapus(string $jalur): bool
{
    $res = fb_eksekusi_http($jalur, 'DELETE');
    return ($res['status'] === 200);
}

/**
 * Mengambil data beserta ETag untuk konkurensi aman (Optimistic Locking)
 *
 * @return array ['data' => mixed, 'etag' => string|null]
 */
function fb_ambil_etag(string $jalur): array
{
    $res = fb_eksekusi_http($jalur, 'GET', null, ['X-Firebase-ETag' => 'true']);
    $etag = $res['headers']['etag'] ?? null;
    if ($etag !== null) {
        $etag = trim($etag);
    }
    return [
        'data' => $res['body'],
        'etag' => $etag,
    ];
}

/**
 * Menyimpan data secara bersyarat hanya jika ETag cocok (If-Match)
 * Bila ETag berbeda (ada modifikasi dari proses lain), Firebase mengembalikan status 412 (Precondition Failed).
 *
 * @return array ['sukses' => bool, 'status' => int, 'data' => mixed, 'etag' => string|null]
 */
function fb_simpan_jika_etag(string $jalur, mixed $data, ?string $etag): array
{
    $header = [];
    if (!empty($etag)) {
        $header['If-Match'] = $etag;
    }

    $res = fb_eksekusi_http($jalur, 'PUT', $data, $header);
    $sukses = ($res['status'] === 200);
    $etag_baru = $res['headers']['etag'] ?? null;

    return [
        'sukses' => $sukses,
        'status' => $res['status'],
        'data'   => $res['body'],
        'etag'   => $etag_baru,
    ];
}

/**
 * Eksekusi pembaruan bersyarat (Optimistic Concurrency Control) dengan percobaan ulang otomatis
 * Mengulangi proses hingga maksimal 5 kali bila terjadi konflik (status 412).
 *
 * @param string $jalur Jalur node yang akan diperbarui
 * @param callable $fn_modifikasi Fungsi Callback ($data_saat_ini) => $data_baru (kembalikan false untuk membatalkan)
 * @param int $maks_percobaan Maksimal perulangan bila terjadi tabrakan (default 5)
 * @return bool Mengembalikan true jika berhasil diperbarui, false jika gagal/dibatalkan
 */
function fb_perbarui_bersyarat(string $jalur, callable $fn_modifikasi, int $maks_percobaan = 5): bool
{
    for ($i = 0; $i < $maks_percobaan; $i++) {
        $ambil = fb_ambil_etag($jalur);
        $data_saat_ini = $ambil['data'];
        $etag = $ambil['etag'];

        // Panggil logika modifikasi
        $data_baru = $fn_modifikasi($data_saat_ini);
        if ($data_baru === false) {
            // Callback membatalkan pembaruan (misal stok tidak mencukupi)
            return false;
        }

        $hasil = fb_simpan_jika_etag($jalur, $data_baru, $etag);
        if ($hasil['sukses']) {
            return true;
        }

        // Jika status 412 (data telah diubah pihak lain), tunggu jeda singkat acak lalu ulangi
        if ($hasil['status'] === 412) {
            usleep(random_int(10000, 50000)); // 10ms - 50ms
            continue;
        }

        // Jika status selain 412 gagal permanen
        error_log("fb_perbarui_bersyarat gagal pada [$jalur] status {$hasil['status']}");
        return false;
    }

    error_log("fb_perbarui_bersyarat melebihi batas percobaan ($maks_percobaan kali) pada [$jalur]");
    return false;
}
