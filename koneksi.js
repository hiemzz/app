// Konfigurasi Supabase - Ganti dengan data dari Project Settings > API di Supabase
const supabaseUrl = 'https://odlvozfueejngirfrczo.supabase.co/rest/v1/';
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9kbHZvemZ1ZWVqbmdpcmZyY3pvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzczODU1MDYsImV4cCI6MjA5Mjk2MTUwNn0.yp03bnfws8IrMu_evwesLM8u0cIMIje8ufUhrFSsM38';
const supabase = supabase.createClient(supabaseUrl, supabaseKey);

// Fungsi pembantu untuk format data agar cocok dengan logic stok.php lama
function formatInventoryFromSupabase(data) {
    let formatted = {};
    data.forEach(item => {
        if (!formatted[item.sloc]) formatted[item.sloc] = [];
        formatted[item.sloc].push({
            code: item.material_code,
            desc: item.material_desc,
            batch: item.batch,
            qty: item.qty_unrestricted,
            qInsp: item.qty_insp,
            qBlock: item.qty_block,
            is_manual: item.is_manual
        });
    });
    return formatted;
}
