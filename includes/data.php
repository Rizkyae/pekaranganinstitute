<?php
/**
 * Lapisan Abstraksi Data Pekarangan Institute (Firebase Realtime Database)
 *
 * Menyediakan fungsi-fungsi tingkat tinggi berbahasa Indonesia untuk mengakses
 * dan memanipulasi data (/admin, /produk, /program, /pesanan, /donasi, /laporan, /pengaturan).
 * Halaman aplikasi tidak boleh memanggil fungsi tingkat rendah fb_* secara langsung.
 */

require_once __DIR__ . '/firebase.php';

/* =========================================================================
 * 1. PENGATURAN SISTEM (/pengaturan)
 * ========================================================================= */

/**
 * Mengambil nilai konfigurasi pengaturan sistem berdasarkan kunci
 */
function pengaturan_ambil(string $kunci, ?string $bawaan = null): ?string
{
    static $cache = null;

    if ($cache === null) {
        $semua = fb_ambil('pengaturan');
        $cache = is_array($semua) ? $semua : [];
    }

    if (isset($cache[$kunci])) {
        return (string)$cache[$kunci];
    }

    return $bawaan;
}

/**
 * Mengambil seluruh daftar pengaturan sistem
 */
function pengaturan_semua(): array
{
    $semua = fb_ambil('pengaturan');
    return is_array($semua) ? $semua : [];
}

/**
 * Menyimpan satu nilai pengaturan sistem
 */
function pengaturan_simpan(string $kunci, string $nilai): bool
{
    $hasil = fb_simpan("pengaturan/{$kunci}", $nilai);
    return ($hasil !== false);
}

/**
 * Menyimpan banyak pengaturan sistem sekaligus
 */
function pengaturan_simpan_banyak(array $data): bool
{
    $hasil = fb_ubah('pengaturan', $data);
    return ($hasil !== false);
}

/* =========================================================================
 * 2. ADMINISTRATOR SISTEM (/admin)
 * ========================================================================= */

/**
 * Mengambil seluruh akun administrator
 */
function admin_semua(): array
{
    $data = fb_ambil('admin');
    if (!is_array($data)) {
        return [];
    }

    $hasil = [];
    foreach ($data as $k => $item) {
        if (is_array($item)) {
            $item['id'] = $item['id'] ?? (string)$k;
            $hasil[] = $item;
        }
    }
    return $hasil;
}

/**
 * Menghitung total administrator yang terdaftar
 */
function admin_hitung(): int
{
    return count(admin_semua());
}

/**
 * Mengambil satu administrator berdasarkan ID
 */
function admin_ambil(string $id): ?array
{
    $item = fb_ambil("admin/{$id}");
    if (!is_array($item)) {
        return null;
    }
    $item['id'] = $item['id'] ?? $id;
    return $item;
}

/**
 * Mencari akun administrator berdasarkan alamat email
 */
function admin_by_email(string $email): ?array
{
    $semua = admin_semua();
    $email_cari = strtolower(trim($email));
    foreach ($semua as $item) {
        if (strtolower(trim($item['email'] ?? '')) === $email_cari) {
            return $item;
        }
    }
    return null;
}

/**
 * Menambahkan akun administrator baru
 */
function admin_tambah(array $data): ?string
{
    $sekarang = date('Y-m-d H:i:s');
    $data['dibuat_pada'] = $data['dibuat_pada'] ?? $sekarang;

    $id = fb_tambah('admin', $data);
    if ($id !== null) {
        fb_ubah("admin/{$id}", ['id' => $id]);
    }
    return $id;
}

/* =========================================================================
 * 3. PRODUK (/produk)
 * ========================================================================= */

/**
 * Mengambil seluruh produk, diurutkan berdasarkan dibuat_pada descending
 */
function produk_semua(bool $hanya_tampil = false): array
{
    $data = fb_ambil('produk');
    if (!is_array($data)) {
        return [];
    }

    $hasil = [];
    foreach ($data as $k => $item) {
        if (is_array($item)) {
            $item['id'] = $item['id'] ?? (string)$k;
            if ($hanya_tampil && ($item['status'] ?? '') !== 'tampil') {
                continue;
            }
            $hasil[] = $item;
        }
    }

    // Urutkan berdasarkan dibuat_pada descending
    usort($hasil, function ($a, $b) {
        return strcmp($b['dibuat_pada'] ?? '', $a['dibuat_pada'] ?? '');
    });

    return $hasil;
}

/**
 * Mengambil data produk berdasarkan ID
 */
function produk_ambil(string $id): ?array
{
    $item = fb_ambil("produk/{$id}");
    if (!is_array($item)) {
        return null;
    }
    $item['id'] = $item['id'] ?? $id;
    return $item;
}

/**
 * Mengambil data produk berdasarkan slug
 */
function produk_by_slug(string $slug): ?array
{
    $semua = produk_semua();
    foreach ($semua as $item) {
        if (($item['slug'] ?? '') === $slug) {
            return $item;
        }
    }
    return null;
}

/**
 * Menambahkan data produk baru
 */
function produk_tambah(array $data): ?string
{
    $sekarang = date('Y-m-d H:i:s');
    $data['dibuat_pada'] = $data['dibuat_pada'] ?? $sekarang;
    $data['diubah_pada'] = $sekarang;
    $data['harga'] = (int)($data['harga'] ?? 0);
    $data['stok'] = (int)($data['stok'] ?? 0);
    $data['is_contoh'] = (int)($data['is_contoh'] ?? 0);

    $id = fb_tambah('produk', $data);
    if ($id !== null) {
        fb_ubah("produk/{$id}", ['id' => $id]);
    }
    return $id;
}

/**
 * Memperbarui data produk
 */
function produk_ubah(string $id, array $data): bool
{
    $data['diubah_pada'] = date('Y-m-d H:i:s');
    if (isset($data['harga'])) {
        $data['harga'] = (int)$data['harga'];
    }
    if (isset($data['stok'])) {
        $data['stok'] = (int)$data['stok'];
    }
    $hasil = fb_ubah("produk/{$id}", $data);
    return ($hasil !== false);
}

/**
 * Memeriksa apakah suatu produk pernah memiliki pesanan
 */
function produk_pernah_dipesan(string $produk_id): bool
{
    $pesanan = fb_ambil('pesanan');
    if (!is_array($pesanan)) {
        return false;
    }
    foreach ($pesanan as $item) {
        if (is_array($item) && (string)($item['produk_id'] ?? '') === (string)$produk_id) {
            return true;
        }
    }
    return false;
}

/**
 * Menghapus produk (hanya jika belum pernah dipesan)
 */
function produk_hapus(string $id): bool
{
    if (produk_pernah_dipesan($id)) {
        return false;
    }
    return fb_hapus("produk/{$id}");
}

/**
 * Mengurangi stok produk secara bersyarat (Optimistic Locking dengan ETag)
 * Menolak jika stok saat ini kurang dari jumlah yang diminta.
 */
function produk_kurangi_stok_bersyarat(string $produk_id, int $jumlah): bool
{
    if ($jumlah <= 0) {
        return true;
    }
    $jalur = "produk/{$produk_id}";
    return fb_perbarui_bersyarat($jalur, function ($produk) use ($jumlah, $produk_id) {
        if (!is_array($produk)) {
            return false;
        }
        $stok_saat_ini = (int)($produk['stok'] ?? 0);
        if ($stok_saat_ini < $jumlah) {
            error_log("produk_kurangi_stok_bersyarat: Stok tidak cukup ($stok_saat_ini < $jumlah) untuk produk {$produk_id}");
            return false;
        }
        $produk['stok'] = $stok_saat_ini - $jumlah;
        $produk['diubah_pada'] = date('Y-m-d H:i:s');
        return $produk;
    });
}

/**
 * Menambah / Mengembalikan stok produk secara bersyarat (Optimistic Locking dengan ETag)
 */
function produk_tambah_stok_bersyarat(string $produk_id, int $jumlah): bool
{
    if ($jumlah <= 0) {
        return true;
    }
    $jalur = "produk/{$produk_id}";
    return fb_perbarui_bersyarat($jalur, function ($produk) use ($jumlah) {
        if (!is_array($produk)) {
            return false;
        }
        $stok_saat_ini = (int)($produk['stok'] ?? 0);
        $produk['stok'] = $stok_saat_ini + $jumlah;
        $produk['diubah_pada'] = date('Y-m-d H:i:s');
        return $produk;
    });
}

/* =========================================================================
 * 4. PROGRAM SOSIAL (/program)
 * ========================================================================= */

/**
 * Mengambil seluruh program sosial, diurutkan berdasarkan dibuat_pada descending
 */
function program_semua(bool $hanya_aktif = false): array
{
    $data = fb_ambil('program');
    if (!is_array($data)) {
        return [];
    }

    $hasil = [];
    foreach ($data as $k => $item) {
        if (is_array($item)) {
            $item['id'] = $item['id'] ?? (string)$k;
            if ($hanya_aktif && ($item['status'] ?? '') !== 'aktif') {
                continue;
            }
            $hasil[] = $item;
        }
    }

    usort($hasil, function ($a, $b) {
        return strcmp($b['dibuat_pada'] ?? '', $a['dibuat_pada'] ?? '');
    });

    return $hasil;
}

/**
 * Mengambil satu program sosial berdasarkan ID
 */
function program_ambil(string $id): ?array
{
    $item = fb_ambil("program/{$id}");
    if (!is_array($item)) {
        return null;
    }
    $item['id'] = $item['id'] ?? $id;
    return $item;
}

/**
 * Mengambil satu program sosial berdasarkan slug
 */
function program_by_slug(string $slug): ?array
{
    $semua = program_semua();
    foreach ($semua as $item) {
        if (($item['slug'] ?? '') === $slug) {
            return $item;
        }
    }
    return null;
}

/**
 * Menambahkan program sosial baru
 */
function program_tambah(array $data): ?string
{
    $sekarang = date('Y-m-d H:i:s');
    $data['dibuat_pada'] = $data['dibuat_pada'] ?? $sekarang;
    $data['diubah_pada'] = $sekarang;
    $data['target_dana'] = (int)($data['target_dana'] ?? 0);
    $data['is_contoh'] = (int)($data['is_contoh'] ?? 0);

    $id = fb_tambah('program', $data);
    if ($id !== null) {
        fb_ubah("program/{$id}", ['id' => $id]);
    }
    return $id;
}

/**
 * Memperbarui program sosial
 */
function program_ubah(string $id, array $data): bool
{
    $data['diubah_pada'] = date('Y-m-d H:i:s');
    if (isset($data['target_dana'])) {
        $data['target_dana'] = (int)$data['target_dana'];
    }
    $hasil = fb_ubah("program/{$id}", $data);
    return ($hasil !== false);
}

/**
 * Memeriksa apakah suatu program memiliki riwayat donasi
 */
function program_punya_donasi(string $program_id): bool
{
    $donasi = fb_ambil('donasi');
    if (!is_array($donasi)) {
        return false;
    }
    foreach ($donasi as $item) {
        if (is_array($item) && (string)($item['program_id'] ?? '') === (string)$program_id) {
            return true;
        }
    }
    return false;
}

/**
 * Menghapus program sosial (hanya jika belum memiliki donasi)
 */
function program_hapus(string $id): bool
{
    if (program_punya_donasi($id)) {
        return false;
    }
    return fb_hapus("program/{$id}");
}

/**
 * Menghitung progres dana dan jumlah donatur suatu program
 * Hanya menghitung donasi dengan status 'berhasil'
 */
function progres_program(string|int $program_id): array
{
    $prog = program_ambil((string)$program_id);
    $target = (int)($prog['target_dana'] ?? 0);

    $semua_donasi = fb_ambil('donasi');
    $terkumpul = 0;
    $jumlah_donatur = 0;

    if (is_array($semua_donasi)) {
        foreach ($semua_donasi as $d) {
            if (is_array($d) && (string)($d['program_id'] ?? '') === (string)$program_id && ($d['status'] ?? '') === 'berhasil') {
                $terkumpul += (int)($d['jumlah'] ?? 0);
                $jumlah_donatur++;
            }
        }
    }

    $persen = $target > 0 ? min(100, (int)round(($terkumpul / $target) * 100)) : 0;

    return [
        'target'         => $target,
        'terkumpul'      => $terkumpul,
        'persen'         => $persen,
        'jumlah_donatur' => $jumlah_donatur,
    ];
}

/* =========================================================================
 * 5. PESANAN PRODUK (/pesanan)
 * ========================================================================= */

/**
 * Mengambil seluruh data pesanan, diurutkan dibuat_pada descending
 */
function pesanan_semua(): array
{
    $data = fb_ambil('pesanan');
    if (!is_array($data)) {
        return [];
    }

    $hasil = [];
    foreach ($data as $k => $item) {
        if (is_array($item)) {
            $item['id'] = $item['id'] ?? (string)$k;
            $hasil[] = $item;
        }
    }

    usort($hasil, function ($a, $b) {
        return strcmp($b['dibuat_pada'] ?? '', $a['dibuat_pada'] ?? '');
    });

    return $hasil;
}

/**
 * Mengambil satu pesanan berdasarkan ID
 */
function pesanan_ambil(string $id): ?array
{
    $item = fb_ambil("pesanan/{$id}");
    if (!is_array($item)) {
        return null;
    }
    $item['id'] = $item['id'] ?? $id;
    return $item;
}

/**
 * Mengambil satu pesanan berdasarkan kode pesanan (mis. ORD-YYYYMMDD-XXXX)
 */
function pesanan_by_kode(string $kode): ?array
{
    $semua = pesanan_semua();
    foreach ($semua as $item) {
        if (($item['kode'] ?? '') === $kode) {
            return $item;
        }
    }
    return null;
}

/**
 * Menghasilkan kode pesanan unik (ORD-YYYYMMDD-XXXX)
 */
function kode_pesanan_unik(): string
{
    for ($i = 0; $i < 10; $i++) {
        $kode = buat_kode('ORD');
        if (pesanan_by_kode($kode) === null) {
            return $kode;
        }
    }
    return buat_kode('ORD') . '-' . random_int(10, 99);
}

/**
 * Membuat pesanan produk baru
 */
function pesanan_buat(array $data): ?string
{
    $sekarang = date('Y-m-d H:i:s');
    if (empty($data['kode'])) {
        $data['kode'] = kode_pesanan_unik();
    }
    $data['dibuat_pada'] = $data['dibuat_pada'] ?? $sekarang;
    $data['diubah_pada'] = $sekarang;
    $data['status'] = $data['status'] ?? 'menunggu';
    $data['harga_satuan'] = (int)($data['harga_satuan'] ?? 0);
    $data['jumlah'] = (int)($data['jumlah'] ?? 1);
    $data['total'] = (int)($data['total'] ?? ($data['harga_satuan'] * $data['jumlah']));
    $data['is_contoh'] = (int)($data['is_contoh'] ?? 0);

    $id = fb_tambah('pesanan', $data);
    if ($id !== null) {
        fb_ubah("pesanan/{$id}", ['id' => $id]);
    }
    return $id;
}

/**
 * Memperbarui status pesanan dengan aturan bisnis pembaruan stok bersyarat (ETag)
 * - Stok berkurang tepat sekali saat pesanan beralih menjadi 'dibayar'.
 * - Stok dikembalikan jika pesanan dibatalkan ('batal') setelah sebelumnya 'dibayar'.
 */
function pesanan_ubah_status(string $id, string $status_baru): bool
{
    $pesanan = pesanan_ambil($id);
    if (!$pesanan) {
        return false;
    }

    $status_lama = $pesanan['status'] ?? 'menunggu';
    if ($status_lama === $status_baru) {
        return true;
    }

    $produk_id = (string)($pesanan['produk_id'] ?? '');
    $jumlah = (int)($pesanan['jumlah'] ?? 0);

    // Transisi 1: Berubah menjadi 'dibayar' -> Kurangi stok produk secara bersyarat ETag
    if ($status_lama !== 'dibayar' && $status_baru === 'dibayar') {
        if ($produk_id !== '' && $jumlah > 0) {
            $sukses_stok = produk_kurangi_stok_bersyarat($produk_id, $jumlah);
            if (!$sukses_stok) {
                error_log("pesanan_ubah_status: Gagal mengurangi stok untuk pesanan {$id} (produk {$produk_id})");
                return false;
            }
        }
    }

    // Transisi 2: Dari 'dibayar' dibatalkan menjadi 'batal' -> Kembalikan stok produk bersyarat ETag
    if ($status_lama === 'dibayar' && $status_baru === 'batal') {
        if ($produk_id !== '' && $jumlah > 0) {
            produk_tambah_stok_bersyarat($produk_id, $jumlah);
        }
    }

    // Perbarui status pesanan
    $hasil = fb_ubah("pesanan/{$id}", [
        'status'      => $status_baru,
        'diubah_pada' => date('Y-m-d H:i:s'),
    ]);

    return ($hasil !== false);
}

/* =========================================================================
 * 6. DONASI PROGRAM (/donasi)
 * ========================================================================= */

/**
 * Mengambil seluruh data donasi, diurutkan dibuat_pada descending
 */
function donasi_semua(): array
{
    $data = fb_ambil('donasi');
    if (!is_array($data)) {
        return [];
    }

    $hasil = [];
    foreach ($data as $k => $item) {
        if (is_array($item)) {
            $item['id'] = $item['id'] ?? (string)$k;
            $hasil[] = $item;
        }
    }

    usort($hasil, function ($a, $b) {
        return strcmp($b['dibuat_pada'] ?? '', $a['dibuat_pada'] ?? '');
    });

    return $hasil;
}

/**
 * Mengambil satu donasi berdasarkan ID
 */
function donasi_ambil(string $id): ?array
{
    $item = fb_ambil("donasi/{$id}");
    if (!is_array($item)) {
        return null;
    }
    $item['id'] = $item['id'] ?? $id;
    return $item;
}

/**
 * Mengambil donasi berdasarkan kode unik (DON-YYYYMMDD-XXXX)
 */
function donasi_by_kode(string $kode): ?array
{
    $semua = donasi_semua();
    foreach ($semua as $item) {
        if (($item['kode'] ?? '') === $kode) {
            return $item;
        }
    }
    return null;
}

/**
 * Menghasilkan kode donasi unik (DON-YYYYMMDD-XXXX)
 */
function kode_donasi_unik(): string
{
    for ($i = 0; $i < 10; $i++) {
        $kode = buat_kode('DON');
        if (donasi_by_kode($kode) === null) {
            return $kode;
        }
    }
    return buat_kode('DON') . '-' . random_int(10, 99);
}

/**
 * Membuat donasi baru
 */
function donasi_buat(array $data): ?string
{
    $sekarang = date('Y-m-d H:i:s');
    if (empty($data['kode'])) {
        $data['kode'] = kode_donasi_unik();
    }
    $data['dibuat_pada'] = $data['dibuat_pada'] ?? $sekarang;
    $data['diubah_pada'] = $sekarang;
    $data['status'] = $data['status'] ?? 'menunggu';
    $data['jumlah'] = (int)($data['jumlah'] ?? 0);
    $data['anonim'] = (int)($data['anonim'] ?? 0);
    $data['is_contoh'] = (int)($data['is_contoh'] ?? 0);

    $id = fb_tambah('donasi', $data);
    if ($id !== null) {
        fb_ubah("donasi/{$id}", ['id' => $id]);
    }
    return $id;
}

/**
 * Memperbarui status donasi (menunggu, berhasil, gagal)
 */
function donasi_ubah_status(string $id, string $status_baru): bool
{
    $hasil = fb_ubah("donasi/{$id}", [
        'status'      => $status_baru,
        'diubah_pada' => date('Y-m-d H:i:s'),
    ]);
    return ($hasil !== false);
}

/**
 * Mencatat klaim donatur bahwa pembayaran QRIS sudah dilakukan.
 * Status tetap menunggu sampai admin memverifikasi dana masuk.
 */
function donasi_catat_klaim_pembayaran(string $id): bool
{
    $donasi = donasi_ambil($id);
    if (!$donasi || ($donasi['status'] ?? '') !== 'menunggu') {
        return false;
    }

    if (!empty($donasi['pembayaran_dikonfirmasi_donatur_pada'])) {
        return true;
    }

    $sekarang = date('Y-m-d H:i:s');
    $hasil = fb_ubah("donasi/{$id}", [
        'metode_pembayaran' => 'QRIS',
        'pembayaran_dikonfirmasi_donatur_pada' => $sekarang,
        'diubah_pada' => $sekarang,
    ]);

    return ($hasil !== false);
}

/**
 * Menghitung total donasi berhasil untuk suatu program
 */
function donasi_total_program(string|int $program_id): int
{
    $semua = fb_ambil('donasi');
    if (!is_array($semua)) {
        return 0;
    }
    $total = 0;
    foreach ($semua as $d) {
        if (is_array($d) && (string)($d['program_id'] ?? '') === (string)$program_id && ($d['status'] ?? '') === 'berhasil') {
            $total += (int)($d['jumlah'] ?? 0);
        }
    }
    return $total;
}

/**
 * Menghitung total keseluruhan donasi berhasil yang terkumpul
 */
function donasi_terkumpul_semua(): int
{
    $semua = fb_ambil('donasi');
    if (!is_array($semua)) {
        return 0;
    }
    $total = 0;
    foreach ($semua as $d) {
        if (is_array($d) && ($d['status'] ?? '') === 'berhasil') {
            $total += (int)($d['jumlah'] ?? 0);
        }
    }
    return $total;
}

/* =========================================================================
 * 7. LAPORAN TRANSPARANSI (/laporan)
 * ========================================================================= */

/**
 * Mengambil seluruh laporan transparansi, diurutkan dibuat_pada descending
 */
function laporan_semua(bool $hanya_tampil = false): array
{
    $data = fb_ambil('laporan');
    if (!is_array($data)) {
        return [];
    }

    $hasil = [];
    foreach ($data as $k => $item) {
        if (is_array($item)) {
            $item['id'] = $item['id'] ?? (string)$k;
            if ($hanya_tampil && ($item['status'] ?? '') !== 'tampil') {
                continue;
            }
            $hasil[] = $item;
        }
    }

    usort($hasil, function ($a, $b) {
        return strcmp($b['dibuat_pada'] ?? '', $a['dibuat_pada'] ?? '');
    });

    return $hasil;
}

/**
 * Mengambil satu laporan berdasarkan ID
 */
function laporan_ambil(string $id): ?array
{
    $item = fb_ambil("laporan/{$id}");
    if (!is_array($item)) {
        return null;
    }
    $item['id'] = $item['id'] ?? $id;
    return $item;
}

/**
 * Menambahkan laporan transparansi baru
 */
function laporan_tambah(array $data): ?string
{
    $sekarang = date('Y-m-d H:i:s');
    $data['dibuat_pada'] = $data['dibuat_pada'] ?? $sekarang;
    $data['is_contoh'] = (int)($data['is_contoh'] ?? 0);

    $id = fb_tambah('laporan', $data);
    if ($id !== null) {
        fb_ubah("laporan/{$id}", ['id' => $id]);
    }
    return $id;
}

/**
 * Memperbarui data laporan transparansi
 */
function laporan_ubah(string $id, array $data): bool
{
    $hasil = fb_ubah("laporan/{$id}", $data);
    return ($hasil !== false);
}

/**
 * Menghapus laporan transparansi
 */
function laporan_hapus(string $id): bool
{
    return fb_hapus("laporan/{$id}");
}

/* =========================================================================
 * 8. PEMUATAN DATA CONTOH (SEED DATA)
 * ========================================================================= */

/**
 * Memuat data awal dari berkas database/seed.json ke Firebase RTDB
 * Memastikan semua data contoh bertanda is_contoh = 1
 */
function data_muat_seed(): bool
{
    $berkas_seed = __DIR__ . '/../database/seed.json';
    if (!file_exists($berkas_seed)) {
        error_log('data_muat_seed: Berkas seed.json tidak ditemukan');
        return false;
    }

    $konten = file_get_contents($berkas_seed);
    $data = json_decode($konten, true);
    if (!is_array($data)) {
        error_log('data_muat_seed: Format seed.json tidak valid');
        return false;
    }

    // Pastikan semua data produk, program, donasi, pesanan, laporan ditandai is_contoh = 1
    foreach (['produk', 'program', 'donasi', 'pesanan', 'laporan'] as $koleksi) {
        if (isset($data[$koleksi]) && is_array($data[$koleksi])) {
            foreach ($data[$koleksi] as $k => &$record) {
                if (is_array($record)) {
                    $record['is_contoh'] = 1;
                    $record['id'] = $record['id'] ?? (string)$k;
                }
            }
            unset($record);
            // Simpan per koleksi
            $res = fb_simpan($koleksi, $data[$koleksi]);
            if ($res === false) {
                return false;
            }
        }
    }

    if (isset($data['pengaturan']) && is_array($data['pengaturan'])) {
        $res = fb_simpan('pengaturan', $data['pengaturan']);
        if ($res === false) {
            return false;
        }
    }

    return true;
}

/* =========================================================================
 * 9. PENGELOLAAN KONTEN HALAMAN PUBLIK (/pengaturan)
 * ========================================================================= */

/**
 * Menyediakan teks dan konfigurasi bawaan agar situs tidak pernah kosong
 */
function konten_bawaan(?string $kunci = null): mixed
{
    $bawaan = [
        // Beranda: Hero & Langkah
        'hero_judul'          => 'Berbelanja, Berbagi, Berdampak.',
        'hero_teks'           => 'Inisiatif kewirausahaan sosial yang menghubungkan produk hasil pekarangan dengan pemberdayaan dan program sosial kemasyarakatan.',
        'hero_foto'           => '',
        'langkah_1_judul'     => 'Berbelanja',
        'langkah_1_teks'      => 'Beli produk berkualitas hasil olahan komunitas pekarangan dan mitra lokal secara adil.',
        'langkah_2_judul'     => 'Berbagi',
        'langkah_2_teks'      => 'Sebagian hasil penjualan dan donasi Anda dialokasikan langsung untuk program sosial produktif.',
        'langkah_3_judul'     => 'Berdampak',
        'langkah_3_teks'      => 'Menguatkan kemandirian pangan keluarga dhuafa dan menciptakan lingkungan yang lestari.',

        // Halaman Tentang
        'tentang_teks'        => "Pekarangan Institute adalah inisiatif kewirausahaan sosial yang bergerak di bidang pemberdayaan pekarangan produktif.\n\nKami meyakini bahwa pekarangan rumah bukan sekadar lahan kosong di sekitar tempat tinggal, melainkan ruang hidup strategis yang menyimpan potensi besar untuk pemenuhan gizi keluarga, pelestarian keanekaragaman hayati nabati lokal, serta penguatan ketahanan ekonomi mikro komunitas.",
        'tentang_foto'        => '',
        'pilar_1_judul'       => '1. Berbelanja',
        'pilar_1_teks'        => 'Membeli produk pekarangan berkualitas langsung dari pekebun dan pengrajin lokal dengan harga yang adil bagi produsen.',
        'pilar_2_judul'       => '2. Berbagi',
        'pilar_2_teks'        => 'Menyisihkan surplus usaha dan memfasilitasi donasi publik secara transparan untuk mendanai bibit, pelatihan, dan pendampingan.',
        'pilar_3_judul'       => '3. Berdampak',
        'pilar_3_teks'        => 'Menghasilkan perubahan terukur: keluarga dhuafa memiliki akses pangan segar mandiri dan komunitas menjadi lebih tangguh.',

        // Footer & Logo
        'footer_teks'         => 'Pemberdayaan pekarangan produktif dan penguatan ekonomi komunitas menuju ketahanan pangan keluarga mandiri.',
        'logo_mitra'          => '',
        'logo_instansi'       => '',
    ];

    if ($kunci !== null) {
        return $bawaan[$kunci] ?? '';
    }

    return $bawaan;
}

/**
 * Mengambil nilai konten dari pengaturan RTDB dengan cadangan nilai bawaan
 */
function konten_ambil(string $kunci): string
{
    $bawaan = (string)konten_bawaan($kunci);
    $nilai = pengaturan_ambil($kunci, null);
    if ($nilai === null || trim($nilai) === '') {
        return $bawaan;
    }
    return $nilai;
}

/**
 * Mengambil seluruh data konten halaman
 */
function konten_semua(): array
{
    $bawaan = konten_bawaan();
    $pengaturan = pengaturan_semua();
    $hasil = [];
    foreach ($bawaan as $k => $v) {
        $simpan = $pengaturan[$k] ?? null;
        $hasil[$k] = ($simpan !== null && trim((string)$simpan) !== '') ? (string)$simpan : $v;
    }
    return $hasil;
}

/**
 * Mengembalikan konten bagian tertentu ke nilai bawaan dan menghapus file unggahan terkait
 */
function konten_reset_bagian(string $bagian): bool
{
    $pemetaan = [
        'beranda' => ['hero_judul', 'hero_teks', 'hero_foto', 'langkah_1_judul', 'langkah_1_teks', 'langkah_2_judul', 'langkah_2_teks', 'langkah_3_judul', 'langkah_3_teks'],
        'tentang' => ['tentang_teks', 'tentang_foto', 'pilar_1_judul', 'pilar_1_teks', 'pilar_2_judul', 'pilar_2_teks', 'pilar_3_judul', 'pilar_3_teks'],
        'footer'  => ['footer_teks'],
        'logo'    => ['logo_mitra', 'logo_instansi'],
    ];

    if (!isset($pemetaan[$bagian])) {
        return false;
    }

    $kunci_bagian = $pemetaan[$bagian];
    $bawaan = konten_bawaan();
    $data_reset = [];
    $file_lama_dihapus = [];

    foreach ($kunci_bagian as $k) {
        if (in_array($k, ['hero_foto', 'tentang_foto', 'logo_mitra', 'logo_instansi'], true)) {
            $file_lama = pengaturan_ambil($k, '');
            if (!empty($file_lama)) $file_lama_dihapus[] = $file_lama;
        }
        $data_reset[$k] = (string)($bawaan[$k] ?? '');
    }

    $sukses = pengaturan_simpan_banyak($data_reset);
    if ($sukses) {
        $pengaturan_tersimpan = pengaturan_semua();
        $media_tersimpan = array_intersect_key($pengaturan_tersimpan, array_flip(['logo_mitra', 'logo_instansi', 'hero_foto', 'tentang_foto']));
        foreach (array_unique($file_lama_dihapus) as $nama) {
            if (!in_array($nama, $media_tersimpan, true)) {
                hapus_berkas_unggahan((string)$nama);
            }
        }
    }

    return $sukses;
}
