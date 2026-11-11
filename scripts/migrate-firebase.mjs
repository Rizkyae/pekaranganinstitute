import { createSign } from "node:crypto";
import { readdir, readFile } from "node:fs/promises";
import { basename, dirname, extname, join } from "node:path";
import { fileURLToPath } from "node:url";

const sourceUrl = process.env.FIREBASE_DATABASE_URL?.replace(/\/+$/, "");
const serviceAccountPath = process.env.FIREBASE_SERVICE_ACCOUNT_PATH;
const targetUrl = process.env.SUPABASE_URL?.replace(/\/+$/, "");
const targetKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
const confirmed = process.argv.includes("--confirm-import");
const uploadDirectory = join(dirname(fileURLToPath(import.meta.url)), "..", "uploads");
const mediaTypes = new Map([
  [".jpg", "image/jpeg"],
  [".jpeg", "image/jpeg"],
  [".png", "image/png"],
  [".webp", "image/webp"],
  [".pdf", "application/pdf"],
]);
const mediaFields = new Set(["foto", "foto_sampul", "berkas", "hero_foto", "tentang_foto", "logo_mitra", "logo_instansi"]);

const collections = [
  ["produk", "products", (id, value) => ({
    id,
    slug: requiredText(value.slug, `produk/${id}/slug`),
    name: requiredText(value.nama, `produk/${id}/nama`),
    status: oneOf(value.status, ["draf", "tampil", "arsip"], "draf", `produk/${id}/status`),
    price: integer(value.harga),
    stock: integer(value.stok),
    reserved_stock: 0,
    payload: { ...value, id },
    ...timestamps(value),
  })],
  ["program", "programs", (id, value) => ({
    id,
    slug: requiredText(value.slug, `program/${id}/slug`),
    title: requiredText(value.judul, `program/${id}/judul`),
    status: oneOf(value.status, ["aktif", "selesai", "arsip"], "aktif", `program/${id}/status`),
    target_amount: integer(value.target_dana),
    payload: { ...value, id },
    ...timestamps(value),
  })],
  ["laporan", "reports", (id, value) => ({
    id,
    title: requiredText(value.judul, `laporan/${id}/judul`),
    status: oneOf(value.status, ["draf", "tampil", "arsip"], "draf", `laporan/${id}/status`),
    payload: { ...value, id },
    ...timestamps(value),
  })],
  ["donasi", "donations", (id, value) => ({
    id,
    code: requiredText(value.kode, `donasi/${id}/kode`),
    program_id: requiredText(String(value.program_id ?? ""), `donasi/${id}/program_id`),
    donor_name: requiredText(value.nama_donatur, `donasi/${id}/nama_donatur`),
    whatsapp: String(value.whatsapp ?? ""),
    amount: positiveInteger(value.jumlah, `donasi/${id}/jumlah`),
    status: oneOf(value.status, ["menunggu", "berhasil", "gagal"], "menunggu", `donasi/${id}/status`),
    payment_method: String(value.metode_pembayaran ?? "QRIS"),
    anonymous: value.anonim === true || value.anonim === 1 || value.anonim === "1" || value.anonim === "true",
    payment_claimed_at: value.pembayaran_dikonfirmasi_donatur_pada || null,
    message: value.pesan || null,
    payload: { ...value, id },
    ...timestamps(value),
  })],
  ["pesanan", "orders", (id, value) => ({
    id,
    code: requiredText(value.kode, `pesanan/${id}/kode`),
    product_id: requiredText(String(value.produk_id ?? ""), `pesanan/${id}/produk_id`),
    product_name: requiredText(value.nama_produk, `pesanan/${id}/nama_produk`),
    unit_price: integer(value.harga_satuan),
    quantity: positiveInteger(value.jumlah, `pesanan/${id}/jumlah`),
    total: integer(value.total),
    customer_name: requiredText(value.nama_pemesan, `pesanan/${id}/nama_pemesan`),
    whatsapp: String(value.whatsapp ?? ""),
    address: String(value.alamat ?? ""),
    note: value.catatan || null,
    status: oneOf(value.status, ["menunggu", "dibayar", "diproses", "dikirim", "selesai", "batal"], "menunggu", `pesanan/${id}/status`),
    payload: { ...value, id },
    ...timestamps(value),
  })],
];

function requiredText(value, field) {
  const text = String(value ?? "").trim();
  if (!text) throw new Error(`Missing required value: ${field}`);
  return text;
}

function integer(value) {
  const number = Number(value ?? 0);
  if (!Number.isSafeInteger(number) || number < 0) throw new Error("Invalid numeric value in source data.");
  return number;
}

function positiveInteger(value, field) {
  const number = integer(value);
  if (number < 1) throw new Error(`Invalid positive number: ${field}`);
  return number;
}

function oneOf(value, allowed, fallback, field) {
  if (value === undefined || value === null || value === "") return fallback;
  if (allowed.includes(value)) return value;
  throw new Error(`Unsupported value in ${field}: ${value}`);
}

function timestamps(value) {
  return {
    created_at: validTimestamp(value.dibuat_pada),
    updated_at: validTimestamp(value.diubah_pada ?? value.dibuat_pada),
  };
}

function validTimestamp(value) {
  if (!value) return new Date().toISOString();
  const time = Date.parse(value);
  if (Number.isNaN(time)) throw new Error("Invalid timestamp in source data.");
  return new Date(time).toISOString();
}

function getLocalMediaName(value, key, localAssets) {
  if (typeof value !== "string" || !value.trim() || /^(https?:)?\/\//i.test(value)) return null;
  const normalized = value.replace(/\\/g, "/");
  if (/^(\/?assets\/|data:|blob:)/i.test(normalized)) return null;
  const looksLikeUpload = /(^|\/)uploads\//i.test(normalized);
  if (!looksLikeUpload && !mediaFields.has(key)) return null;
  const name = basename(normalized);
  if (!mediaTypes.has(extname(name).toLowerCase())) return null;
  return localAssets.has(name) ? name : `missing:${name}`;
}

function findMediaReferences(value, localAssets, references = new Set(), key = "") {
  if (Array.isArray(value)) {
    for (const item of value) findMediaReferences(item, localAssets, references, key);
  } else if (value && typeof value === "object") {
    for (const [childKey, child] of Object.entries(value)) {
      findMediaReferences(child, localAssets, references, childKey);
    }
  } else {
    const name = getLocalMediaName(value, key, localAssets);
    if (name) references.add(name);
  }
  return references;
}

function rewriteMediaReferences(value, publicUrls, localAssets, key = "") {
  if (Array.isArray(value)) {
    return value.map((item) => rewriteMediaReferences(item, publicUrls, localAssets, key));
  }
  if (value && typeof value === "object") {
    return Object.fromEntries(Object.entries(value).map(([childKey, child]) => [
      childKey,
      rewriteMediaReferences(child, publicUrls, localAssets, childKey),
    ]));
  }
  const name = getLocalMediaName(value, key, localAssets);
  return name && !name.startsWith("missing:") ? publicUrls.get(name) || value : value;
}

async function loadLocalAssets() {
  let entries;
  try {
    entries = await readdir(uploadDirectory, { withFileTypes: true });
  } catch (error) {
    if (error.code === "ENOENT") throw new Error("The local uploads directory is missing.");
    throw error;
  }
  const assets = new Map();
  for (const entry of entries) {
    if (!entry.isFile()) continue;
    const type = mediaTypes.get(extname(entry.name).toLowerCase());
    if (type) assets.set(entry.name, { path: join(uploadDirectory, entry.name), type });
  }
  return assets;
}

async function uploadMedia(name, asset) {
  const response = await fetch(`${targetUrl}/storage/v1/object/site-assets/${encodeURIComponent(name)}`, {
    method: "POST",
    headers: {
      apikey: targetKey,
      Authorization: `******`,
      "Content-Type": asset.type,
      "x-upsert": "true",
    },
    body: await readFile(asset.path),
  });
  if (!response.ok) {
    throw new Error(`Uploading a referenced site asset failed (HTTP ${response.status}).`);
  }
  return `${targetUrl}/storage/v1/object/public/site-assets/${encodeURIComponent(name)}`;
}

function base64url(value) {
  return Buffer.from(value).toString("base64url");
}

async function getFirebaseToken(serviceAccount) {
  const now = Math.floor(Date.now() / 1000);
  const header = base64url(JSON.stringify({ alg: "RS256", typ: "JWT" }));
  const claims = base64url(JSON.stringify({
    iss: serviceAccount.client_email,
    scope: "https://www.googleapis.com/auth/firebase.database",
    aud: "https://oauth2.googleapis.com/token",
    iat: now,
    exp: now + 3600,
  }));
  const unsigned = `${header}.${claims}`;
  const signer = createSign("RSA-SHA256");
  signer.update(unsigned);
  const assertion = `${unsigned}.${signer.sign(serviceAccount.private_key, "base64url")}`;
  const response = await fetch("https://oauth2.googleapis.com/token", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      grant_type: "urn:ietf:params:oauth:grant-type:jwt-bearer",
      assertion,
    }),
  });
  if (!response.ok) throw new Error(`Firebase authorization failed (HTTP ${response.status}).`);
  const token = await response.json();
  if (!token.access_token) throw new Error("Firebase authorization returned no access token.");
  return token.access_token;
}

async function fetchCollection(collection, accessToken) {
  const response = await fetch(`${sourceUrl}/${collection}.json`, {
    headers: { Authorization: `Bearer ${accessToken}` },
  });
  if (!response.ok) throw new Error(`Reading Firebase collection "${collection}" failed (HTTP ${response.status}).`);
  const value = await response.json();
  if (value !== null && (typeof value !== "object" || Array.isArray(value))) {
    throw new Error(`Firebase collection "${collection}" has an unexpected shape.`);
  }
  return value || {};
}

async function upsertRows(table, rows, conflictColumn = "id") {
  const response = await fetch(`${targetUrl}/rest/v1/${table}?on_conflict=${conflictColumn}`, {
    method: "POST",
    headers: {
      apikey: targetKey,
      Authorization: `Bearer ${targetKey}`,
      "Content-Type": "application/json",
      Prefer: "resolution=merge-duplicates,return=minimal",
    },
    body: JSON.stringify(rows),
  });
  if (!response.ok) {
    const body = await response.text();
    throw new Error(`Supabase import into "${table}" failed (HTTP ${response.status}): ${body.slice(0, 500)}`);
  }
}

async function main() {
  if (!sourceUrl || !serviceAccountPath || !targetUrl || !targetKey) {
    throw new Error("Set FIREBASE_DATABASE_URL, FIREBASE_SERVICE_ACCOUNT_PATH, SUPABASE_URL, and SUPABASE_SERVICE_ROLE_KEY.");
  }

  const serviceAccount = JSON.parse(await readFile(serviceAccountPath, "utf8"));
  if (!serviceAccount.client_email || !serviceAccount.private_key) {
    throw new Error("The Firebase service-account file is not valid.");
  }
  const firebaseToken = await getFirebaseToken(serviceAccount);
  const records = [];

  for (const [source, table, mapRecord] of collections) {
    const data = await fetchCollection(source, firebaseToken);
    const rows = Object.entries(data).map(([id, value]) => {
      if (!value || typeof value !== "object" || Array.isArray(value)) {
        throw new Error(`Unexpected record format in Firebase collection "${source}".`);
      }
      return mapRecord(String(value.id ?? id), value);
    });
    records.push({ source, table, rows });
    console.log(`${source} -> ${table}: ${rows.length} record(s) ready`);
  }

  const settings = await fetchCollection("pengaturan", firebaseToken);
  const settingRows = Object.entries(settings).map(([key, value]) => ({
    key,
    value,
    updated_at: new Date().toISOString(),
  }));
  records.push({ source: "pengaturan", table: "site_settings", rows: settingRows, conflictColumn: "key" });
  console.log(`pengaturan -> site_settings: ${settingRows.length} record(s) ready`);
  console.log("Admin password records are intentionally not migrated. Create Supabase Auth users and grant admin access separately.");

  const localAssets = await loadLocalAssets();
  const references = new Set();
  for (const { table, rows } of records) {
    if (table === "site_settings") {
      for (const row of rows) findMediaReferences(row.value, localAssets, references, row.key);
    } else {
      findMediaReferences(rows, localAssets, references);
    }
  }
  const missingAssets = [...references].filter((name) => name.startsWith("missing:"));
  const requiredAssets = [...references].filter((name) => !name.startsWith("missing:"));
  console.log(`uploads -> site-assets: ${requiredAssets.length} referenced file(s) ready`);
  if (missingAssets.length) {
    console.warn(`${missingAssets.length} referenced upload(s) are missing locally; review before migration.`);
  }
  const products = records.find(({ table }) => table === "products").rows;
  const programs = records.find(({ table }) => table === "programs").rows;
  const donations = records.find(({ table }) => table === "donations").rows;
  const orders = records.find(({ table }) => table === "orders").rows;
  const productIds = new Set(products.map(({ id }) => id));
  const programIds = new Set(programs.map(({ id }) => id));
  const missingProgramReferences = donations.filter(({ program_id }) => !programIds.has(program_id)).length;
  const missingProductReferences = orders.filter(({ product_id }) => !productIds.has(product_id)).length;
  const duplicateSlugs = new Set(products.map(({ slug }) => slug)).size !== products.length
    || new Set(programs.map(({ slug }) => slug)).size !== programs.length;
  console.log(`Reference checks: ${missingProgramReferences} missing program(s), ${missingProductReferences} missing product(s)`);
  if (duplicateSlugs) console.warn("Duplicate product/program slugs were found; Supabase will reject those rows.");

  if (!confirmed) {
    console.log("Dry run only. Review the counts, then rerun with --confirm-import to send these records to the configured Supabase project.");
    return;
  }

  if (!targetUrl.startsWith("https://") || !targetKey || targetKey.length < 20) {
    throw new Error("The Supabase destination configuration is invalid.");
  }
  if (missingAssets.length) {
    throw new Error("Import stopped before writing data because referenced uploads are missing locally.");
  }
  if (missingProgramReferences || missingProductReferences || duplicateSlugs) {
    throw new Error("Import stopped before writing data because source records violate database relationships or unique slugs.");
  }
  const publicUrls = new Map();
  for (const name of requiredAssets) {
    const asset = localAssets.get(name);
    if ((await readFile(asset.path)).length > 5 * 1024 * 1024) {
      throw new Error("Import stopped because a referenced upload exceeds the 5 MB storage limit.");
    }
    publicUrls.set(name, await uploadMedia(name, asset));
  }
  for (const { rows } of records) {
    for (const row of rows) {
      if (row.payload) row.payload = rewriteMediaReferences(row.payload, publicUrls, localAssets);
    }
  }
  for (const row of records.find(({ table }) => table === "site_settings").rows) {
    row.value = rewriteMediaReferences(row.value, publicUrls, localAssets, row.key);
  }

  for (const { table, rows, conflictColumn } of records) {
    for (let offset = 0; offset < rows.length; offset += 100) {
      await upsertRows(table, rows.slice(offset, offset + 100), conflictColumn);
    }
    console.log(`Imported ${rows.length} record(s) into ${table}.`);
  }
  console.log(`Migration completed to ${new URL(targetUrl).host}. No Firebase data was deleted.`);
  console.log(`Firebase credential file used locally: ${basename(serviceAccountPath)} (not copied).`);
}

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
