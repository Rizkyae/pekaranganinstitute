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
    const result = await supabaseRequest("/rest/v1/rpc/create_public_donation", {
      method: "POST",
      body: {
        p_program_id: String(input.programId || ""),
        p_amount: Number(input.amount),
        p_donor_name: String(input.donorName || ""),
        p_whatsapp: String(input.whatsapp || ""),
        p_anonymous: Boolean(input.anonymous),
        p_message: input.message ? String(input.message) : null,
      },
    });
    const donation = Array.isArray(result) ? result[0] : result;
    if (!donation?.donation_code) {
      send(res, 503, { error: "Donasi belum berhasil dicatat." });
      return;
    }
    send(res, 201, { code: donation.donation_code });
  } catch (error) {
    sendFailure(res, error);
  }
};
