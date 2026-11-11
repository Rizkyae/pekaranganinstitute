import { getErrorMessage, getSupabase } from "./supabase-client.js";

const app = document.querySelector("#app");
const page = document.body.dataset.page;
const query = new URLSearchParams(location.search);
const slug = query.get("slug") || query.get("program") || "";
const esc = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({
  "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
})[char]);
const money = (value) => new Intl.NumberFormat("id-ID", {
  style: "currency", currency: "IDR", maximumFractionDigits: 0,
}).format(Number(value || 0));
const record = (row) => ({ ...(row?.payload || {}), ...row, ...(row?.payload || {}) });
const imageUrl = (value) => {
  const image = String(value || "").trim();
  if (!image) return "/assets/img/logo-pekarangan.jpeg";
  if (/^(https?:)?\/\//i.test(image) || image.startsWith("/")) return image;
  return `/uploads/${encodeURIComponent(image.split("/").pop())}`;
};
const picture = (image, alt) => `<div class="foto-wadah"><img src="${esc(imageUrl(image))}" alt="${esc(alt)}" loading="lazy" onerror="this.src='/assets/img/logo-pekarangan.jpeg'"></div>`;
const linkButton = (href, label, secondary = false) => `<a class="tombol ${secondary ? "tombol-sekunder" : "tombol-utama"}" href="${href}">${label}</a>`;
const heading = (title, text = "") => `<div class="bagian-kepala"><h1>${esc(title)}</h1>${text ? `<p>${esc(text)}</p>` : ""}</div>`;
const appShell = (content, settings = {}) => `<a class="lompat-konten" href="#konten-utama">Lewati ke konten utama</a>
  <header class="pi-header"><div class="kontainer pi-header-inner"><a class="pi-brand" href="/index.html"><img src="/assets/img/logo-pekarangan.jpeg" width="44" height="44" alt=""><span>${esc(settings.nama_situs || "Pekarangan Institute")}</span></a>
  <nav aria-label="Navigasi utama"><a href="/index.html">Beranda</a><a href="/produk.html">Produk</a><a href="/program.html">Program</a><a href="/transparansi.html">Transparansi</a><a href="/tentang.html">Tentang</a><a href="/kontak.html">Kontak</a>${linkButton("/donasi.html", "Donasi")}</nav></div></header>
  <main id="konten-utama">${content}</main><footer class="pi-footer"><div class="kontainer pi-footer-inner"><img src="/assets/img/logo-pekarangan.jpeg" width="40" height="40" alt=""><p>${esc(settings.footer_teks || "Pemberdayaan pekarangan produktif dan penguatan ekonomi komunitas.")}</p><small>© ${new Date().getFullYear()} ${esc(settings.nama_situs || "Pekarangan Institute")}</small><a href="/admin/login.html">Admin</a></div></footer>`;
const alert = (text) => `<div class="kotak-peringatan" role="status">${esc(text)}</div>`;
const showError = (error) => {
  app.innerHTML = appShell(`<section class="kontainer bagian">${heading("Koneksi belum tersedia")}${alert(getErrorMessage(error))}<p>Atur <code>SUPABASE_URL</code> dan <code>SUPABASE_ANON_KEY</code> pada Environment Variables Vercel. Kunci service-role hanya boleh disimpan sebagai environment variable server.</p></section>`);
};

async function loadData(client) {
  const results = await Promise.all([
    client.from("public_settings").select("key,value"),
    client.from("public_products").select("*").order("created_at", { ascending: false }),
    client.from("public_programs").select("*").order("created_at", { ascending: false }),
    client.from("public_reports").select("*").order("created_at", { ascending: false }),
  ]);
  for (const result of results) if (result.error) throw result.error;
  return {
    settings: Object.fromEntries((results[0].data || []).map(({ key, value }) => [key, value])),
    products: (results[1].data || []).map(record),
    programs: (results[2].data || []).map(record),
    reports: (results[3].data || []).map(record),
  };
}

function card(item, type) {
  const product = type === "product";
  const title = product ? item.nama || item.name : item.judul || item.title;
  const href = `/${product ? "produk-detail" : "program-detail"}.html?slug=${encodeURIComponent(item.slug || "")}`;
  const summary = product ? item.deskripsi_singkat || item.deskripsi : item.ringkasan;
  return `<article class="kartu">${picture(product ? item.foto : item.foto_sampul, title)}<div class="kartu-badan"><span class="kartu-kategori">${esc(item.kategori || (product ? "Produk Pekarangan" : "Program Sosial"))}</span><h2 class="kartu-judul"><a href="${href}">${esc(title)}</a></h2><p>${esc(summary || "")}</p><p><strong>${product ? money(item.harga ?? item.price) : `Target ${money(item.target_dana ?? item.target_amount)}`}</strong></p><p>${linkButton(href, "Lihat Detail", true)}</p></div></article>`;
}

function renderHome(data) {
  const { settings: s, products, programs } = data;
  return `<section class="kontainer bagian pi-hero"><div><span class="hero-label">BERBELANJA · BERBAGI · BERDAMPAK</span><h1>${esc(s.hero_judul || "Pemberdayaan Pekarangan untuk Kesejahteraan Komunitas")}</h1><p>${esc(s.hero_teks || "Inisiatif kewirausahaan sosial yang menghubungkan produk pekarangan dengan pemberdayaan komunitas.")}</p>${linkButton("/produk.html", "Belanja Sekarang")} ${linkButton("/program.html", "Lihat Program", true)}</div>${picture(s.hero_foto, "Pekarangan Institute")}</section>
  <section class="kontainer bagian"><div class="bagian-kepala"><h2>Bagaimana Kami Bekerja</h2><p>Setiap transaksi memberdayakan pekebun lokal dan mendukung program sosial.</p></div><div class="grid-kartu-3">${[1, 2, 3].map((n) => `<article class="kartu pi-step"><h3>${esc(s[`langkah_${n}_judul`] || ["Berbelanja", "Berbagi", "Berdampak"][n - 1])}</h3><p>${esc(s[`langkah_${n}_teks`] || "")}</p></article>`).join("")}</div></section>
  <section class="kontainer bagian"><div class="bagian-kepala"><h2>Produk Pilihan Terbaru</h2></div><div class="grid-kartu-3">${products.slice(0, 3).map((item) => card(item, "product")).join("") || "<p>Belum ada produk yang ditampilkan.</p>"}</div><p>${linkButton("/produk.html", "Semua Produk", true)}</p></section>
  <section class="kontainer bagian"><div class="bagian-kepala"><h2>Program Sosial Berjalan</h2></div><div class="grid-kartu-3">${programs.slice(0, 3).map((item) => card(item, "program")).join("") || "<p>Belum ada program aktif.</p>"}</div><p>${linkButton("/program.html", "Semua Program", true)}</p></section>`;
}

function renderList(data, type) {
  const product = type === "product";
  let items = product ? data.products : data.programs;
  const search = (query.get("cari") || "").toLowerCase();
  if (product && search) items = items.filter((item) => `${item.nama || item.name} ${item.kategori || ""}`.toLowerCase().includes(search));
  const title = product ? "Katalog Produk" : "Program Sosial";
  const filter = product ? `<form class="pi-filters" action="/produk.html"><label>Cari produk<input name="cari" value="${esc(query.get("cari") || "")}"></label><button class="tombol tombol-utama">Cari</button></form>` : "";
  return `<section class="kontainer bagian">${heading(title, product ? "Dukung produk hasil pekarangan dan mitra lokal." : "Dukung inisiatif sosial yang transparan dan berdampak.")}${filter}<div class="grid-kartu-3">${items.map((item) => card(item, type)).join("") || "<p>Belum ada data untuk ditampilkan.</p>"}</div></section>`;
}

function renderProductDetail(data) {
  const item = data.products.find((value) => value.slug === slug);
  if (!item) return `<section class="kontainer bagian">${heading("Produk tidak ditemukan")}<p>Produk mungkin sudah tidak tersedia.</p>${linkButton("/produk.html", "Kembali ke Produk", true)}</section>`;
  return `<section class="kontainer bagian"><p><a href="/produk.html">← Kembali ke produk</a></p><div class="pi-detail">${picture(item.foto, item.nama || item.name)}<div>${heading(item.nama || item.name, item.kategori || "")}<p>${esc(item.deskripsi || item.deskripsi_singkat || "")}</p><p><strong>${money(item.harga ?? item.price)}</strong> · Stok ${Number(item.stok ?? item.stock ?? 0)}</p><p>Penjual: ${esc(item.penjual || "Mitra Pekarangan Institute")}</p>
    <form class="pi-form" id="order-form" data-product-id="${esc(item.id)}"><h2>Pesan produk</h2><label>Nama lengkap<input name="customerName" maxlength="150" required></label><label>WhatsApp<input name="whatsapp" type="tel" maxlength="24" required></label><label>Alamat pengiriman<textarea name="address" maxlength="1000" required></textarea></label><label>Jumlah<input name="quantity" type="number" min="1" max="100" value="1" required></label><label>Catatan (opsional)<textarea name="note" maxlength="500"></textarea></label><label class="pi-honeypot">Website<input name="website" tabindex="-1" autocomplete="off"></label><button class="tombol tombol-utama" ${Number(item.stok ?? item.stock ?? 0) <= 0 ? "disabled" : ""}>Kirim Pesanan</button><div class="pi-form-message" role="status"></div></form></div></div></section>`;
}

function renderProgramDetail(data) {
  const item = data.programs.find((value) => value.slug === slug);
  if (!item) return `<section class="kontainer bagian">${heading("Program tidak ditemukan")}<p>Program mungkin sudah tidak aktif.</p>${linkButton("/program.html", "Kembali ke Program", true)}</section>`;
  return `<section class="kontainer bagian"><p><a href="/program.html">← Kembali ke program</a></p><div class="pi-detail">${picture(item.foto_sampul, item.judul || item.title)}<div>${heading(item.judul || item.title, item.ringkasan || "")}<div class="pi-prose">${esc(item.cerita || item.ringkasan || "").replace(/\n/g, "<br>")}</div><p><strong>Target:</strong> ${money(item.target_dana ?? item.target_amount)}</p><p><strong>Lokasi:</strong> ${esc(item.lokasi || "-")}</p><p><strong>Penerima manfaat:</strong> ${esc(item.penerima_manfaat || "-")}</p>${linkButton(`/donasi.html?program=${encodeURIComponent(item.slug)}`, "Donasi untuk Program")}</div></div></section>`;
}

function renderDonation(data) {
  const selected = data.programs.find((item) => item.slug === slug);
  const minimum = Math.max(1000, Number(data.settings.donasi_minimum || 10000));
  return `<section class="kontainer bagian">${heading("Berdonasi untuk Program", "Donasi diverifikasi manual sebelum dinyatakan berhasil.")}<form class="pi-form pi-form-narrow" id="donation-form"><label>Program sosial<select name="programId" required><option value="">Pilih program</option>${data.programs.map((item) => `<option value="${esc(item.id)}" ${selected?.id === item.id ? "selected" : ""}>${esc(item.judul || item.title)}</option>`).join("")}</select></label>
    <div class="pi-amounts">${[25000, 50000, 100000, 250000].map((amount) => `<button type="button" class="tombol tombol-sekunder" data-amount="${amount}">${money(amount)}</button>`).join("")}</div>
    <label>Nominal (minimum ${money(minimum)})<input name="amount" type="number" min="${minimum}" max="1000000000" step="1000" required></label><label>Nama donatur<input name="donorName" maxlength="150" required></label><label>WhatsApp<input name="whatsapp" type="tel" maxlength="24" required></label><label>Pesan atau doa (opsional)<textarea name="message" maxlength="500"></textarea></label><label class="pi-checkbox"><input type="checkbox" name="anonymous"> Tampilkan nama saya sebagai anonim</label><label class="pi-honeypot">Website<input name="website" tabindex="-1" autocomplete="off"></label><button class="tombol tombol-utama">Lanjut Pembayaran QRIS</button><div class="pi-form-message" role="status"></div></form></section>`;
}

function renderPayment() {
  const payment = JSON.parse(sessionStorage.getItem("pi-donation") || "null");
  const code = query.get("kode") || payment?.code || "";
  if (!code) return `<section class="kontainer bagian">${heading("Data pembayaran tidak tersedia")}<p>Silakan mulai donasi kembali.</p>${linkButton("/donasi.html", "Kembali ke donasi")}</section>`;
  return `<section class="kontainer bagian pi-payment">${heading("Pembayaran Donasi QRIS", "Pindai QRIS dan transfer tepat sesuai nominal di bawah.")}<div class="kartu kartu-form-wadah"><img class="pi-qris" src="/assets/img/qris-pekarangan.jpeg" alt="Kode QRIS Pekarangan Institute"><h2>Atas nama: Pekarangan Institute</h2>${payment ? `<p>Donatur: <strong>${esc(payment.donorName)}</strong></p><p>Program: ${esc(payment.programTitle)}</p><p>Nominal: <strong>${money(payment.amount)}</strong></p>` : ""}<p>Kode donasi: <strong>${esc(code)}</strong></p><p>QRIS ini statis; masukkan nominal secara manual. Donasi tetap menunggu sampai admin memverifikasi pembayaran.</p><button type="button" class="tombol tombol-utama" id="claim-donation" data-code="${esc(code)}">Saya Sudah Membayar via QRIS</button><div class="pi-form-message" role="status"></div></div></section>`;
}

async function renderTransparency(client, data) {
  const programs = await Promise.all(data.programs.map(async (program) => {
    const { data: result, error } = await client.rpc("public_program_fundraising", { p_program_id: program.id });
    if (error) throw error;
    return { program, value: Array.isArray(result) ? result[0] : result };
  }));
  const total = programs.reduce((sum, item) => sum + Number(item.value?.total_amount || 0), 0);
  return `<section class="kontainer bagian">${heading("Transparansi Dana", "Ringkasan donasi terverifikasi dan laporan yang dipublikasikan.")}<div class="kartu kartu-form-wadah"><h2>Total donasi terverifikasi</h2><p class="kartu-harga">${money(total)}</p></div><h2>Donasi per program</h2><div class="grid-kartu-3">${programs.map(({ program, value }) => `<article class="kartu kartu-badan"><h3>${esc(program.judul || program.title)}</h3><p>${money(value?.total_amount || 0)} terkumpul</p><p>${Number(value?.donor_count || 0)} donatur</p></article>`).join("")}</div><h2>Laporan berkala</h2><div class="grid-kartu-3">${data.reports.map((report) => `<article class="kartu kartu-badan"><h3>${esc(report.judul || report.title)}</h3><p>${esc(report.ringkasan || "")}</p>${report.berkas ? `<a href="${esc(imageUrl(report.berkas))}">Lihat laporan</a>` : ""}</article>`).join("") || "<p>Belum ada laporan publik.</p>"}</div></section>`;
}

function renderAbout(data) {
  const s = data.settings;
  return `<section class="kontainer bagian">${heading("Tentang Pekarangan Institute")}<div class="pi-detail">${picture(s.tentang_foto, "Komunitas Pekarangan Institute")}<div class="pi-prose">${esc(s.tentang_teks || "Pekarangan Institute adalah inisiatif kewirausahaan sosial di bidang pemberdayaan pekarangan produktif.").replace(/\n/g, "<br>")}<div class="grid-kartu-3">${[1, 2, 3].map((n) => `<article class="kartu"><h2>${esc(s[`pilar_${n}_judul`] || "")}</h2><p>${esc(s[`pilar_${n}_teks`] || "")}</p></article>`).join("")}</div></div></div></section>`;
}

function renderContact(data) {
  const s = data.settings;
  const phone = String(s.whatsapp || "").replace(/\D/g, "");
  return `<section class="kontainer bagian">${heading("Hubungi Kami")}<div class="kartu kartu-form-wadah"><p>Email: <a href="mailto:${esc(s.email || "")}">${esc(s.email || "Belum diatur")}</a></p><p>WhatsApp: ${esc(s.whatsapp || "Belum diatur")}</p><p>Alamat: ${esc(s.alamat || "Belum diatur")}</p>${phone ? linkButton(`https://wa.me/${phone}`, "Hubungi via WhatsApp") : ""}</div></section>`;
}

async function postJson(path, payload) {
  const response = await fetch(path, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify(payload) });
  const result = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(result.error || "Permintaan gagal.");
  return result;
}

function setMessage(container, text, isError = false) {
  const output = container.querySelector(".pi-form-message");
  if (!output) return;
  output.textContent = text;
  output.classList.toggle("is-error", isError);
  output.classList.toggle("is-success", !isError);
}

async function wireForms(data) {
  document.querySelectorAll("[data-amount]").forEach((button) => button.addEventListener("click", () => {
    const input = document.querySelector('#donation-form [name="amount"]');
    if (input) input.value = button.dataset.amount;
  }));
  document.querySelector("#donation-form")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form.reportValidity()) return;
    const values = Object.fromEntries(new FormData(form));
    const submit = form.querySelector("button:not([type]),button[type=submit]");
    submit.disabled = true;
    try {
      const result = await postJson("/api/donations", { ...values, amount: Number(values.amount), anonymous: form.elements.anonymous.checked });
      const program = data.programs.find((item) => item.id === values.programId);
      sessionStorage.setItem("pi-donation", JSON.stringify({ code: result.code, donorName: values.donorName, amount: Number(values.amount), programTitle: program?.judul || program?.title || "" }));
      location.href = `/donasi-sukses.html?kode=${encodeURIComponent(result.code)}`;
    } catch (error) {
      setMessage(form, getErrorMessage(error), true);
      submit.disabled = false;
    }
  });
  document.querySelector("#order-form")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form.reportValidity()) return;
    const values = Object.fromEntries(new FormData(form));
    if (values.website) return;
    const submit = form.querySelector("button:not([type]),button[type=submit]");
    submit.disabled = true;
    try {
      const result = await postJson("/api/orders", { ...values, productId: form.dataset.productId, quantity: Number(values.quantity) });
      sessionStorage.setItem("pi-order", JSON.stringify({ code: result.code }));
      location.href = `/pesanan-sukses.html?kode=${encodeURIComponent(result.code)}`;
    } catch (error) {
      setMessage(form, getErrorMessage(error), true);
      submit.disabled = false;
    }
  });
  document.querySelector("#claim-donation")?.addEventListener("click", async (event) => {
    const button = event.currentTarget;
    const box = button.closest(".kartu");
    try {
      button.disabled = true;
      await postJson("/api/claim-donation", { code: button.dataset.code });
      setMessage(box, "Klaim pembayaran dicatat. Status tetap menunggu sampai admin memverifikasi dana masuk.");
    } catch (error) {
      setMessage(box, getErrorMessage(error), true);
      button.disabled = false;
    }
  });
}

async function main() {
  try {
    const client = await getSupabase();
    const data = await loadData(client);
    let content;
    switch (page) {
      case "home": content = renderHome(data); break;
      case "products": content = renderList(data, "product"); break;
      case "product-detail": content = renderProductDetail(data); break;
      case "programs": content = renderList(data, "program"); break;
      case "program-detail": content = renderProgramDetail(data); break;
      case "donate": content = renderDonation(data); break;
      case "donation-payment": content = renderPayment(); break;
      case "order-success": {
        const order = JSON.parse(sessionStorage.getItem("pi-order") || "null");
        const code = query.get("kode") || order?.code || "";
        content = `<section class="kontainer bagian">${heading("Pesanan diterima")}<div class="kartu kartu-form-wadah"><p>Pesanan dicatat dengan status menunggu konfirmasi admin.</p>${code ? `<p>Kode pesanan: <strong>${esc(code)}</strong></p>` : ""}<p>Simpan kode pesanan untuk komunikasi dengan pengelola.</p>${linkButton("/produk.html", "Kembali ke katalog")}</div></section>`;
        break;
      }
      case "about": content = renderAbout(data); break;
      case "contact": content = renderContact(data); break;
      case "transparency": content = await renderTransparency(client, data); break;
      default: content = `<section class="kontainer bagian">${heading("Halaman tidak ditemukan")}</section>`;
    }
    app.innerHTML = appShell(content, data.settings);
    await wireForms(data);
  } catch (error) {
    showError(error);
  }
}

main();
