<?php
return [
 'driver' => env('BANK_DRIVER', 'sqlite'),
 'admin_email' => env('ADMIN_EMAIL', 'admin@banco.local'),
 'admin_password' => env('ADMIN_PASSWORD'),
 'admin_token' => env('ADMIN_TOKEN'),
 'supabase_url' => env('SUPABASE_URL'),
 'supabase_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
 'supabase_anon_key' => env('SUPABASE_ANON_KEY'),
 'admin_supabase_id' => env('ADMIN_SUPABASE_ID'),
];
