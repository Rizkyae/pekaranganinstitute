# Pekarangan Institute — Vercel + Supabase

The site is being migrated from PHP/Firebase to static HTML, browser JavaScript,
Vercel Functions, and Supabase. The HTML pages are static; privileged writes and
order/payment claims use server-side Vercel Functions. The Supabase service-role
key must never be added to browser code or committed to Git.

## Set up Supabase

1. Create a Supabase project.
2. In **SQL Editor**, run `supabase/migrations/202611110001_initial_schema.sql`.
3. In **Authentication → Users**, create the first administrator. Public sign-up
   is intentionally not used.
4. Open `/admin/setup.html` and run its displayed `INSERT` statement in the
   Supabase SQL Editor using that user's UUID and email.
5. In **Project Settings → API**, copy the project URL and publishable/anon key.
   The service-role key is server-only.

## Configure Vercel

Import this repository as a Vercel project. Set these environment variables for
the desired deployment environments:

- `SUPABASE_URL` — Supabase project URL.
- `SUPABASE_ANON_KEY` — Supabase publishable/anon key (public).
- `SUPABASE_SERVICE_ROLE_KEY` — secret; server-side Vercel Functions only.

Deploy, then check `/admin/tes-koneksi.html`. The site uses Supabase Storage bucket
`site-assets` for administrator media uploads. The SQL migration creates its
bucket and access policies.

## Import existing Firebase data

The migration script is a **dry run by default**. It reads products, programs,
reports, donations, orders, and public settings; administrator password records
are deliberately excluded because they cannot be imported as Supabase Auth
passwords. It also checks legacy image/PDF references against local `uploads/`;
on confirmed import it copies referenced media into Supabase Storage and rewrites
the imported records to their new public URLs. Missing local media stops a
confirmed import before any database rows are written. No source data is deleted.

Set these variables in a local shell only:

- `FIREBASE_DATABASE_URL`
- `FIREBASE_SERVICE_ACCOUNT_PATH` — a Firebase service-account JSON file kept
  outside the repository.
- `SUPABASE_URL`
- `SUPABASE_SERVICE_ROLE_KEY` — do not paste into chat or commit it.

Run `node scripts/migrate-firebase.mjs` to inspect record counts. After checking
that the Supabase project is the intended destination and that the migration is
approved, run `node scripts/migrate-firebase.mjs --confirm-import` to upsert the
records. The script does not read or upload the Firebase `admin` collection.
Review imported records and public/private access before switching production
traffic.

## Local checks

- `node --check assets/js/app.js`
- `node --check assets/js/admin-app.js`
- `node --check lib/supabase-server.js`
- `node --check api/donations.js`
- `node scripts/migrate-firebase.mjs` (requires the local Firebase key and
  Supabase destination variables; do not run the import flag until reviewed)
