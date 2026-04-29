// Ganti isi koneksi.js kamu dengan kode ini sepenuhnya
const supabaseUrl = 'https://odlvozfueejngirfrczo.supabase.co';
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9kbHZvemZ1ZWVqbmdpcmZyY3pvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzczODU1MDYsImV4cCI6MjA5Mjk2MTUwNn0.yp03bnfws8IrMu_evwesLM8u0cIMIje8ufUhrFSsM38';

// PERBAIKAN: Menimpa library bawaan agar langsung menjadi jembatan koneksi
window.supabase = window.supabase.createClient(supabaseUrl, supabaseKey);
