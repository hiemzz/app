// koneksi.js
var supabaseUrl = 'https://odlvozfueejngirfrczo.supabase.co';
var supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9kbHZvemZ1ZWVqbmdpcmZyY3pvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzczODU1MDYsImV4cCI6MjA5Mjk2MTUwNn0.yp03bnfws8IrMu_evwesLM8u0cIMIje8ufUhrFSsM38';

// Gunakan var agar variabel ini terbaca di semua halaman
var dbCloud = supabase.createClient(supabaseUrl, supabaseKey);
