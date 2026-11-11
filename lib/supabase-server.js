const JSON_HEADERS = { "Content-Type": "application/json; charset=utf-8" };

function send(res, status, body) {
  res.writeHead(status, JSON_HEADERS);
  res.end(JSON.stringify(body));
}

function requireMethod(req, res, expected) {
  if (req.method !== expected) {
    send(res, 405, { error: "Metode tidak diizinkan." });
    return false;
  }
  return true;
}

function requireSameOrigin(req, res) {
  const origin = req.headers.origin;
  const host = req.headers.host;
  if (!origin || !host) return true;

  try {
    if (new URL(origin).host === host) return true;
  } catch {
    // Reject malformed origins below.
  }

  send(res, 403, { error: "Asal permintaan tidak diizinkan." });
  return false;
}

async function readJson(req, res) {
  let raw = "";
  for await (const chunk of req) {
    raw += chunk;
    if (raw.length > 16384) {
      send(res, 413, { error: "Data permintaan terlalu besar." });
      return null;
    }
  }

  try {
    return JSON.parse(raw || "{}");
  } catch {
    send(res, 400, { error: "Format permintaan tidak valid." });
    return null;
  }
}

function getSupabaseConfig() {
  const url = process.env.SUPABASE_URL;
  const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
  if (!url || !serviceKey) {
    throw new Error("Supabase server environment is not configured.");
  }
  return { url: url.replace(/\/+$/, ""), serviceKey };
}

async function supabaseRequest(path, { method = "GET", body, headers = {} } = {}) {
  const { url, serviceKey } = getSupabaseConfig();
  const response = await fetch(`${url}${path}`, {
    method,
    headers: {
      apikey: serviceKey,
      Authorization: `Bearer ${serviceKey}`,
      "Content-Type": "application/json",
      ...headers,
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  const text = await response.text();
  let data;
  try {
    data = text ? JSON.parse(text) : null;
  } catch {
    data = null;
  }
  if (!response.ok) {
    const error = new Error("Supabase request failed.");
    error.status = response.status;
    error.details = data;
    throw error;
  }
  return data;
}

async function requireAdmin(req, res) {
  const authorization = req.headers.authorization || "";
  const accessToken = authorization.match(/^Bearer\s+(.+)$/i)?.[1];
  const { url } = getSupabaseConfig();
  const anonKey = process.env.SUPABASE_ANON_KEY;
  if (!accessToken || !anonKey) {
    send(res, 401, { error: "Silakan masuk sebagai admin." });
    return null;
  }

  const userResponse = await fetch(`${url}/auth/v1/user`, {
    headers: { apikey: anonKey, Authorization: `Bearer ${accessToken}` },
  });
  if (!userResponse.ok) {
    send(res, 401, { error: "Sesi masuk tidak valid atau telah berakhir." });
    return null;
  }
  const user = await userResponse.json();
  const admins = await supabaseRequest(
    `/rest/v1/admin_users?id=eq.${encodeURIComponent(user.id)}&active=eq.true&select=id&limit=1`,
  );
  if (!admins?.length) {
    send(res, 403, { error: "Akun ini tidak memiliki akses admin." });
    return null;
  }
  return user;
}

function sendFailure(res, error) {
  if (error.status === 400 || error.status === 409) {
    send(res, error.status, { error: "Data tidak valid atau terjadi konflik. Periksa input lalu coba kembali." });
    return;
  }
  console.error("Supabase API request failed:", error.message);
  send(res, 503, { error: "Layanan data sedang tidak tersedia. Coba kembali." });
}

module.exports = {
  getSupabaseConfig,
  readJson,
  requireAdmin,
  requireMethod,
  requireSameOrigin,
  send,
  sendFailure,
  supabaseRequest,
};
