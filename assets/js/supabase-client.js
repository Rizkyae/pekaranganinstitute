import { createClient } from "https://esm.sh/@supabase/supabase-js@2";

let clientPromise;

export async function getSupabase() {
  if (!clientPromise) {
    clientPromise = fetch("/api/config", { headers: { Accept: "application/json" } })
      .then(async (response) => {
        if (!response.ok) throw new Error("Konfigurasi Supabase belum dapat dimuat.");
        const config = await response.json();
        if (!config.supabaseUrl || !config.supabaseAnonKey) {
          throw new Error("Supabase belum tersambung. Atur SUPABASE_URL dan SUPABASE_ANON_KEY pada Environment Variables Vercel.");
        }
        return createClient(config.supabaseUrl, config.supabaseAnonKey, {
          auth: { persistSession: true, autoRefreshToken: true, detectSessionInUrl: true },
        });
      });
  }
  return clientPromise;
}

export function getErrorMessage(error) {
  return error?.message || "Terjadi kesalahan. Silakan coba kembali.";
}
