const { requireMethod, send } = require("../lib/supabase-server");

module.exports = (req, res) => {
  if (!requireMethod(req, res, "GET")) return;
  res.setHeader("Cache-Control", "no-store");
  send(res, 200, {
    supabaseUrl: process.env.SUPABASE_URL || "",
    supabaseAnonKey: process.env.SUPABASE_ANON_KEY || "",
  });
};
