<?php
// --- SERVER SIDE LOGIC (PHP) ---
$db_file = 'database.json';

function load_db() {
    global $db_file;
    if (!file_exists($db_file)) {
        return ['layout' => [], 'headers' => [], 'inventory' => [], 'vert' => [], 'batchDB' => [], 'defaultAlloc' => 'PRODUKSI'];
    }
    $content = file_get_contents($db_file);
    $decoded = json_decode($content, true);
    return $decoded ?: ['layout' => [], 'headers' => [], 'inventory' => [], 'vert' => [], 'batchDB' => [], 'defaultAlloc' => 'PRODUKSI'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'sync_server') {
        file_put_contents($db_file, $_POST['payload']);
        echo "Data Server Berhasil Diperbarui!";
        exit;
    }
}
$server_data = load_db();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Pro - Modern Pastel 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root { 
            /* Modern Pastel Palette */
            --primary: #6366f1; /* Soft Indigo */
            --primary-hover: #4f46e5;
            --red: #ff8787; /* Pastel Red */
            --green: #69db7c; /* Pastel Green */
            --orange: #ffa94d; /* Pastel Orange */
            --dark: #1e293b;
            --purple: #b197fc; /* Pastel Purple */
            --expired: #343a40; /* Dark gray */
            --warning: #ffc078; /* Soft warning */
            
            /* Glassmorphism */
            --glass: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.6);
            --bg-body: #f8fafc;
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg-body); 
            margin: 0; padding: 15px; 
            color: #334155; 
            font-size: 12px; 
        }

        /* Dashboard Cards */
        .summary-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .summary-card { 
            background: var(--glass); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            padding: 12px 15px; border-radius: 14px; border: 1px solid var(--glass-border); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: center; min-width: 100px; flex: 1;
            transition: all 0.3s ease; 
        }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        
        .total-card { background: var(--dark); color: white; border-top: 4px solid var(--green); min-width: 140px; }
        .summary-val { font-size: 20px; font-weight: 800; display: block; margin: 4px 0; transition: color 0.3s; }
        .summary-label { font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .total-card .summary-label { color: #cbd5e1; }

        /* WARNING OVER 85% CLASS */
        .card-alert {
            background: linear-gradient(135deg, #ffe3e3 0%, #ffc9c9 100%) !important;
            border: 1px solid #ffa8a8 !important;
            border-top: 4px solid #fa5252 !important;
        }
        .card-alert .summary-val { color: #c92a2a !important; }
        .card-alert .summary-label { color: #e03131 !important; }
        .card-alert small { color: #c92a2a !important; font-weight: 600; }

        /* Legend */
        .legend-panel { background: white; padding: 12px 15px; border-radius: 12px; margin-bottom: 15px; display: flex; gap: 15px; flex-wrap: wrap; font-size: 11px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .legend-item { display: flex; align-items: center; gap: 6px; font-weight: 600; color: #475569; }
        .l-box { width: 14px; height: 14px; border-radius: 4px; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1); }

        /* Tabs */
        .tabs { display: flex; gap: 6px; margin-bottom: 20px; background: #e2e8f0; padding: 6px; border-radius: 12px; width: fit-content; }
        .tab-btn { padding: 8px 18px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; background: transparent; color: #64748b; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-family: 'Inter', sans-serif; }
        .tab-btn:hover { color: var(--primary); }
        .tab-btn.active { background: white; color: var(--primary); box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

        /* Map UI */
        .lorong-section { background: white; padding: 15px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px; overflow-x: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: 0.3s; }
        .lorong-section:hover { box-shadow: 0 8px 15px rgba(0,0,0,0.04); }
        .bin-grid { display: grid; grid-template-columns: repeat(25, 52px); gap: 4px; width: fit-content; }
        .bin-box { width: 52px; height: 52px; background: #fff; border: 1px solid #cbd5e1; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; font-size: 9px; border-radius: 8px; transition: all 0.2s ease; position: relative; font-family: 'JetBrains Mono', monospace;}
        .bin-box:hover { transform: scale(1.08); z-index: 10; box-shadow: 0 8px 20px rgba(0,0,0,0.12); border-color: transparent; }
        .bin-header, .vert-label { background: var(--dark) !important; color: white !important; font-weight: 700; font-family: 'Inter', sans-serif; letter-spacing: 0.5px; border: none;}
        
        /* Pastel Bin Colors */
        .is-expired { background: var(--expired) !important; color: #ff8787 !important; border: 2px solid #fa5252 !important; }
        .is-warning { background: var(--warning) !important; color: #d9480f !important; border: 1px solid #f76707 !important; }
        .occ-red { background: var(--red) !important; color: #822 !important; border-color: #ff6b6b !important; } 
        .occ-purple { background: var(--purple) !important; color: #305 !important; border-color: #9775fa !important;} 
        .occ-green { background: var(--green) !important; color: #153 !important; border-color: #51cf66 !important;}
        .occ-orange { background: var(--orange) !important; color: #630 !important; border-color: #ff922b !important;}

        .highlight-find, .highlight-pick { outline: 3px solid var(--primary) !important; z-index: 100 !important; animation: pulse-ring 1s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse-ring { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; transform: scale(0.95); } }

        .panel { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .btn { padding: 10px 16px; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; color: white; font-size: 11px; transition: all 0.2s ease; letter-spacing: 0.5px;}
        .btn:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn:active { transform: scale(0.96); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        th { background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;}
        tr:hover td { background: #f8fafc; transition: 0.2s; }
        .resume-row { background: #f1f5f9; font-weight: 800; color: var(--primary); }
        .resume-row:hover td { background: #f1f5f9; }

        input, textarea { font-family: 'Inter', sans-serif; font-size: 12px; outline: none; transition: 0.3s; }
        input:focus, textarea:focus { border-color: var(--primary) !important; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }

        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { background: white; margin: 4% auto; padding: 25px; width: 85%; border-radius: 20px; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.2); }
    </style>
</head>
<body onload="initApp()">

<div class="summary-container" id="dashSummary">
    <div class="summary-card total-card" id="totalCard">
        <span class="summary-label">UTILISASI TOTAL</span>
        <span class="summary-val" id="totalPct">0%</span>
        <div style="font-size:9px; margin-top:5px;">ISI: <b id="totalFill">0</b> PLT | KOS: <b id="totalEmpty">0</b> PLT</div>
    </div>
</div>

<div class="legend-panel">
    <div class="legend-item"><div class="l-box" style="background: var(--purple);"></div> Multi Material</div>
    <div class="legend-item"><div class="l-box" style="background: var(--red);"></div> Penuh (>700 KG)</div>
    <div class="legend-item"><div class="l-box" style="background: var(--green);"></div> Normal (>400 KG)</div>
    <div class="legend-item"><div class="l-box" style="background: var(--orange);"></div> Low (<400 KG)</div>
    <div class="legend-item"><div class="l-box" style="background: var(--expired); border: 1px solid #fa5252;"></div> Expired</div>
    <div class="legend-item"><div class="l-box" style="background: var(--warning); border: 1px solid #f76707;"></div> Warning (30d)</div>
</div>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event, 'tabMap')">📍 PETA GUDANG</button>
    <button class="tab-btn" onclick="openTab(event, 'tabPicking')">📋 PICKING LIST</button>
    <button class="tab-btn" onclick="openTab(event, 'tabAnalisa')">📊 ANALISA MATERIAL</button>
    <button class="tab-btn" onclick="openTab(event, 'tabLaporan')">🚨 LAPORAN STOK</button>
</div>

<div id="tabMap" class="tab-content">
    <div class="panel">
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px;">
            <button class="btn" style="background:var(--orange);" onclick="toggleMode()">⚙️ EDIT MODE</button>
            <button class="btn" style="background:var(--primary);" onclick="syncServer()">🚀 SYNC SERVER</button>
            <button class="btn" style="background:#10b981;" onclick="processSAP()">⚡ PROSES SAP</button>
            <button class="btn" style="background:#64748b;" onclick="exportJSON()">📤 EXPORT JSON</button>
            <button class="btn" style="background:#334155;" onclick="document.getElementById('fileRestore').click()">📥 RESTORE</button>
            <input type="file" id="fileRestore" style="display:none" onchange="importJSON(event)">
        </div>
        <input type="text" id="matSearch" placeholder="🔍 Cari Kode atau Nama Material..." onkeyup="searchMap()" style="width:100%; padding:12px 15px; border-radius:10px; border:1px solid #cbd5e1; margin-bottom:12px; box-sizing: border-box;">
        <textarea id="excelInput" placeholder="📋 Paste data SAP (Material, Desc, Location, Batch, Unrestricted, Insp, Block)..." style="width:100%; height:50px; border-radius:10px; border:1px solid #cbd5e1; padding:12px; box-sizing: border-box;"></textarea>
    </div>
    <div id="warehouse"></div>
</div>

<div id="tabPicking" class="tab-content" style="display:none">
    <div class="panel">
        <h4 style="margin-top:0;">📋 FEFO Picking List (Excel Friendly)</h4>
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; margin-bottom:15px;">
            <input type="text" id="pickSearch" placeholder="Filter Material..." onkeyup="calculateAll()" style="padding:10px; border-radius:8px; border:1px solid #cbd5e1;">
            <input type="number" id="targetQty" placeholder="Target KG..." onkeyup="calculateAll()" style="padding:10px; border-radius:8px; border:1px solid #cbd5e1;">
            <input type="text" id="allocFilter" placeholder="Filter Alokasi..." onkeyup="calculateAll()" style="padding:10px; border-radius:8px; border:1px solid #cbd5e1;">
        </div>
        <div id="pickBody"></div>
    </div>
</div>

<div id="tabAnalisa" class="tab-content" style="display:none">
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        <div class="panel"><h4 style="margin-top:0;">📈 Top 10 Material</h4><div id="top10Body"></div></div>
        <div class="panel">
            <h4 style="margin-top:0;">⚙️ Config Batch</h4>
            <div style="margin-bottom:12px; padding:12px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
                <label style="font-size:10px; font-weight:800; color:var(--primary);">NAMA DEFAULT:</label>
                <input type="text" id="defaultAllocName" style="width:100%; padding:8px; margin-top:6px; border-radius:6px; border:1px solid #cbd5e1; box-sizing: border-box;" onchange="defaultAlloc=this.value.toUpperCase()">
            </div>
            <button class="btn" style="background:var(--primary); margin-bottom:10px;" onclick="addBatchRow()">+ Tambah Rule</button>
            <button class="btn" style="background:#10b981; margin-bottom:10px;" onclick="syncServer()">💾 Simpan Database</button>
            <div id="batchDBBody"></div>
        </div>
    </div>
    <div class="panel"><h4 style="margin-top:0;">📦 Resume Alokasi</h4><div id="batchResumBody"></div></div>
</div>

<div id="tabLaporan" class="tab-content" style="display:none">
    <div class="panel" style="border-top:5px solid #0f172a"><h4 style="margin-top:0;">🚫 Blocked Stock Report</h4><div id="blockBody"></div></div>
    <div class="panel" style="border-top:5px solid var(--red)"><h4 style="margin-top:0;">🚨 Expired & Warning Report</h4><div id="expBody"></div></div>
    <div class="panel" style="border-top:5px solid var(--primary)"><h4 style="margin-top:0;">🔬 Quality Inspection Report</h4><div id="inspBody"></div></div>
</div>

<div id="detailModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-top:0; color: var(--dark);"></h3><hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 15px;">
        <div id="modalTableContainer"></div>
        <button class="btn" style="background:var(--dark); width:100%; margin-top:20px; padding:12px;" onclick="closeModal()">TUTUP</button>
    </div>
</div>

<script>
let appData = <?php echo json_encode($server_data); ?>;
let layout = appData.layout || {};
let headers = appData.headers || {};
let inventory = appData.inventory || {};
let vert = appData.vert || {};
let batchDB = appData.batchDB || [];
let defaultAlloc = appData.defaultAlloc || 'PRODUKSI';

let editMode = false, recommendedSlocs = [];
const TODAY = new Date();

function initApp() {
    document.getElementById('defaultAllocName').value = defaultAlloc;
    calculateAll();
}

function openTab(e, name) {
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(name).style.display = "block";
    e.currentTarget.classList.add("active");
    calculateAll();
}

function calculateAll() {
    try { renderPickList(); } catch(e) {}
    try { renderMap(); } catch(e) {}
    try { renderAnalisa(); } catch(e) {}
    try { renderLaporan(); } catch(e) {}
    try { renderBatchDB(); } catch(e) {}
    try { renderTop10(); } catch(e) {}
}

// --- RENDER MAP DENGAN FITUR >85% WARNING ALERT ---
function renderMap() {
    const container = document.getElementById('warehouse');
    const dash = document.getElementById('dashSummary');
    if(!container) return;
    container.innerHTML = '';
    
    Array.from(dash.children).forEach(c => { if(c.id !== 'totalCard') c.remove(); });

    let gFill = 0, gTot = 0; 

    for (let l = 1; l <= 7; l++) {
        let lFill = 0, lTot = 0;
        const section = document.createElement('div'); section.className = 'lorong-section';
        section.innerHTML = `<h4 style="margin-top:0; color: #475569;">LORONG H${l}</h4>`;
        const grid = document.createElement('div'); grid.className = 'bin-grid';
        
        grid.appendChild(createBox('', 'bin-header vert-label'));
        for(let c=1; c<=24; c++) {
            let k = `L${l}-C${c}`;
            grid.appendChild(createBox(headers[k] || String.fromCharCode(64+c), 'bin-header', k, 'header'));
        }

        for (let r = 1; r <= 10; r++) {
            let vK = `L${l}-V${r}`;
            grid.appendChild(createBox(vert[vK] || r, 'vert-label', vK, 'vert'));
            
            for (let c = 1; c <= 24; c++) {
                const bKey = `L${l}-R${r}-C${c}`, addr = layout[bKey] || '';
                const box = document.createElement('div'); box.className = 'bin-box'; box.id = `BOX-${bKey}`;
                
                if (editMode) {
                    const i = document.createElement('input'); i.value = addr; i.style="width:85%; border:none; text-align:center; font-size:9px; background:transparent; color:#000; font-weight: bold;";
                    i.onchange = (e) => layout[bKey] = String(e.target.value).toUpperCase(); 
                    box.appendChild(i);
                } else if(addr) {
                    lTot++; gTot++; 
                    const items = inventory[addr] || [];
                    const weight = items.reduce((s,i) => s + (parseFloat(i.qty)||0) + (parseFloat(i.qInsp)||0) + (parseFloat(i.qBlock)||0), 0);
                    
                    if(weight > 0) {
                        lFill++; gFill++; 
                        let status = 'normal';
                        items.forEach(it => {
                            let s = getEDStatus(it.batch);
                            if(s === 'expired') status = 'expired';
                            else if(s === 'warning' && status !== 'expired') status = 'warning';
                        });
                        
                        if(status === 'expired') box.classList.add('is-expired');
                        else if(status === 'warning') box.classList.add('is-warning');
                        else if(new Set(items.map(it => it.code)).size > 1) box.classList.add('occ-purple');
                        else if(weight > 700) box.classList.add('occ-red');
                        else if(weight < 400) box.classList.add('occ-orange');
                        else box.classList.add('occ-green');
                    }
                    
                    if(recommendedSlocs.includes(addr)) box.classList.add('highlight-pick');
                    
                    box.onclick = () => showDetail(addr);
                    box.innerHTML = `<span style="font-size:8px; color:#64748b; font-weight:800;">${addr}</span><b style="font-size:10px">${weight > 0 ? Math.round(weight) : ''}</b>`;
                } else { 
                    box.style.opacity = "0.2"; 
                }
                grid.appendChild(box);
            }
        }
        section.appendChild(grid); container.appendChild(section);
        
        const pct = lTot > 0 ? Math.round((lFill/lTot)*100) : 0;
        const card = document.createElement('div'); 
        
        // Logika > 85% untuk warna merah peringatan
        const alertClass = pct > 85 ? 'card-alert' : '';
        card.className = `summary-card ${alertClass}`;
        
        card.innerHTML = `<span class="summary-label">H${l} OCC</span><div class="summary-val">${pct}%</div><small style="color:#64748b;">${lFill}/${lTot}</small>`;
        dash.appendChild(card);
    }
    
    // --- ESTIMASI PALET OPEN SLOC 1002 & 1003 ---
    let w1002 = 0, w1003 = 0;
    Object.keys(inventory).forEach(sloc => {
        if(sloc === '1002') {
            w1002 = inventory[sloc].reduce((s,i) => s + (parseFloat(i.qty)||0) + (parseFloat(i.qInsp)||0) + (parseFloat(i.qBlock)||0), 0);
        } else if(sloc === '1003') {
            w1003 = inventory[sloc].reduce((s,i) => s + (parseFloat(i.qty)||0) + (parseFloat(i.qInsp)||0) + (parseFloat(i.qBlock)||0), 0);
        }
    });

    let pal1002 = w1002 > 0 ? Math.ceil(w1002 / 350) : 0;
    let pal1003 = w1003 > 0 ? Math.ceil(w1003 / 350) : 0;
    let totalOpenPallets = pal1002 + pal1003;

    // RUMUS KAPASITAS (Anti minus)
    let totalTerisi = gFill + totalOpenPallets;
    let totalKosong = gTot - totalTerisi;
    if(totalKosong < 0) totalKosong = 0; 
    let persentaseTotal = gTot > 0 ? Math.round((totalTerisi / gTot) * 100) : 0;

    if(w1002 > 0) {
        const c1 = document.createElement('div'); c1.className = 'summary-card';
        c1.style.borderTop = '4px solid var(--purple)';
        c1.innerHTML = `<span class="summary-label">SLOC 1002</span><div class="summary-val">${pal1002} <span style="font-size:10px">PLT</span></div><small style="color:#64748b;">${Math.round(w1002)} KG</small>`;
        dash.appendChild(c1);
    }
    if(w1003 > 0) {
        const c2 = document.createElement('div'); c2.className = 'summary-card';
        c2.style.borderTop = '4px solid var(--orange)';
        c2.innerHTML = `<span class="summary-label">SLOC 1003</span><div class="summary-val">${pal1003} <span style="font-size:10px">PLT</span></div><small style="color:#64748b;">${Math.round(w1003)} KG</small>`;
        dash.appendChild(c2);
    }

    // Logika Alert > 85% untuk Kartu Total Utama
    const totalCardEl = document.getElementById('totalCard');
    if (persentaseTotal > 85) {
        totalCardEl.classList.add('card-alert');
        totalCardEl.style.background = 'linear-gradient(135deg, #ffe3e3 0%, #ffc9c9 100%)';
        totalCardEl.style.color = '#c92a2a';
    } else {
        totalCardEl.classList.remove('card-alert');
        totalCardEl.style.background = 'var(--dark)';
        totalCardEl.style.color = 'white';
        totalCardEl.style.borderTop = '4px solid var(--green)';
    }

    document.getElementById('totalPct').innerText = persentaseTotal + '%';
    document.getElementById('totalFill').innerText = totalTerisi;
    document.getElementById('totalEmpty').innerText = totalKosong;
}

// --- UTILS ---
function parseBatchDate(batch) {
    if(!batch) return new Date(2099, 0, 1);
    let nums = String(batch).replace(/\D/g, '');
    if(nums.length < 6) return new Date(2099, 0, 1);
    let d = parseInt(nums.substring(0,2)), m = parseInt(nums.substring(2,4)) - 1, y = parseInt("20" + nums.substring(4,6));
    let dt = new Date(y, m, d);
    return isNaN(dt.getTime()) ? new Date(2099, 0, 1) : dt;
}

function getEDStatus(batch) {
    let dt = parseBatchDate(batch);
    let diff = (dt - TODAY) / 86400000;
    if(diff < 0) return 'expired';
    if(diff <= 30) return 'warning';
    return 'normal';
}

function getAlloc(b) {
    if(!b) return defaultAlloc;
    let sortedRules = [...batchDB].sort((a,b) => String(b.c).length - String(a.c).length);
    for(let r of sortedRules) { 
        if(r.c && String(b).toUpperCase().includes(String(r.c).toUpperCase())) return r.t; 
    }
    return defaultAlloc;
}

function createBox(v, c, k, t) {
    const b = document.createElement('div'); b.className = 'bin-box ' + c;
    if(editMode && k) {
        const i = document.createElement('input'); i.value = v; i.style="width:80%; border:none; text-align:center; background:transparent; color:white; font-weight:bold;";
        i.onchange = (e) => { 
            let val = String(e.target.value).toUpperCase();
            if(t==='vert') vert[k] = val; 
            else if(t==='header') headers[k] = val; 
        };
        b.appendChild(i);
    } else b.innerText = v;
    return b;
}

// TULISAN MERAH JIKA EXPIRED DI POPUP
function showDetail(addr) {
    const items = inventory[addr]; if(!items) return;
    document.getElementById('modalTitle').innerText = "Detail Lokasi: " + addr;
    let h = "<table><thead><tr><th>Material (Kode | Nama)</th><th>Batch</th><th>Qty</th><th>Alokasi</th></tr></thead><tbody>";
    items.forEach(it => {
        let q = (parseFloat(it.qty)||0) + (parseFloat(it.qInsp)||0) + (parseFloat(it.qBlock)||0);
        let isExp = getEDStatus(it.batch) === 'expired';
        let textStyle = isExp ? 'color: var(--red); font-weight: bold;' : '';
        h += `<tr style="${textStyle}"><td>${it.code} | ${it.desc}</td><td>${it.batch}</td><td><b>${Math.round(q)}</b></td><td>${getAlloc(it.batch)}</td></tr>`;
    });
    document.getElementById('modalTableContainer').innerHTML = h + "</tbody></table>";
    document.getElementById('detailModal').style.display = 'block';
}

// TULISAN MERAH JIKA EXPIRED DI LAPORAN
function renderLaporan() {
    let expH = "", inspH = "", blockH = "";
    let qExp = 0, qInsp = 0, qBlock = 0;
    
    Object.keys(inventory).forEach(s => {
        inventory[s].forEach(it => {
            let totalQty = (parseFloat(it.qty)||0) + (parseFloat(it.qInsp)||0) + (parseFloat(it.qBlock)||0);
            let st = getEDStatus(it.batch);
            
            if(st !== 'normal') { 
                let textStyle = st === 'expired' ? 'color: var(--red); font-weight: bold;' : 'color: #d9480f;';
                expH += `<tr style="${textStyle}"><td>${s}</td><td>${it.code} | ${it.desc}</td><td>${it.batch}</td><td>${Math.round(totalQty)}</td><td><b>${st.toUpperCase()}</b></td></tr>`; 
                qExp += totalQty; 
            }
            if((parseFloat(it.qInsp)||0) > 0) { 
                inspH += `<tr><td>${s}</td><td>${it.code} | ${it.desc}</td><td>${it.batch}</td><td>${Math.round(it.qInsp)}</td></tr>`; 
                qInsp += parseFloat(it.qInsp); 
            }
            if((parseFloat(it.qBlock)||0) > 0) { 
                blockH += `<tr><td>${s}</td><td>${it.code} | ${it.desc}</td><td>${it.batch}</td><td>${Math.round(it.qBlock)}</td></tr>`; 
                qBlock += parseFloat(it.qBlock); 
            }
        });
    });
    
    const wrap = (h, q, lbl) => `<table><thead><tr><th>Sloc</th><th>Material</th><th>Batch</th><th>Qty</th>${lbl==='Exp'?'<th>Ket</th>':''}</tr></thead><tbody>${h}<tr class="resume-row"><td colspan="3" style="text-align:right">TOTAL</td><td colspan="2">${Math.round(q)} KG</td></tr></tbody></table>`;
    
    document.getElementById('expBody').innerHTML = wrap(expH, qExp, 'Exp');
    document.getElementById('inspBody').innerHTML = wrap(inspH, qInsp, 'Insp');
    document.getElementById('blockBody').innerHTML = wrap(blockH, qBlock, 'Block');
}

function renderAnalisa() {
    let resum = {};
    Object.values(inventory).flat().forEach(it => {
        let al = getAlloc(it.batch);
        if(!resum[al]) resum[al] = {q: 0, items: {}};
        let v = (parseFloat(it.qty)||0) + (parseFloat(it.qInsp)||0) + (parseFloat(it.qBlock)||0);
        resum[al].q += v;
        let key = `${it.code} | ${it.desc}`;
        resum[al].items[key] = (resum[al].items[key] || 0) + v;
    });
    let h = "<table><thead><tr><th>Alokasi</th><th>Total KG</th><th>Rincian Material</th></tr></thead><tbody>";
    Object.entries(resum).forEach(([al, d]) => {
        let det = Object.entries(d.items).sort((a,b)=>b[1]-a[1]).map(x => `• ${x[0]} (<b>${Math.round(x[1])}</b>)`).join('<br>');
        h += `<tr><td style="vertical-align:top"><b>${al}</b></td><td style="vertical-align:top">${Math.round(d.q)}</td><td><small style="color:#475569;">${det}</small></td></tr>`;
    });
    document.getElementById('batchResumBody').innerHTML = h + "</tbody></table>";
}

// TULISAN MERAH JIKA EXPIRED DI PICKING LIST
function renderPickList() {
    const q = String(document.getElementById('pickSearch')?.value || "").toUpperCase();
    const target = parseFloat(document.getElementById('targetQty')?.value) || 0;
    const allocF = String(document.getElementById('allocFilter')?.value || "").toUpperCase();
    recommendedSlocs = [];
    
    let list = [];
    Object.keys(inventory).forEach(s => inventory[s].forEach(it => {
        let al = getAlloc(it.batch);
        let mStr = String(it.code + " " + it.desc).toUpperCase();
        if((!q || mStr.includes(q)) && (!allocF || al.includes(allocF))) {
            list.push({...it, sloc: s, al: al, exp: parseBatchDate(it.batch), rawBatch: it.batch});
        }
    }));
    
    list.sort((a,b) => a.exp - b.exp);
    let rt = 0;
    let h = "<table><thead><tr><th>ED</th><th>Batch</th><th>Sloc</th><th>Material</th><th>Qty</th><th>Akum</th><th>Alokasi</th></tr></thead><tbody>";
    list.forEach(p => {
        let w = (parseFloat(p.qty)||0) + (parseFloat(p.qInsp)||0) + (parseFloat(p.qBlock)||0);
        rt += w;
        let isRec = (target > 0 && (rt - w) < target);
        if(isRec) recommendedSlocs.push(p.sloc);
        
        let isExp = getEDStatus(p.rawBatch) === 'expired';
        let bgStyle = isRec ? 'background:#dcfce7; font-weight:bold;' : '';
        let txtStyle = isExp ? 'color: var(--red); font-weight:bold;' : '';
        
        h += `<tr style="${bgStyle} ${txtStyle}"><td>${p.exp.toLocaleDateString('id-ID')}</td><td>${p.rawBatch}</td><td>${p.sloc}</td><td>${p.code} | ${p.desc}</td><td>${Math.round(w)}</td><td>${Math.round(rt)}</td><td>${p.al}</td></tr>`;
    });
    const pb = document.getElementById('pickBody');
    if(pb) pb.innerHTML = h + "</tbody></table>";
}

function renderTop10() {
    let m = {}; 
    Object.values(inventory).flat().forEach(it => { 
        if(!m[it.code]) m[it.code] = {n: it.desc, q: 0}; 
        m[it.code].q += (parseFloat(it.qty)||0) + (parseFloat(it.qInsp)||0) + (parseFloat(it.qBlock)||0); 
    });
    let s = Object.entries(m).sort((a,b) => b[1].q - a[1].q).slice(0, 10);
    document.getElementById('top10Body').innerHTML = "<table><thead><tr><th>Material</th><th>Total Qty</th></tr></thead><tbody>" + s.map(x => `<tr><td>${x[0]} | ${x[1].n}</td><td><b>${Math.round(x[1].q)} KG</b></td></tr>`).join('') + "</tbody></table>";
}

function renderBatchDB() {
    document.getElementById('batchDBBody').innerHTML = batchDB.map((it, i) => `<div style="display:flex; gap:8px; margin-bottom:8px;"><input value="${it.c}" onchange="batchDB[${i}].c=this.value.toUpperCase()" style="flex:1; padding:8px; border-radius:6px; border:1px solid #cbd5e1;" placeholder="Keyword"><input value="${it.t}" onchange="batchDB[${i}].t=this.value.toUpperCase()" style="flex:1; padding:8px; border-radius:6px; border:1px solid #cbd5e1;" placeholder="Alokasi"><button onclick="batchDB.splice(${i},1);calculateAll()" style="background:#fa5252; color:white; border:none; padding:8px 12px; border-radius:6px; cursor:pointer;">X</button></div>`).join('');
}

// --- FILE & DATA MANAGEMENT ---
function processSAP() {
    const raw = document.getElementById('excelInput').value.trim(); if(!raw) return;
    const lines = raw.split("\n");
    const h = lines[0].toLowerCase().split("\t").map(x => x.trim());
    
    let mI = h.indexOf('material'); if(mI===-1) mI = 0;
    let dI = h.indexOf('material description'); if(dI===-1) dI = 1;
    let sI = h.findIndex(x => x.includes('location') || x.includes('sloc')); if(sI===-1) sI = 2;
    let bI = h.indexOf('batch'); if(bI===-1) bI = 3;
    let uI = h.indexOf('unrestricted'); if(uI===-1) uI = 4;
    let iI = h.findIndex(x => x.includes('insp')); if(iI===-1) iI = 5;
    let blI = h.findIndex(x => x.includes('block')); if(blI===-1) blI = 6;
    
    inventory = {};
    for (let i = 1; i < lines.length; i++) {
        const c = lines[i].split("\t"); if(!c[mI]) continue;
        const sloc = c[sI]?.trim();
        const num = (v) => parseFloat(String(v||"0").replace(/\./g, '').replace(',', '.')) || 0;
        
        if(!inventory[sloc]) inventory[sloc] = [];
        inventory[sloc].push({ 
            code: String(c[mI]).trim(), 
            desc: c[dI]||'-', 
            batch: String(c[bI])||'-', 
            qty: num(c[uI]), 
            qInsp: num(c[iI]), 
            qBlock: num(c[blI]) 
        });
    }
    calculateAll(); 
    alert("Data SAP Berhasil Diproses! Klik 'SYNC SERVER' untuk menyimpan.");
}

function syncServer() {
    const payload = JSON.stringify({layout, headers, inventory, vert, batchDB, defaultAlloc});
    const fd = new FormData(); fd.append('action', 'sync_server'); fd.append('payload', payload);
    fetch(window.location.href, { method: 'POST', body: fd }).then(r => r.text()).then(t => alert("✅ " + t));
}

function importJSON(e) {
    const reader = new FileReader();
    reader.onload = (ev) => {
        try {
            const d = JSON.parse(ev.target.result);
            layout = d.layout || {}; headers = d.headers || {}; inventory = d.inventory || {}; 
            vert = d.vert || {}; batchDB = d.batchDB || []; defaultAlloc = d.defaultAlloc || 'PRODUKSI';
            calculateAll(); alert("Data JSON Berhasil Dimuat!");
        } catch(err) { alert("Format File JSON Salah!"); }
    };
    reader.readAsText(e.target.files[0]);
}

function exportJSON() {
    const b = new Blob([JSON.stringify({layout, headers, inventory, vert, batchDB, defaultAlloc})], {type: "application/json"});
    const a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = 'warehouse_backup.json'; a.click();
}

function searchMap() {
    const q = String(document.getElementById('matSearch').value).toUpperCase();
    document.querySelectorAll('.bin-box').forEach(b => b.classList.remove('highlight-find'));
    if(!q) return;
    Object.keys(inventory).forEach(s => {
        if(inventory[s].some(it => String(it.code).toUpperCase().includes(q) || String(it.desc).toUpperCase().includes(q))) {
            Object.keys(layout).forEach(k => { 
                if(layout[k] === s) {
                    const bx = document.getElementById(`BOX-${k}`);
                    if(bx) bx.classList.add('highlight-find');
                }
            });
        }
    });
}

function toggleMode() { editMode = !editMode; calculateAll(); }
function closeModal() { document.getElementById('detailModal').style.display = 'none'; }
function addBatchRow() { batchDB.push({c:'', t:''}); renderBatchDB(); }
</script>
</body>
</html>