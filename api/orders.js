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
    if (input.website) {
      send(res, 400, { error: "Permintaan tidak valid." });
      return;
    }
    const result = await supabaseRequest("/rest/v1/rpc/create_public_order", {
      method: "POST",
      body: {
        p_product_id: String(input.productId || ""),
        p_quantity: Number(input.quantity),
        p_customer_name: String(input.customerName || ""),
        p_whatsapp: String(input.whatsapp || ""),
        p_address: String(input.address || ""),
        p_note: input.note ? String(input.note) : null,
      },
    });
    const order = Array.isArray(result) ? result[0] : result;
    if (!order?.order_code) {
      send(res, 503, { error: "Pesanan belum berhasil dicatat." });
      return;
    }
    send(res, 201, { code: order.order_code });
  } catch (error) {
    sendFailure(res, error);
  }
};
