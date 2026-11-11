import { getErrorMessage, getSupabase } from "./supabase-client.js";

const app = document.querySelector("#app");
const page = document.body.dataset.adminPage;
const esc = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({
  "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
})[char]);
const money = (value) => new Intl.NumberFormat("id-ID", {
  style: "currency", currency: "IDR", maximumFractionDigits: 0,
}).format(Number(value || 0));
const orderTransitions = {
  menunggu: ["menunggu", "dibayar", "batal"],
  dibayar: ["dibayar", "diproses", "batal"],
  diproses: ["diproses", "dikirim"],
  dikirim: ["dikirim", "selesai"],
  selesai: ["selesai"],
  batal: ["batal"],
};
const defs = {
  products: { table: "products", title: "Produk", form: "produk-form.html", item: "produk", columns: [["Nama", "name"], ["Slug", "slug"], ["Status", "status"], ["Harga", "price"], ["Stok", "stock"]] },
  programs: { table: "programs", title: "Program", form: "program-form.html", item: "program", columns: [["Judul", "title"], ["Slug", "slug"], ["Status", "status"], ["Target dana", "target_amount"]] },
  reports: { table: "reports", title: "Laporan", form: "laporan-form.html", item: "laporan", columns: [["Judul", "title"], ["Status", "status"], ["Dibuat", "created_at"]] },
  donations: { table: "donations", title: "Donasi", columns: [["Kode", "code"], ["Donatur", "donor_name"], ["Program", "program_id"], ["Nominal", "amount"], ["Status", "status"], ["Klaim bayar", "payment_claimed_at"]] },
  orders: { table: "orders", title: "Pesanan", columns: [["Kode", "code"], ["Pemesan", "customer_name"], ["Produk", "product_name"], ["Jumlah", "quantity"], ["Total", "total"], ["Status", "status"]] },
};

function message(text, isError = false) {
  return `<div class="${isError ? "kotak-merah" : "kotak-info"}" role="status">${esc(text)}</div>`;
}

function adminShell(content, user, active = "") {
  const links = [
    ["Dashboard", "index.html", "dashboard"], ["Produk", "produk.html", "products"],
    ["Program", "program.html", "programs"], ["Donasi", "donasi.html", "donations"],
    ["Pesanan", "pesanan.html", "orders"], ["Laporan", "laporan.html", "reports"],
    ["Konten", "konten.html", "content"], ["Pengaturan", "pengaturan.html", "settings"],
    ["Situs publik", "/index.html", ""],
  ];
  return `<div class="pi-admin"><aside class="pi-admin-nav"><a class="pi-brand" href="/admin/index.html"><img src="/assets/img/logo-pekarangan.jpeg" width="42" height="42" alt=""><span>Pekarangan Admin</span></a><nav>${links.map(([label, href, key]) => `<a class="${key === active ? "aktif" : ""}" href="${href}">${label}</a>`).join("")}<a href="/admin/tes-koneksi.html">Status koneksi</a><a href="/admin/logout.html">Keluar</a></nav><small>${esc(user.email || "")}</small></aside><main class="pi-admin-main"><div class="pi-admin-top"><strong>Panel Pengelola</strong><a href="/admin/logout.html">Keluar</a></div>${content}</main></div>`;
}

function loginView(error = "") {
  return `<section class="kartu-auth"><div class="kartu-auth-kepala"><img src="/assets/img/logo-pekarangan.jpeg" width="76" height="76" alt=""><h1>Masuk Admin</h1><p>Gunakan akun admin Supabase yang sudah disiapkan.</p></div>${error ? message(error, true) : ""}<form class="pi-form" id="login-form"><label>Email<input type="email" name="email" autocomplete="username" required></label><label>Kata sandi<input type="password" name="password" autocomplete="current-password" required></label><button class="tombol tombol-utama">Masuk</button><div class="pi-form-message" role="status"></div></form><p><a href="/index.html">Kembali ke situs</a></p></section>`;
}

async function getAdmin(client) {
  const { data: result, error } = await client.auth.getUser();
  if (error || !result.user) return null;
  const { data: admin, error: roleError } = await client.from("admin_users").select("id,email,display_name").eq("id", result.user.id).maybeSingle();
  if (roleError) throw roleError;
  return admin ? { ...result.user, ...admin } : null;
}

async function loadTable(client, table) {
  const result = await client.from(table).select("*").order("created_at", { ascending: false });
  if (result.error) throw result.error;
  return result.data || [];
}

function dashboard(data, user) {
  const paidDonations = data.donations.filter((row) => row.status === "berhasil").reduce((sum, row) => sum + Number(row.amount || 0), 0);
  const waitingDonations = data.donations.filter((row) => row.status === "menunggu").length;
  const waitingOrders = data.orders.filter((row) => row.status === "menunggu").length;
  return adminShell(`<section class="bagian"><h1>Dashboard</h1><p>Selamat datang, ${esc(user.display_name || user.email)}.</p><div class="grid-kartu-3"><article class="kartu kartu-badan"><h2>Produk</h2><strong>${data.products.length}</strong></article><article class="kartu kartu-badan"><h2>Program aktif</h2><strong>${data.programs.filter((row) => row.status === "aktif").length}</strong></article><article class="kartu kartu-badan"><h2>Donasi berhasil</h2><strong>${money(paidDonations)}</strong></article><article class="kartu kartu-badan"><h2>Donasi menunggu</h2><strong>${waitingDonations}</strong></article><article class="kartu kartu-badan"><h2>Pesanan menunggu</h2><strong>${waitingOrders}</strong></article></div></section>`, user, "dashboard");
}

function tableView(kind, rows, user) {
  const definition = defs[kind];
  const addButton = definition.form ? `<a class="tombol tombol-utama" href="/admin/${definition.form}">Tambah ${definition.item}</a>` : "";
  const body = rows.map((row) => `<tr data-record-id="${esc(row.id)}"><td>${esc(row.code || row.name || row.title || row.id)}</td>${definition.columns.slice(1).map(([, key]) => `<td>${key.includes("amount") || key === "price" || key === "total" ? money(row[key]) : esc(row[key] || "-")}</td>`).join("")}<td>${kind === "orders" ? `<select data-order-status aria-label="Status pesanan">${(orderTransitions[row.status] || [row.status]).map((status) => `<option ${row.status === status ? "selected" : ""}>${status}</option>`).join("")}</select><button class="tombol-aksi tombol-aksi-ubah" data-save-order>Perbarui</button>` : kind === "donations" ? `<select data-donation-status aria-label="Status donasi">${["menunggu", "berhasil", "gagal"].map((status) => `<option ${row.status === status ? "selected" : ""}>${status}</option>`).join("")}</select><button class="tombol-aksi tombol-aksi-ubah" data-save-donation>Perbarui</button>` : `<a class="tombol-aksi tombol-aksi-ubah" href="/admin/${definition.form}?id=${encodeURIComponent(row.id)}">Ubah</a><button class="tombol-aksi tombol-aksi-hapus" data-delete>Hapus</button>`}</td></tr>`).join("");
  return adminShell(`<section class="bagian"><div class="pi-admin-heading"><div><h1>Kelola ${definition.title}</h1><p>${rows.length} data</p></div>${addButton}</div><div class="pi-table-wrap"><table class="pi-table"><thead><tr>${definition.columns.map(([label]) => `<th>${label}</th>`).join("")}<th>Aksi</th></tr></thead><tbody>${body || `<tr><td colspan="${definition.columns.length + 1}">Belum ada data.</td></tr>`}</tbody></table></div><div class="pi-admin-message" role="status"></div></section>`, user, kind);
}

function jsonForm(kind, row, user) {
  const definition = defs[kind];
  const existing = row?.payload || {};
  const example = kind === "products"
    ? { nama: "", slug: "", kategori: "", harga: 0, stok: 0, deskripsi_singkat: "", deskripsi: "", penjual: "", foto: "", status: "draf" }
    : kind === "programs"
      ? { judul: "", slug: "", ringkasan: "", cerita: "", target_dana: 0, tenggat: "", lokasi: "", penerima_manfaat: "", foto_sampul: "", status: "aktif" }
      : { judul: "", periode: "", status: "draf", ringkasan: "", berkas: "" };
  const payload = JSON.stringify(Object.keys(existing).length ? existing : example, null, 2);
  return adminShell(`<section class="bagian"><h1>${row ? "Ubah" : "Tambah"} ${definition.item}</h1><p>Isi record sebagai JSON. File gambar dapat diunggah di bawah; salin URL publiknya ke field foto di JSON.</p><form id="record-form" class="pi-form" data-kind="${kind}" data-id="${esc(row?.id || "")}"><label>Data ${definition.item} (JSON)<textarea name="payload" rows="20" spellcheck="false" required>${esc(payload)}</textarea></label><label>Unggah gambar atau PDF<input name="asset" type="file" accept="image/jpeg,image/png,image/webp,application/pdf"></label><button class="tombol tombol-utama">Simpan</button><div class="pi-form-message" role="status"></div></form><p><a href="/admin/${definition.title === "Produk" ? "produk" : definition.title === "Program" ? "program" : "laporan"}.html">Kembali ke daftar</a></p></section>`, user, kind);
}

async function renderSettings(client, user) {
  const rows = await loadTable(client, "site_settings");
  return adminShell(`<section class="bagian"><h1>Pengaturan & konten situs</h1><p>Nilai disimpan sebagai JSON. Teks diinput sebagai string JSON, contoh: <code>"Nama situs"</code>. Unggah logo/foto melalui field file, lalu simpan ke kunci publik seperti <code>hero_foto</code> atau <code>logo_mitra</code>.</p><form class="pi-form" id="setting-form"><label>Kunci<input name="key" required placeholder="nama_situs"></label><label>Nilai JSON<textarea name="value" rows="5"></textarea></label><label>Unggah gambar<input name="asset" type="file" accept="image/jpeg,image/png,image/webp"></label><button class="tombol tombol-utama">Simpan pengaturan</button><div class="pi-form-message" role="status"></div></form><h2>Nilai tersimpan</h2><div class="pi-table-wrap"><table class="pi-table"><thead><tr><th>Kunci</th><th>Nilai</th><th>Aksi</th></tr></thead><tbody>${rows.map((row) => `<tr><td>${esc(row.key)}</td><td><code>${esc(JSON.stringify(row.value))}</code></td><td><button class="tombol-aksi tombol-aksi-ubah" data-setting="${esc(row.key)}" data-value="${esc(JSON.stringify(row.value))}">Muat ke formulir</button></td></tr>`).join("")}</tbody></table></div></section>`, user, "settings");
}

async function uploadAsset(client, file, kind) {
  const extensions = { "image/jpeg": "jpg", "image/png": "png", "image/webp": "webp", "application/pdf": "pdf" };
  if (!extensions[file.type] || file.size > 5 * 1024 * 1024) {
    throw new Error("Pilih gambar JPEG/PNG/WebP atau PDF berukuran maksimal 5 MB.");
  }
  const path = `${kind}/${crypto.randomUUID()}.${extensions[file.type]}`;
  const { error } = await client.storage.from("site-assets").upload(path, file, {
    upsert: false,
    contentType: file.type,
  });
  if (error) throw error;
  return client.storage.from("site-assets").getPublicUrl(path).data.publicUrl;
}

async function render(client, user) {
  if (page === "logout") {
    await client.auth.signOut();
    location.replace("/admin/login.html");
    return;
  }
  if (page === "dashboard") {
    const [products, programs, donations, orders] = await Promise.all(["products", "programs", "donations", "orders"].map((table) => loadTable(client, table)));
    app.innerHTML = dashboard({ products, programs, donations, orders }, user);
    return;
  }
  const formKinds = { "product-form": "products", "program-form": "programs", "report-form": "reports" };
  if (formKinds[page]) {
    const kind = formKinds[page];
    const id = new URLSearchParams(location.search).get("id");
    let row = null;
    if (id) {
      const { data, error } = await client.from(defs[kind].table).select("*").eq("id", id).single();
      if (error) throw error;
      row = data;
    }
    app.innerHTML = jsonForm(kind, row, user);
    return;
  }
  if (page === "settings" || page === "content") {
    app.innerHTML = await renderSettings(client, user);
    return;
  }
  if (page === "setup") {
    app.innerHTML = `<section class="kontainer bagian"><h1>Penyiapan admin Supabase</h1><div class="kotak-info"><p>Untuk keamanan, pendaftaran admin publik dinonaktifkan. Buat pengguna melalui Supabase Dashboard → Authentication → Users. Setelah itu, jalankan SQL berikut dengan UUID dan email pengguna tersebut:</p><pre>insert into public.admin_users (id, email, display_name)\nvalues ('UUID_USER_AUTH', 'admin@example.com', 'Administrator');</pre></div><a href="/admin/login.html">Ke halaman login</a></section>`;
    return;
  }
  if (page === "connection") {
    const { error } = await client.from("public_settings").select("key").limit(1);
    app.innerHTML = adminShell(`<section class="bagian"><h1>Status koneksi</h1>${error ? message(error.message, true) : message("Supabase terhubung dan RLS mengizinkan pembacaan pengaturan publik.")}</section>`, user);
    return;
  }
  const kindByPage = { products: "products", programs: "programs", donations: "donations", orders: "orders", reports: "reports" };
  const kind = kindByPage[page];
  if (kind) {
    app.innerHTML = tableView(kind, await loadTable(client, defs[kind].table), user);
    return;
  }
  app.innerHTML = adminShell(`<section class="bagian"><h1>Halaman tidak ditemukan</h1></section>`, user);
}

function showError(error) {
  app.innerHTML = `<section class="kontainer bagian"><h1>Panel belum tersedia</h1><div class="kotak-merah" role="alert">${esc(getErrorMessage(error))}</div><p>Periksa konfigurasi Supabase, skema database, dan kebijakan akses.</p><a href="/admin/login.html">Login admin</a></section>`;
}

async function postJson(path, payload, token) {
  const response = await fetch(path, {
    method: "POST",
    headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
    body: JSON.stringify(payload),
  });
  const result = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(result.error || "Permintaan gagal.");
  return result;
}

async function wire(client) {
  document.querySelector("#login-form")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const values = Object.fromEntries(new FormData(form));
    const { error } = await client.auth.signInWithPassword({ email: values.email, password: values.password });
    if (error) {
      const output = form.querySelector(".pi-form-message");
      output.textContent = error.message;
      output.classList.add("is-error");
      return;
    }
    const user = await getAdmin(client);
    if (!user) {
      await client.auth.signOut();
      form.querySelector(".pi-form-message").textContent = "Akun ini belum diberi peran admin. Hubungi pengelola Supabase.";
      return;
    }
    location.replace("/admin/index.html");
  });
  document.querySelector("#record-form")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const output = form.querySelector(".pi-form-message");
    let payload;
    try {
      payload = JSON.parse(new FormData(form).get("payload"));
      if (!payload || Array.isArray(payload) || typeof payload !== "object") throw new Error("Data harus berupa object JSON.");
    } catch (error) {
      output.textContent = error.message;
      output.classList.add("is-error");
      return;
    }
    try {
      const file = form.elements.asset.files[0];
      if (file) {
        const url = await uploadAsset(client, file, form.dataset.kind);
        output.textContent = `Unggahan berhasil: ${url}. Salin URL ini ke field foto pada JSON lalu simpan.`;
        output.classList.add("is-success");
        return;
      }
      const kind = form.dataset.kind;
      const common = { status: String(payload.status || "draf"), payload, updated_at: new Date().toISOString() };
      const row = kind === "products"
            ? { ...common, slug: String(payload.slug || ""), name: String(payload.nama || ""), price: Number(payload.harga || 0), stock: Number(payload.stok || 0) }
        : kind === "programs"
              ? { ...common, slug: String(payload.slug || ""), title: String(payload.judul || ""), target_amount: Number(payload.target_dana || 0) }
          : { ...common, title: String(payload.judul || "") };
      if (!row.slug && kind !== "reports") throw new Error("Slug wajib diisi.");
      if (!row.title && kind !== "products") throw new Error("Judul wajib diisi.");
      if (!row.name && kind === "products") throw new Error("Nama produk wajib diisi.");
      const table = defs[kind].table;
      let result;
      if (form.dataset.id) {
        result = await client.from(table).update(row).eq("id", form.dataset.id);
      } else {
        result = await client.from(table).insert({ id: crypto.randomUUID(), ...row });
      }
      if (result.error) throw result.error;
      location.href = `/admin/${kind === "products" ? "produk" : kind === "programs" ? "program" : "laporan"}.html`;
    } catch (error) {
      output.textContent = getErrorMessage(error);
      output.classList.add("is-error");
    }
  });
  document.querySelector("#setting-form")?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const output = form.querySelector(".pi-form-message");
    try {
      const values = Object.fromEntries(new FormData(form));
      const file = form.elements.asset.files[0];
      const result = file ? await uploadAsset(client, file, "site") : JSON.parse(values.value);
      const { error } = await client.from("site_settings").upsert({ key: values.key.trim(), value: result, updated_at: new Date().toISOString() });
      if (error) throw error;
      location.reload();
    } catch (error) {
      output.textContent = getErrorMessage(error);
      output.classList.add("is-error");
    }
  });
  document.querySelectorAll("[data-setting]").forEach((button) => button.addEventListener("click", () => {
    document.querySelector('#setting-form [name="key"]').value = button.dataset.setting;
    document.querySelector('#setting-form [name="value"]').value = button.dataset.value;
  }));
  document.querySelectorAll("[data-delete]").forEach((button) => button.addEventListener("click", async () => {
    if (!confirm("Hapus data ini? Tindakan ini tidak dapat dibatalkan.")) return;
    const row = button.closest("[data-record-id]");
    const kind = page;
    const { error } = await client.from(defs[kind].table).delete().eq("id", row.dataset.recordId);
    if (error) {
      document.querySelector(".pi-admin-message").textContent = error.message;
      return;
    }
    row.remove();
  }));
  document.querySelectorAll("[data-save-donation]").forEach((button) => button.addEventListener("click", async () => {
    const row = button.closest("[data-record-id]");
    const status = row.querySelector("[data-donation-status]").value;
    const { error } = await client.from("donations").update({ status, updated_at: new Date().toISOString() }).eq("id", row.dataset.recordId);
    if (error) document.querySelector(".pi-admin-message").textContent = error.message;
  }));
  document.querySelectorAll("[data-save-order]").forEach((button) => button.addEventListener("click", async () => {
    const row = button.closest("[data-record-id]");
    const { data: result } = await client.auth.getSession();
    try {
      await postJson("/api/order-status", { orderId: row.dataset.recordId, status: row.querySelector("[data-order-status]").value }, result.session.access_token);
      document.querySelector(".pi-admin-message").textContent = "Status pesanan diperbarui.";
    } catch (error) {
      document.querySelector(".pi-admin-message").textContent = getErrorMessage(error);
    }
  }));
}

async function main() {
  try {
    if (page === "setup") {
      app.innerHTML = `<section class="kontainer bagian"><h1>Penyiapan admin Supabase</h1><div class="kotak-info"><p>Untuk keamanan, pendaftaran admin publik dinonaktifkan. Buat pengguna melalui Supabase Dashboard → Authentication → Users. Setelah itu, jalankan SQL berikut dengan UUID dan email pengguna tersebut:</p><pre>insert into public.admin_users (id, email, display_name)\nvalues ('UUID_USER_AUTH', 'admin@example.com', 'Administrator');</pre></div><a href="/admin/login.html">Ke halaman login</a></section>`;
      return;
    }
    const client = await getSupabase();
    if (page === "login") {
      app.innerHTML = loginView();
      await wire(client);
      return;
    }
    const user = await getAdmin(client);
    if (!user) {
      location.replace(`/admin/login.html?next=${encodeURIComponent(location.pathname + location.search)}`);
      return;
    }
    await render(client, user);
    await wire(client);
  } catch (error) {
    showError(error);
  }
}

main();
