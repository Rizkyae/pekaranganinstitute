const {
  getSupabaseConfig,
  readJson,
  requireAdmin,
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
    const admin = await requireAdmin(req, res);
    if (!admin) return;
    const input = await readJson(req, res);
    if (!input) return;
    const result = await supabaseRequest("/rest/v1/rpc/set_order_status", {
      method: "POST",
      body: {
        p_order_id: String(input.orderId || ""),
        p_new_status: String(input.status || ""),
      },
    });
    if (result !== true) {
      send(res, 404, { error: "Pesanan tidak ditemukan." });
      return;
    }
    send(res, 200, { updated: true });
  } catch (error) {
    sendFailure(res, error);
  }
};
