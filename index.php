<?php
/**
 * Halaman Beranda (Index) - Pekarangan Institute
 * Tata letak tetap sama seperti sebelumnya (Hero, Tiga Langkah, Produk Pilihan Terbaru,
 * Program Sosial Berjalan, Blok Ajakan Akhir) dengan tampilan visual modern palet mint-teal CoachPro.
 */

require_once __DIR__ . '/includes/fungsi.php';

$judul_halaman = 'Beranda';
$menu_aktif = 'beranda';
$deskripsi_halaman = ambil_pengaturan('hero_teks');

// Ambil 3 produk terbaru yang berstatus 'tampil'
$daftar_produk = array_slice(produk_semua(true), 0, 3);

// Ambil 2 program aktif terbaru
$daftar_program = array_slice(program_semua(true), 0, 2);

require_once __DIR__ . '/includes/header.php';
?>

<div class="kontainer">
    <!-- 1. Hero Section -->
    <section class="hero" aria-labelledby="hero-judul">
        <div class="hero-grid">
            <div class="hero-konten">
                <span class="hero-label">BERBELANJA &bull; BERBAGI &bull; BERDAMPAK</span>
                <h1 class="hero-judul" id="hero-judul">
                    <?= e(konten_ambil('hero_judul', 'Pemberdayaan Pekarangan untuk Kesejahteraan Komunitas')) ?>
                </h1>
                <p class="hero-teks">
                    <?= e(konten_ambil('hero_teks', 'Inisiatif kewirausahaan sosial yang menghubungkan produk hasil pekarangan dengan pemberdayaan dan program sosial kemasyarakatan.')) ?>
                </p>
                <div class="grup-tombol">
                    <a href="<?= url('produk.php') ?>" class="tombol tombol-utama">Belanja Sekarang</a>
                    <a href="<?= url('program.php') ?>" class="tombol tombol-sekunder">Lihat Program</a>
                </div>
            </div>
            <div class="hero-media">
                <div class="foto-wadah">
                    <?php 
                    $hero_foto = konten_ambil('hero_foto');
                    if (!empty($hero_foto) && file_exists(__DIR__ . '/uploads/' . $hero_foto)): 
                    ?>
                        <img src="<?= url('uploads/' . e($hero_foto)) ?>" alt="<?= e(konten_ambil('hero_judul')) ?>">
                    <?php else: ?>
                        <div class="foto-kosong" aria-label="Placeholder foto hero">[Foto Pekarangan]</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Tiga Langkah (Prinsip Pekarangan) -->
    <section class="bagian" aria-labelledby="judul-langkah">
        <div class="bagian-kepala">
            <h2 id="judul-langkah">Bagaimana Kami Bekerja</h2>
            <p>Setiap transaksi bernilai ganda: memberdayakan pekebun lokal sekaligus mengalirkan kebaikan untuk sesama.</p>
        </div>
        <div class="langkah-grid">
            <div class="langkah-kartu langkah-1">
                <div class="langkah-ikon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                </div>
                <span class="langkah-nomor">Langkah 1</span>
                <h3><?= e(konten_ambil('langkah_1_judul')) ?></h3>
                <p><?= e(konten_ambil('langkah_1_teks')) ?></p>
            </div>
            <div class="langkah-kartu langkah-2">
                <div class="langkah-ikon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </div>
                <span class="langkah-nomor">Langkah 2</span>
                <h3><?= e(konten_ambil('langkah_2_judul')) ?></h3>
                <p><?= e(konten_ambil('langkah_2_teks')) ?></p>
            </div>
            <div class="langkah-kartu langkah-3">
                <div class="langkah-ikon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-9"></path><path d="M12 13a5 5 0 0 0 5-5c0-4-5-6-5-6s-5 2-5 6a5 5 0 0 0 5 5z"></path><path d="M12 17a3 3 0 0 1 3-3"></path></svg>
                </div>
                <span class="langkah-nomor">Langkah 3</span>
                <h3><?= e(konten_ambil('langkah_3_judul')) ?></h3>
                <p><?= e(konten_ambil('langkah_3_teks')) ?></p>
            </div>
        </div>
    </section>

    <!-- 3. Produk Terbaru -->
    <section class="bagian" aria-labelledby="judul-produk-terbaru">
        <div class="bagian-kepala">
            <h2 id="judul-produk-terbaru">Produk Pilihan Terbaru</h2>
            <p>Dukung kemandirian pangan keluarga melalui produk segar dan olahan alami langsung dari pekarangan.</p>
        </div>

        <?php if (!empty($daftar_produk)): ?>
            <div class="grid-kartu-3">
                <?php foreach ($daftar_produk as $item): ?>
                    <article class="kartu">
                        <div class="foto-wadah">
                            <?php if (!empty($item['foto']) && file_exists(__DIR__ . '/uploads/' . $item['foto'])): ?>
                                <img src="<?= url('uploads/' . e($item['foto'])) ?>" alt="<?= e($item['nama']) ?>">
                            <?php else: ?>
                                <div class="foto-kosong">[Foto]</div>
                            <?php endif; ?>
                        </div>
                        <div class="kartu-badan">
                            <div class="baris-antara jarak-bawah-xs">
                                <span class="kartu-kategori"><?= e($item['kategori']) ?></span>
                            </div>
                            <h3 class="kartu-judul">
                                <a href="<?= url('produk-detail.php?slug=' . urlencode($item['slug'])) ?>">
                                    <?= e($item['nama']) ?>
                                </a>
                            </h3>
                            <p class="kartu-ringkasan"><?= e($item['deskripsi_singkat']) ?></p>
                            <div class="kartu-harga"><?= rupiah($item['harga']) ?></div>
                            <div class="kartu-kaki">
                                <a href="<?= url('produk-detail.php?slug=' . urlencode($item['slug'])) ?>" class="tombol tombol-sekunder tombol-blok">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="bagian-aksi">
                <a href="<?= url('produk.php') ?>" class="tombol tombol-sekunder">Lihat Semua Produk &rarr;</a>
            </div>
        <?php else: ?>
            <div class="kotak-kosong">
                <p>Belum ada produk yang ditampilkan saat ini.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- 4. Program Berjalan -->
    <section class="bagian" aria-labelledby="judul-program-berjalan">
        <div class="bagian-kepala">
            <h2 id="judul-program-berjalan">Program Sosial Berjalan</h2>
            <p>Salurkan kepedulian Anda untuk program pemberdayaan dan ketahanan komunitas.</p>
        </div>

        <?php if (!empty($daftar_program)): ?>
            <div class="grid-kartu-2">
                <?php foreach ($daftar_program as $prog): ?>
                    <?php $progres = progres_program((int)$prog['id']); ?>
                    <article class="kartu">
                        <div class="foto-wadah">
                            <?php if (!empty($prog['foto_sampul']) && file_exists(__DIR__ . '/uploads/' . $prog['foto_sampul'])): ?>
                                <img src="<?= url('uploads/' . e($prog['foto_sampul'])) ?>" alt="<?= e($prog['judul']) ?>">
                            <?php else: ?>
                                <div class="foto-kosong">[Foto]</div>
                            <?php endif; ?>
                        </div>
                        <div class="kartu-badan">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.375rem;">
                                <span class="kartu-kategori">Program Sosial</span>
                            </div>
                            <h3 class="kartu-judul">
                                <a href="<?= url('program-detail.php?slug=' . urlencode($prog['slug'])) ?>">
                                    <?= e($prog['judul']) ?>
                                </a>
                            </h3>
                            <p class="kartu-ringkasan"><?= e($prog['ringkasan']) ?></p>

                            <!-- Bilah Progres Donasi -->
                            <div class="progress-wadah">
                                <div class="progress-info">
                                    <span class="progress-info-terkumpul"><?= rupiah($progres['terkumpul']) ?> terkumpul</span>
                                    <span class="progress-info-target">Target: <?= rupiah($progres['target']) ?></span>
                                </div>
                                <div class="progress-track" role="progressbar" aria-valuenow="<?= (int)$progres['persen'] ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-fill" style="width: <?= (int)$progres['persen'] ?>%;"></div>
                                </div>
                                <div class="teks-xs teks-redup jarak-atas-xs">
                                    Tercapai <?= (int)$progres['persen'] ?>% &bull; <?= (int)$progres['jumlah_donatur'] ?> Donatur
                                </div>
                            </div>

                            <div class="kartu-kaki">
                                <a href="<?= url('donasi.php?program=' . urlencode($prog['slug'])) ?>" class="tombol tombol-utama tombol-blok">
                                    Donasi Sekarang
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="bagian-aksi">
                <a href="<?= url('program.php') ?>" class="tombol tombol-sekunder">Lihat Semua Program &rarr;</a>
            </div>
        <?php else: ?>
            <div class="kotak-kosong">
                <p>Belum ada program aktif saat ini.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- 5. Ajakan Akhir (CTA) -->
    <section class="bagian">
        <div class="blok-ajakan">
            <h2>Mari Bergabung dalam Gerakan Kebaikan</h2>
            <p>Dukungan Anda dalam bentuk pembelian produk maupun donasi sukarela langsung memberikan dampak nyata bagi keluarga dan lingkungan binaan kami.</p>
            <div class="grup-tombol">
                <a href="<?= url('produk.php') ?>" class="tombol tombol-utama">Mulai Belanja</a>
                <a href="<?= url('donasi.php') ?>" class="tombol tombol-utama">Berdonasi Sekarang</a>
            </div>
            <div>
                <a href="<?= url('transparansi.php') ?>" class="blok-ajakan-tautan">
                    Pelajari bagaimana dana dikelola dan disalurkan &rarr;
                </a>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
