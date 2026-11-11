const {
  getSupabaseConfig,
  readJson,
  requireMethod,
  requireSameOrigin,
  send,
  sendFailure,
  supabaseRequest,
} = require("../lib/supabase-server");

module.exports = async (req, res) => {
  if (!requireMethod(req, res, "POST") || !requireSameOrigin(req, res)) return;
  try {
    getSupabaseConfig();
    const input = await readJson(req, res);
    if (!input) return;
    const code = String(input.code || "").trim();
    if (!/^DON-\d{8}-[A-Z0-9]{4,16}$/i.test(code)) {
      send(res, 400, { error: "Kode donasi tidak valid." });
      return;
    }
    const claimed = await supabaseRequest("/rest/v1/rpc/claim_public_donation", {
      method: "POST",
      body: { p_code: code },
    });
    if (claimed !== true) {
      send(res, 409, { error: "Donasi tidak ditemukan atau tidak lagi menunggu pembayaran." });
      return;
    }
    send(res, 200, { claimed: true });
  } catch (error) {
    sendFailure(res, error);
  }
};
