create extension if not exists pgcrypto;

create table if not exists public.admin_users (
    id uuid primary key references auth.users (id) on delete cascade,
    email text not null unique,
    display_name text not null default '',
    active boolean not null default true,
    created_at timestamptz not null default now()
);

create or replace function public.is_admin()
returns boolean
language sql
stable
security definer
set search_path = ''
as $$
    select exists (
        select 1
        from public.admin_users
        where id = (select auth.uid())
          and active
    );
$$;

revoke all on function public.is_admin() from public;
grant execute on function public.is_admin() to authenticated;
grant execute on function public.is_admin() to anon;

create table if not exists public.site_settings (
    key text primary key,
    value jsonb not null default 'null'::jsonb,
    updated_at timestamptz not null default now()
);

create table if not exists public.products (
    id text primary key,
    slug text not null unique,
    name text not null,
    status text not null default 'draf' check (status in ('draf', 'tampil', 'arsip')),
    price bigint not null default 0 check (price >= 0),
    stock integer not null default 0 check (stock >= 0),
    reserved_stock integer not null default 0 check (reserved_stock >= 0),
    check (reserved_stock <= stock),
    payload jsonb not null default '{}'::jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.programs (
    id text primary key,
    slug text not null unique,
    title text not null,
    status text not null default 'aktif' check (status in ('aktif', 'selesai', 'arsip')),
    target_amount bigint not null default 0 check (target_amount >= 0),
    payload jsonb not null default '{}'::jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.donations (
    id text primary key,
    code text not null unique,
    program_id text not null references public.programs (id) on update cascade,
    donor_name text not null,
    whatsapp text not null default '',
    amount bigint not null check (amount > 0),
    status text not null default 'menunggu' check (status in ('menunggu', 'berhasil', 'gagal')),
    payment_method text not null default 'QRIS',
    anonymous boolean not null default false,
    payment_claimed_at timestamptz,
    message text,
    payload jsonb not null default '{}'::jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.orders (
    id text primary key,
    code text not null unique,
    product_id text not null references public.products (id) on update cascade,
    product_name text not null,
    unit_price bigint not null check (unit_price >= 0),
    quantity integer not null check (quantity > 0),
    total bigint not null check (total >= 0),
    customer_name text not null,
    whatsapp text not null,
    address text not null,
    note text,
    status text not null default 'menunggu' check (status in ('menunggu', 'dibayar', 'diproses', 'dikirim', 'selesai', 'batal')),
    payload jsonb not null default '{}'::jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.reports (
    id text primary key,
    title text not null,
    status text not null default 'draf' check (status in ('draf', 'tampil', 'arsip')),
    payload jsonb not null default '{}'::jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create index if not exists products_status_created_idx on public.products (status, created_at desc);
create index if not exists programs_status_created_idx on public.programs (status, created_at desc);
create index if not exists donations_program_status_idx on public.donations (program_id, status, created_at desc);
create index if not exists orders_status_created_idx on public.orders (status, created_at desc);
create index if not exists reports_status_created_idx on public.reports (status, created_at desc);

grant usage on schema public to anon, authenticated;
grant select on public.products, public.programs, public.reports, public.site_settings to authenticated;
grant select on public.donations, public.orders to authenticated;
grant select, insert, update, delete on public.admin_users to authenticated;
grant insert, update, delete on public.products, public.programs, public.donations,
    public.reports, public.site_settings to authenticated;
grant delete on public.orders to authenticated;
revoke insert, update on public.orders from authenticated;

alter table public.admin_users enable row level security;
alter table public.site_settings enable row level security;
alter table public.products enable row level security;
alter table public.programs enable row level security;
alter table public.donations enable row level security;
alter table public.orders enable row level security;
alter table public.reports enable row level security;

create policy "Admins can manage admin users"
    on public.admin_users for all to authenticated
    using (public.is_admin()) with check (public.is_admin());

create policy "Admins can manage site settings"
    on public.site_settings for all to authenticated
    using (public.is_admin()) with check (public.is_admin());

create policy "Admins can manage products"
    on public.products for all to authenticated
    using (public.is_admin()) with check (public.is_admin());

create policy "Admins can manage programs"
    on public.programs for all to authenticated
    using (public.is_admin()) with check (public.is_admin());

create policy "Admins can manage donations"
    on public.donations for all to authenticated
    using (public.is_admin()) with check (public.is_admin());

create policy "Admins can read orders"
    on public.orders for select to authenticated
    using (public.is_admin());

create policy "Admins can delete orders"
    on public.orders for delete to authenticated
    using (public.is_admin());

create policy "Admins can manage reports"
    on public.reports for all to authenticated
    using (public.is_admin()) with check (public.is_admin());

create or replace view public.public_products
with (security_barrier = true)
as
select id, slug, name, status, price, greatest(stock - reserved_stock, 0) as stock,
       jsonb_build_object(
           'nama', payload -> 'nama',
           'kategori', payload -> 'kategori',
           'deskripsi_singkat', payload -> 'deskripsi_singkat',
           'deskripsi', payload -> 'deskripsi',
           'penjual', payload -> 'penjual',
           'foto', payload -> 'foto',
           'slug', payload -> 'slug'
       ) as payload,
       created_at, updated_at
from public.products
where status = 'tampil';

create or replace view public.public_programs
with (security_barrier = true)
as
select id, slug, title, status, target_amount,
       jsonb_build_object(
           'judul', payload -> 'judul',
           'slug', payload -> 'slug',
           'ringkasan', payload -> 'ringkasan',
           'cerita', payload -> 'cerita',
           'target_dana', payload -> 'target_dana',
           'tenggat', payload -> 'tenggat',
           'lokasi', payload -> 'lokasi',
           'penerima_manfaat', payload -> 'penerima_manfaat',
           'foto_sampul', payload -> 'foto_sampul'
       ) as payload,
       created_at, updated_at
from public.programs
where status = 'aktif';

create or replace view public.public_reports
with (security_barrier = true)
as
select id, title,
       jsonb_build_object(
           'judul', payload -> 'judul',
           'periode', payload -> 'periode',
           'ringkasan', payload -> 'ringkasan',
           'berkas', payload -> 'berkas'
       ) as payload,
       created_at, updated_at
from public.reports
where status = 'tampil';

create or replace view public.public_settings
with (security_barrier = true)
as
select key, value
from public.site_settings
where key in (
    'nama_situs', 'tagline', 'email', 'alamat', 'whatsapp',
    'donasi_minimum', 'alokasi_operasional', 'alokasi_sosial',
    'catatan_transparansi', 'hero_judul', 'hero_teks', 'hero_foto',
    'langkah_1_judul', 'langkah_1_teks', 'langkah_2_judul',
    'langkah_2_teks', 'langkah_3_judul', 'langkah_3_teks',
    'tentang_teks', 'tentang_foto', 'pilar_1_judul', 'pilar_1_teks',
    'pilar_2_judul', 'pilar_2_teks', 'pilar_3_judul', 'pilar_3_teks',
    'footer_teks', 'logo_mitra', 'logo_instansi'
);

grant select on public.public_products, public.public_programs, public.public_reports,
    public.public_settings to anon, authenticated;

create policy "Public can read visible products"
    on public.products for select to anon, authenticated
    using (status = 'tampil' or public.is_admin());

create policy "Public can read active programs"
    on public.programs for select to anon, authenticated
    using (status = 'aktif' or public.is_admin());

create policy "Public can read published reports"
    on public.reports for select to anon, authenticated
    using (status = 'tampil' or public.is_admin());

create policy "Public can read public settings"
    on public.site_settings for select to anon, authenticated
    using (
        key in (
            'nama_situs', 'tagline', 'email', 'alamat', 'whatsapp',
            'donasi_minimum', 'alokasi_operasional', 'alokasi_sosial',
            'catatan_transparansi', 'hero_judul', 'hero_teks', 'hero_foto',
            'langkah_1_judul', 'langkah_1_teks', 'langkah_2_judul',
            'langkah_2_teks', 'langkah_3_judul', 'langkah_3_teks',
            'tentang_teks', 'tentang_foto', 'pilar_1_judul', 'pilar_1_teks',
            'pilar_2_judul', 'pilar_2_teks', 'pilar_3_judul', 'pilar_3_teks',
            'footer_teks', 'logo_mitra', 'logo_instansi'
        ) or public.is_admin()
    );

create or replace function public.create_public_donation(
    p_program_id text,
    p_amount bigint,
    p_donor_name text,
    p_whatsapp text,
    p_anonymous boolean default false,
    p_message text default null
)
returns table (donation_id text, donation_code text)
language plpgsql
security definer
set search_path = ''
as $$
declare
    v_program public.programs%rowtype;
    v_minimum bigint := 10000;
    v_code text;
    v_id text := gen_random_uuid()::text;
begin
    select * into v_program
    from public.programs
    where id = p_program_id and status = 'aktif';
    if not found then
        raise exception 'Program tidak tersedia';
    end if;

    select coalesce((value #>> '{}')::bigint, 10000) into v_minimum
    from public.site_settings where key = 'donasi_minimum';
    v_minimum := greatest(coalesce(v_minimum, 10000), 1000);

    if p_amount < v_minimum or p_amount > 1000000000 then
        raise exception 'Nominal donasi di luar batas yang diizinkan';
    end if;
    if length(trim(p_donor_name)) not between 1 and 150
       or length(trim(p_whatsapp)) not between 8 and 24
       or (p_message is not null and length(p_message) > 500) then
        raise exception 'Data donasi tidak valid';
    end if;

    v_code := 'DON-' || to_char(now() at time zone 'Asia/Jakarta', 'YYYYMMDD') || '-' ||
              upper(encode(gen_random_bytes(8), 'hex'));

    insert into public.donations (
        id, code, program_id, donor_name, whatsapp, amount, status,
        payment_method, anonymous, message, payload
    ) values (
        v_id, v_code, p_program_id, trim(p_donor_name), trim(p_whatsapp),
        p_amount, 'menunggu', 'QRIS', coalesce(p_anonymous, false),
        nullif(trim(coalesce(p_message, '')), ''),
        jsonb_build_object('migrated', false)
    );

    return query select v_id, v_code;
end;
$$;

create or replace function public.claim_public_donation(p_code text)
returns boolean
language plpgsql
security definer
set search_path = ''
as $$
begin
    update public.donations
    set payment_claimed_at = coalesce(payment_claimed_at, now()),
        updated_at = now()
    where code = upper(trim(p_code))
      and status = 'menunggu';
    return found;
end;
$$;

create or replace function public.create_public_order(
    p_product_id text,
    p_quantity integer,
    p_customer_name text,
    p_whatsapp text,
    p_address text,
    p_note text default null
)
returns table (order_id text, order_code text)
language plpgsql
security definer
set search_path = ''
as $$
declare
    v_product public.products%rowtype;
    v_code text;
    v_id text := gen_random_uuid()::text;
begin
    if p_quantity < 1 or p_quantity > 100
       or length(trim(p_customer_name)) not between 1 and 150
       or length(trim(p_whatsapp)) not between 8 and 24
       or length(trim(p_address)) not between 1 and 1000
       or (p_note is not null and length(p_note) > 500) then
        raise exception 'Data pesanan tidak valid';
    end if;

    select * into v_product
    from public.products
    where id = p_product_id and status = 'tampil'
    for update;
    if not found or v_product.stock - v_product.reserved_stock < p_quantity then
        raise exception 'Produk tidak tersedia atau stok tidak mencukupi';
    end if;

    update public.products
    set reserved_stock = reserved_stock + p_quantity, updated_at = now()
    where id = v_product.id;

    v_code := 'ORD-' || to_char(now() at time zone 'Asia/Jakarta', 'YYYYMMDD') || '-' ||
              upper(encode(gen_random_bytes(8), 'hex'));

    insert into public.orders (
        id, code, product_id, product_name, unit_price, quantity, total,
        customer_name, whatsapp, address, note, status
    ) values (
        v_id, v_code, v_product.id, v_product.name, v_product.price,
        p_quantity, v_product.price * p_quantity, trim(p_customer_name),
        trim(p_whatsapp), trim(p_address), nullif(trim(coalesce(p_note, '')), ''),
        'menunggu'
    );

    return query select v_id, v_code;
end;
$$;

create or replace function public.set_order_status(p_order_id text, p_new_status text)
returns boolean
language plpgsql
security definer
set search_path = ''
as $$
declare
    v_order public.orders%rowtype;
begin
    if p_new_status not in ('menunggu', 'dibayar', 'diproses', 'dikirim', 'selesai', 'batal') then
        raise exception 'Status pesanan tidak valid';
    end if;

    select * into v_order from public.orders where id = p_order_id for update;
    if not found then
        return false;
    end if;
    if v_order.status = p_new_status then
        return true;
    end if;
    if not (
        (v_order.status = 'menunggu' and p_new_status in ('dibayar', 'batal'))
        or (v_order.status = 'dibayar' and p_new_status in ('diproses', 'batal'))
        or (v_order.status = 'diproses' and p_new_status = 'dikirim')
        or (v_order.status = 'dikirim' and p_new_status = 'selesai')
    ) then
        raise exception 'Transisi status pesanan tidak diizinkan';
    end if;

    if v_order.status = 'menunggu' and p_new_status = 'dibayar' then
        update public.products
        set stock = stock - v_order.quantity,
            reserved_stock = greatest(0, reserved_stock - v_order.quantity),
            updated_at = now()
        where id = v_order.product_id and stock >= v_order.quantity;
        if not found then
            raise exception 'Stok produk berubah; pesanan tidak dapat dikonfirmasi';
        end if;
    elsif v_order.status = 'menunggu' and p_new_status = 'batal' then
        update public.products
        set reserved_stock = greatest(0, reserved_stock - v_order.quantity),
            updated_at = now()
        where id = v_order.product_id;
    elsif v_order.status = 'dibayar' and p_new_status = 'batal' then
        update public.products
        set stock = stock + v_order.quantity, updated_at = now()
        where id = v_order.product_id;
    end if;

    update public.orders
    set status = p_new_status, updated_at = now()
    where id = p_order_id;
    return true;
end;
$$;

create or replace function public.public_program_fundraising(p_program_id text)
returns table (total_amount bigint, donor_count bigint)
language sql
stable
security definer
set search_path = ''
as $$
    select coalesce(sum(amount), 0)::bigint, count(*)::bigint
    from public.donations
    where program_id = p_program_id and status = 'berhasil';
$$;

create or replace function public.public_recent_donors(p_program_id text, p_limit integer default 5)
returns table (display_name text, amount bigint, donated_at timestamptz)
language sql
stable
security definer
set search_path = ''
as $$
    select case when anonymous then 'Hamba Allah' else donor_name end,
           amount, created_at
    from public.donations
    where program_id = p_program_id and status = 'berhasil'
    order by created_at desc
    limit greatest(1, least(coalesce(p_limit, 5), 20));
$$;

revoke all on function public.create_public_donation(text, bigint, text, text, boolean, text) from public;
revoke all on function public.claim_public_donation(text) from public;
revoke all on function public.create_public_order(text, integer, text, text, text, text) from public;
revoke all on function public.set_order_status(text, text) from public;
revoke all on function public.public_program_fundraising(text) from public;
revoke all on function public.public_recent_donors(text, integer) from public;
grant execute on function public.create_public_donation(text, bigint, text, text, boolean, text) to service_role;
grant execute on function public.claim_public_donation(text) to service_role;
grant execute on function public.create_public_order(text, integer, text, text, text, text) to service_role;
grant execute on function public.set_order_status(text, text) to service_role;
grant execute on function public.public_program_fundraising(text) to anon, authenticated;
grant execute on function public.public_recent_donors(text, integer) to anon, authenticated;

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values (
    'site-assets', 'site-assets', true, 5242880,
    array['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
)
on conflict (id) do update
set public = excluded.public,
    file_size_limit = excluded.file_size_limit,
    allowed_mime_types = excluded.allowed_mime_types;

create policy "Public can view site assets"
    on storage.objects for select to anon, authenticated
    using (bucket_id = 'site-assets');

create policy "Admins can upload site assets"
    on storage.objects for insert to authenticated
    with check (bucket_id = 'site-assets' and public.is_admin());

create policy "Admins can update site assets"
    on storage.objects for update to authenticated
    using (bucket_id = 'site-assets' and public.is_admin())
    with check (bucket_id = 'site-assets' and public.is_admin());

create policy "Admins can delete site assets"
    on storage.objects for delete to authenticated
    using (bucket_id = 'site-assets' and public.is_admin());
