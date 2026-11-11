<?php
/**
 * Daftar Program Sosial - Pekarangan Institute
 * Menampilkan program berstatus 'aktif' dan 'selesai' dengan progres dana aktual (donasi berhasil).
 */

require_once __DIR__ . '/includes/fungsi.php';

$judul_halaman = 'Program Sosial';
$menu_aktif = 'program';
$deskripsi_halaman = 'Inisiatif dan program sosial kemasyarakatan Pekarangan Institute untuk ketahanan pangan dan pemberdayaan komunitas.';

// Ambil program berstatus 'aktif' dan 'selesai' dari RTDB
$semua = program_semua();
$daftar_program = [];
foreach ($semua as $p) {
    if (in_array($p['status'] ?? '', ['aktif', 'selesai'], true)) {
        $daftar_program[] = $p;
    }
}
usort($daftar_program, function ($a, $b) {
    $prioritas_a = (($a['status'] ?? '') === 'aktif') ? 1 : 2;
    $prioritas_b = (($b['status'] ?? '') === 'aktif') ? 1 : 2;
    if ($prioritas_a !== $prioritas_b) {
        return $prioritas_a <=> $prioritas_b;
    }
    return strcmp($b['dibuat_pada'] ?? '', $a['dibuat_pada'] ?? '');
});

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <div class="bagian-kepala">
        <h1>Program Sosial & Pemberdayaan</h1>
        <p>
            Setiap rupiah donasi disalurkan secara transparan untuk memfasilitasi bibit, sarana kebun produktif, serta pendampingan warga menuju ketahanan pangan mandiri.
        </p>
    </div>

    <?php if (!empty($daftar_program)): ?>
        <div class="grid-kartu-2">
            <?php foreach ($daftar_program as $prog): ?>
                <?php
                $progres = progres_program((int)$prog['id']);
                $target = (int)$prog['target_dana'];
                $terkumpul = (int)$progres['terkumpul'];
                $persen_asli = $target > 0 ? (int)round(($terkumpul / $target) * 100) : 0;
                $persen_visual = min(100, $persen_asli);
                $adalah_aktif = ($prog['status'] === 'aktif');
                ?>
                <article class="kartu">
                    <div class="foto-wadah">
                        <?php if (!empty($prog['foto_sampul']) && file_exists(__DIR__ . '/uploads/' . $prog['foto_sampul'])): ?>
                            <img src="<?= url('uploads/' . e($prog['foto_sampul'])) ?>" alt="<?= e($prog['judul']) ?>">
                        <?php else: ?>
                            <div class="foto-kosong">[Foto Program]</div>
                        <?php endif; ?>
                    </div>

                    <div class="kartu-badan">
                        <div class="baris-antara-wrap jarak-bawah-sm">
                            <div>
                                <?php if ($adalah_aktif): ?>
                                    <span class="lencana lencana-hijau">Aktif</span>
                                <?php else: ?>
                                    <span class="lencana lencana-kuning">Selesai</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h2 class="kartu-judul">
                            <a href="<?= url('program-detail.php?slug=' . urlencode($prog['slug'])) ?>">
                                <?= e($prog['judul']) ?>
                            </a>
                        </h2>

                        <p class="kartu-ringkasan"><?= e($prog['ringkasan']) ?></p>

                        <!-- Informasi Lokasi & Tenggat Waktu -->
                        <div class="kartu-info-tambahan">
                            <div>
                                <strong>Lokasi:</strong> <?= e($prog['lokasi']) ?>
                            </div>
                            <?php if (!empty($prog['tenggat'])): ?>
                                <div>
                                    <strong>Tenggat:</strong> <?= tanggal_id($prog['tenggat']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Bilah Progres Donasi (Visual dibatasi maks 100%, angka asli tetap ditampilkan) -->
                        <div class="progress-wadah">
                            <div class="progress-info">
                                <span class="progress-info-terkumpul"><?= rupiah($terkumpul) ?></span>
                                <span class="progress-info-target">Target: <?= rupiah($target) ?></span>
                            </div>
                            <div class="progress-track" role="progressbar" aria-valuenow="<?= $persen_visual ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progres donasi program">
                                <div class="progress-fill" style="width: <?= $persen_visual ?>%;"></div>
                            </div>
                            <div class="baris-antara teks-xs teks-redup jarak-atas-xs">
                                <span>Tercapai <strong><?= $persen_asli ?>%</strong></span>
                                <span><?= (int)$progres['jumlah_donatur'] ?> Donatur</span>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="kartu-kaki">
                            <div class="grup-tombol">
                                <?php if ($adalah_aktif): ?>
                                    <a href="<?= url('donasi.php?program=' . urlencode($prog['slug'])) ?>" class="tombol tombol-utama tata-flex-1">
                                        Donasi Sekarang
                                    </a>
                                <?php endif; ?>
                                <a href="<?= url('program-detail.php?slug=' . urlencode($prog['slug'])) ?>" class="tombol tombol-sekunder <?= $adalah_aktif ? 'tata-flex-1' : 'tombol-blok' ?>">
                                    Detail Program
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="kotak-kosong">
            <h3>Belum ada program saat ini.</h3>
            <p>Program sosial sedang dipersiapkan oleh tim fasilitator Pekarangan Institute.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
