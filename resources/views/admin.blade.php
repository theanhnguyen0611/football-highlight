<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin — BolaReel</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg: #0f0f13;
    --surface: #18181f;
    --border: #2a2a35;
    --muted: #555568;
    --text: #e8e8f0;
    --sub: #8888a8;
    --accent: #ef4444;
    --accent-dim: rgba(239,68,68,.15);
    --green: #22c55e;
    --green-dim: rgba(34,197,94,.15);
    --blue: #3b82f6;
    --blue-dim: rgba(59,130,246,.15);
    --yellow: #eab308;
    --radius: 10px;
}

body { background: var(--bg); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; min-height: 100vh; }

/* ── Login ── */
.login-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
.login-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 40px 36px; width: 100%; max-width: 380px; }
.login-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
.login-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent); }
.login-title { font-size: 18px; font-weight: 700; letter-spacing: -.3px; }
.login-sub { font-size: 12px; color: var(--sub); margin-top: 2px; }
.login-label { display: block; font-size: 12px; color: var(--sub); margin-bottom: 6px; }
.login-input { width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; color: var(--text); font-size: 14px; outline: none; transition: border-color .15s; }
.login-input:focus { border-color: var(--accent); }
.login-btn { width: 100%; margin-top: 14px; background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 11px; font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity .15s; }
.login-btn:hover { opacity: .85; }

/* ── App shell ── */
#app { display: none; max-width: 960px; margin: 0 auto; padding: 28px 20px 60px; }
.topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
.topbar-logo { display: flex; align-items: center; gap: 8px; }
.topbar-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); }
.topbar-title { font-size: 16px; font-weight: 700; }
.topbar-label { font-size: 11px; background: var(--accent-dim); color: var(--accent); padding: 2px 8px; border-radius: 20px; font-weight: 600; }
.logout-btn { background: none; border: 1px solid var(--border); border-radius: 7px; color: var(--sub); padding: 6px 14px; font-size: 12px; cursor: pointer; transition: all .15s; }
.logout-btn:hover { border-color: var(--accent); color: var(--accent); }

/* ── Search ── */
.search-row { display: flex; gap: 10px; margin-bottom: 20px; }
.search-input { flex: 1; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; color: var(--text); font-size: 14px; outline: none; transition: border-color .15s; }
.search-input:focus { border-color: var(--accent); }
.search-btn { background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; }
.search-btn:hover { opacity: .85; }

/* ── Match list ── */
.match-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 28px; }
.match-row { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 16px; display: flex; align-items: center; gap: 14px; cursor: pointer; transition: border-color .15s; }
.match-row:hover, .match-row.selected { border-color: var(--accent); }
.match-row.selected { background: rgba(239,68,68,.05); }
.match-teams { flex: 1; font-weight: 600; font-size: 14px; }
.match-meta { font-size: 12px; color: var(--sub); margin-top: 3px; }
.badges { display: flex; gap: 6px; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end; }
.match-badge { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; white-space: nowrap; }
.badge-ready { background: var(--green-dim); color: var(--green); }
.badge-pending { background: rgba(234,179,8,.15); color: var(--yellow); }
.badge-none { background: var(--border); color: var(--sub); }
.badge-hl { background: var(--blue-dim); color: var(--blue); }
.empty-state { text-align: center; color: var(--sub); padding: 40px 0; }

/* ── Upload panels side by side ── */
.panels-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 700px) { .panels-grid { grid-template-columns: 1fr; } }

.panel-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.panel-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.panel-dot.hl { background: var(--blue); }
.panel-dot.fm { background: var(--accent); }

.panel-match-name { font-size: 13px; color: var(--sub); margin-bottom: 16px; font-weight: 400; text-transform: none; letter-spacing: 0; }

.upload-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px;
    display: none;
}
.upload-panel.hl-panel { border-color: var(--blue); }
.upload-panel.fm-panel { border-color: var(--accent); }

/* ── Tabs ── */
.tabs { display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; margin-bottom: 16px; }
.tab { flex: 1; padding: 8px; text-align: center; font-size: 12px; font-weight: 600; cursor: pointer; background: var(--bg); color: var(--sub); border: none; transition: all .15s; }
.tab.active { background: var(--accent); color: #fff; }
.tab-content { display: none; }
.tab-content.active { display: block; }

/* Blue accent for highlight panel */
.hl-panel .tab.active { background: var(--blue); }
.hl-panel .search-btn { background: var(--blue); }
.hl-panel .submit-btn { background: var(--blue); }
.hl-panel .field-input:focus { border-color: var(--blue); }
.hl-panel .upload-drop:hover, .hl-panel .upload-drop.drag-over { border-color: var(--blue); background: var(--blue-dim); }

/* ── Fields ── */
.field-label { font-size: 12px; color: var(--sub); margin-bottom: 6px; display: block; }
.field-input { width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; color: var(--text); font-size: 14px; outline: none; transition: border-color .15s; }
.field-input:focus { border-color: var(--accent); }

/* ── Drop zone ── */
.upload-drop { border: 2px dashed var(--border); border-radius: 10px; padding: 28px 16px; text-align: center; cursor: pointer; transition: border-color .15s, background .15s; position: relative; }
.upload-drop:hover, .upload-drop.drag-over { border-color: var(--accent); background: var(--accent-dim); }
.upload-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload-icon { font-size: 24px; margin-bottom: 8px; }
.upload-hint { font-size: 12px; color: var(--sub); }
.upload-hint strong { color: var(--text); }
.upload-hint small { display: block; font-size: 11px; margin-top: 3px; }
.file-chosen { margin-top: 10px; font-size: 12px; color: var(--green); font-weight: 600; }

/* ── Submit ── */
.submit-btn { width: 100%; margin-top: 14px; background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 11px; font-size: 13px; font-weight: 700; cursor: pointer; transition: opacity .15s; }
.submit-btn:hover { opacity: .85; }
.submit-btn:disabled { opacity: .45; cursor: not-allowed; }

/* ── Status ── */
.status-box { margin-top: 14px; padding: 12px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; display: none; }
.status-box.info { background: var(--accent-dim); color: var(--accent); }
.status-box.success { background: var(--green-dim); color: var(--green); }
.status-box.error { background: var(--accent-dim); color: var(--accent); }
.status-sub { font-size: 12px; font-weight: 400; margin-top: 3px; opacity: .8; }

/* ── Pagination ── */
.pagination { display: flex; gap: 8px; justify-content: center; margin-top: 16px; }
.page-btn { background: var(--surface); border: 1px solid var(--border); border-radius: 7px; color: var(--text); padding: 6px 14px; font-size: 12px; cursor: pointer; transition: all .15s; }
.page-btn:hover, .page-btn.active { border-color: var(--accent); color: var(--accent); }

.section-label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: var(--sub); font-weight: 600; margin-bottom: 10px; }
.panels-match-name { font-size: 14px; font-weight: 700; margin-bottom: 14px; display: none; }
.panels-match-name span { color: var(--sub); font-weight: 400; font-size: 12px; }
</style>
</head>
<body>

<!-- LOGIN -->
<div class="login-wrap" id="loginWrap">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-dot"></div>
            <div>
                <div class="login-title">BolaReel Admin</div>
                <div class="login-sub">Video Management Panel</div>
            </div>
        </div>
        <label class="login-label">Admin Key</label>
        <input type="password" id="keyInput" class="login-input" placeholder="Enter admin key..." />
        <button class="login-btn" onclick="login()">Enter</button>
    </div>
</div>

<!-- APP -->
<div id="app">
    <div class="topbar">
        <div class="topbar-logo">
            <div class="topbar-dot"></div>
            <span class="topbar-title">BolaReel</span>
            <span class="topbar-label">Admin</span>
        </div>
        <button class="logout-btn" onclick="logout()">Sign out</button>
    </div>

    <div class="search-row">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by team name..." onkeydown="if(event.key==='Enter')searchMatches()" />
        <button class="search-btn" onclick="searchMatches()">Search</button>
    </div>

    <div class="section-label" id="listLabel">Recent matches</div>
    <div class="match-list" id="matchList"></div>
    <div class="pagination" id="pagination"></div>

    <!-- Selected match name -->
    <div class="panels-match-name" id="panelsMatchName"></div>

    <!-- Two panels side by side -->
    <div class="panels-grid">

        <!-- HIGHLIGHT PANEL -->
        <div class="upload-panel hl-panel" id="hlPanel">
            <div class="panel-header">
                <div class="panel-dot hl"></div>
                Highlight Video
            </div>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('hl','file')">Upload File</button>
                <button class="tab" onclick="switchTab('hl','youtube')">YouTube URL</button>
            </div>

            <div class="tab-content active" id="hl-tabFile">
                <div class="upload-drop" id="hl-dropZone">
                    <input type="file" id="hl-fileInput" accept=".mp4,.mkv,.avi,.mov,.webm" onchange="onFileChosen('hl', this)" />
                    <div class="upload-icon">🎬</div>
                    <div class="upload-hint">
                        <strong>Click or drag & drop</strong>
                        <small>MP4, MKV, AVI, MOV, WEBM</small>
                    </div>
                </div>
                <div class="file-chosen" id="hl-fileChosen" style="display:none"></div>
            </div>

            <div class="tab-content" id="hl-tabYoutube">
                <label class="field-label">YouTube URL</label>
                <input type="url" id="hl-ytInput" class="field-input" placeholder="https://www.youtube.com/watch?v=..." />
            </div>

            <button class="submit-btn" id="hl-submitBtn" onclick="submitUpload('hl')">Set Highlight</button>
            <div class="status-box" id="hl-statusBox">
                <div id="hl-statusText"></div>
                <div class="status-sub" id="hl-statusSub"></div>
            </div>
        </div>

        <!-- FULL MATCH PANEL -->
        <div class="upload-panel fm-panel" id="fmPanel">
            <div class="panel-header">
                <div class="panel-dot fm"></div>
                Full Match Video
            </div>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('fm','file')">Upload File</button>
                <button class="tab" onclick="switchTab('fm','youtube')">YouTube URL</button>
            </div>

            <div class="tab-content active" id="fm-tabFile">
                <div class="upload-drop" id="fm-dropZone">
                    <input type="file" id="fm-fileInput" accept=".mp4,.mkv,.avi,.mov,.webm" onchange="onFileChosen('fm', this)" />
                    <div class="upload-icon">📼</div>
                    <div class="upload-hint">
                        <strong>Click or drag & drop</strong>
                        <small>MP4, MKV, AVI, MOV, WEBM</small>
                    </div>
                </div>
                <div class="file-chosen" id="fm-fileChosen" style="display:none"></div>
            </div>

            <div class="tab-content" id="fm-tabYoutube">
                <label class="field-label">YouTube URL</label>
                <input type="url" id="fm-ytInput" class="field-input" placeholder="https://www.youtube.com/watch?v=..." />
            </div>

            <button class="submit-btn" id="fm-submitBtn" onclick="submitUpload('fm')">Upload Full Match</button>
            <div class="status-box" id="fm-statusBox">
                <div id="fm-statusText"></div>
                <div class="status-sub" id="fm-statusSub"></div>
            </div>
        </div>

    </div>
</div>

<script>
let adminKey = '';
let currentMatchId = null;
let currentPage = 1;
let currentQuery = '';
let panelTabs = { hl: 'file', fm: 'file' };
let pollIntervals = { hl: null, fm: null };

// ── Auth ──────────────────────────────────────────────────
function login() {
    const key = document.getElementById('keyInput').value.trim();
    if (!key) return;
    adminKey = key;
    localStorage.setItem('fh_admin_key', key);
    showApp();
}
function logout() {
    localStorage.removeItem('fh_admin_key');
    adminKey = '';
    document.getElementById('loginWrap').style.display = 'flex';
    document.getElementById('app').style.display = 'none';
    clearInterval(pollIntervals.hl);
    clearInterval(pollIntervals.fm);
}
function showApp() {
    document.getElementById('loginWrap').style.display = 'none';
    document.getElementById('app').style.display = 'block';
    loadMatches(1, '');
}
document.getElementById('keyInput').addEventListener('keydown', e => { if (e.key === 'Enter') login(); });

// ── Matches ───────────────────────────────────────────────
async function loadMatches(page, q) {
    currentPage = page;
    currentQuery = q;
    const url = `/api/matches?per_page=10&page=${page}${q ? '&q=' + encodeURIComponent(q) : ''}`;
    const res = await fetch(url);
    const json = await res.json();
    const matches = json.data || [];
    const meta = json.meta || {};

    document.getElementById('listLabel').textContent = q
        ? `Results for "${q}" (${meta.total || 0})`
        : 'Recent matches';

    const list = document.getElementById('matchList');
    if (!matches.length) {
        list.innerHTML = '<div class="empty-state">No matches found.</div>';
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    // Fetch both highlight + full_match status for each match
    const [hlStatuses, fmStatuses] = await Promise.all([
        Promise.all(matches.map(m =>
            fetch(`/api/admin/full-match/${m.id}?type=highlight`, { headers: { 'X-Admin-Key': adminKey } })
                .then(r => r.json()).catch(() => ({ status: 'none' }))
        )),
        Promise.all(matches.map(m =>
            fetch(`/api/admin/full-match/${m.id}?type=full_match`, { headers: { 'X-Admin-Key': adminKey } })
                .then(r => r.json()).catch(() => ({ status: 'none' }))
        )),
    ]);

    list.innerHTML = matches.map((m, i) => {
        const hl = hlStatuses[i]?.status || 'none';
        const fm = fmStatuses[i]?.status || 'none';
        const hlBadge = hl === 'ready'
            ? '<span class="match-badge badge-hl">Highlight ✓</span>'
            : (hl === 'pending' || hl === 'downloading')
                ? '<span class="match-badge badge-pending">HL Processing…</span>'
                : '';
        const fmBadge = fm === 'ready'
            ? '<span class="match-badge badge-ready">Full Match ✓</span>'
            : (fm === 'pending' || fm === 'downloading')
                ? '<span class="match-badge badge-pending">FM Processing…</span>'
                : '';
        const home = m.home_team?.name || '?';
        const away = m.away_team?.name || '?';
        const date = m.match_date ? new Date(m.match_date).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}) : '';
        const league = m.league?.name || '';
        return `<div class="match-row" id="mrow-${m.id}" onclick="selectMatch(${m.id}, '${esc(home)} vs ${esc(away)}', '${esc(date)}', '${esc(league)}')">
            <div style="flex:1">
                <div class="match-teams">${esc(home)} <span style="color:var(--sub)">vs</span> ${esc(away)}</div>
                <div class="match-meta">${esc(league)}${league && date ? ' · ' : ''}${esc(date)}</div>
            </div>
            <div class="badges">${hlBadge}${fmBadge}</div>
        </div>`;
    }).join('');

    renderPagination(meta);
}

function searchMatches() {
    loadMatches(1, document.getElementById('searchInput').value.trim());
}

function renderPagination(meta) {
    const pg = document.getElementById('pagination');
    if (!meta.last_page || meta.last_page <= 1) { pg.innerHTML = ''; return; }
    let html = '';
    if (meta.current_page > 1) html += `<button class="page-btn" onclick="loadMatches(${meta.current_page - 1}, currentQuery)">← Prev</button>`;
    html += `<span class="page-btn active" style="cursor:default">${meta.current_page} / ${meta.last_page}</span>`;
    if (meta.current_page < meta.last_page) html += `<button class="page-btn" onclick="loadMatches(${meta.current_page + 1}, currentQuery)">Next →</button>`;
    pg.innerHTML = html;
}

// ── Select match ──────────────────────────────────────────
function selectMatch(id, name, date, league) {
    currentMatchId = id;
    document.querySelectorAll('.match-row').forEach(r => r.classList.remove('selected'));
    const row = document.getElementById('mrow-' + id);
    if (row) { row.classList.add('selected'); }

    const label = document.getElementById('panelsMatchName');
    label.innerHTML = name + (date || league ? ` <span>${[date, league].filter(Boolean).join(' · ')}</span>` : '');
    label.style.display = 'block';

    document.getElementById('hlPanel').style.display = 'block';
    document.getElementById('fmPanel').style.display = 'block';

    // Reset forms
    ['hl', 'fm'].forEach(p => {
        document.getElementById(`${p}-fileInput`).value = '';
        document.getElementById(`${p}-ytInput`).value = '';
        document.getElementById(`${p}-fileChosen`).style.display = 'none';
        hideStatus(p);
        clearInterval(pollIntervals[p]);
    });

    // Scroll panels into view
    setTimeout(() => label.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
}

// ── Tabs ──────────────────────────────────────────────────
function switchTab(panel, tab) {
    panelTabs[panel] = tab;
    const tabs = document.querySelectorAll(`#${panel}Panel .tab`);
    tabs[0].classList.toggle('active', tab === 'file');
    tabs[1].classList.toggle('active', tab === 'youtube');
    document.getElementById(`${panel}-tabFile`).classList.toggle('active', tab === 'file');
    document.getElementById(`${panel}-tabYoutube`).classList.toggle('active', tab === 'youtube');
    document.getElementById(`${panel}-submitBtn`).textContent =
        panel === 'hl'
            ? (tab === 'file' ? 'Set Highlight' : 'Queue Highlight Download')
            : (tab === 'file' ? 'Upload Full Match' : 'Queue Full Match Download');
}

function onFileChosen(panel, input) {
    const f = input.files[0];
    if (!f) return;
    const mb = (f.size / 1024 / 1024).toFixed(1);
    const el = document.getElementById(`${panel}-fileChosen`);
    el.textContent = `✓ ${f.name} (${mb} MB)`;
    el.style.display = 'block';
}

// Drop zones
['hl', 'fm'].forEach(panel => {
    const dz = document.getElementById(`${panel}-dropZone`);
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
    dz.addEventListener('drop', e => {
        e.preventDefault();
        dz.classList.remove('drag-over');
        if (e.dataTransfer.files.length) {
            document.getElementById(`${panel}-fileInput`).files = e.dataTransfer.files;
            onFileChosen(panel, document.getElementById(`${panel}-fileInput`));
        }
    });
});

// ── Submit ────────────────────────────────────────────────
async function submitUpload(panel) {
    if (!currentMatchId) return;
    const videoType = panel === 'hl' ? 'highlight' : 'full_match';
    const tab = panelTabs[panel];
    const btn = document.getElementById(`${panel}-submitBtn`);

    if (tab === 'file') {
        const file = document.getElementById(`${panel}-fileInput`).files[0];
        if (!file) { showStatus(panel, 'error', 'Please choose a video file.'); return; }

        btn.disabled = true;
        btn.textContent = 'Uploading…';
        showStatus(panel, 'info', 'Uploading file…', 'This may take a while.');

        const fd = new FormData();
        fd.append('match_id', currentMatchId);
        fd.append('video_type', videoType);
        fd.append('file', file);

        try {
            const res = await fetch('/api/admin/full-match', {
                method: 'POST',
                headers: { 'X-Admin-Key': adminKey },
                body: fd,
            });
            const json = await res.json();
            if (!res.ok) {
                showStatus(panel, 'error', 'Upload failed.', JSON.stringify(json.errors || json.error || json));
                btn.disabled = false;
                resetBtnLabel(panel, tab);
                return;
            }
            showStatus(panel, 'info', 'Converting to HLS…', 'Will update when ready.');
            pollStatus(panel, json.video_id);
        } catch (e) {
            showStatus(panel, 'error', 'Network error: ' + e.message);
            btn.disabled = false;
            resetBtnLabel(panel, tab);
        }
    } else {
        const url = document.getElementById(`${panel}-ytInput`).value.trim();
        if (!url || !url.includes('youtube')) { showStatus(panel, 'error', 'Please enter a valid YouTube URL.'); return; }

        btn.disabled = true;
        btn.textContent = 'Queuing…';

        try {
            const res = await fetch('/api/admin/full-match', {
                method: 'POST',
                headers: { 'X-Admin-Key': adminKey, 'Content-Type': 'application/json' },
                body: JSON.stringify({ match_id: currentMatchId, video_type: videoType, youtube_url: url }),
            });
            const json = await res.json();
            if (!res.ok) {
                showStatus(panel, 'error', 'Failed.', JSON.stringify(json.errors || json.error || json));
                btn.disabled = false;
                resetBtnLabel(panel, tab);
                return;
            }
            showStatus(panel, 'info', 'Queued for yt-dlp download…', 'Will update when ready.');
            pollStatus(panel, json.video_id);
        } catch (e) {
            showStatus(panel, 'error', 'Network error: ' + e.message);
            btn.disabled = false;
            resetBtnLabel(panel, tab);
        }
    }
}

function pollStatus(panel, videoId) {
    const type = panel === 'hl' ? 'highlight' : 'full_match';
    clearInterval(pollIntervals[panel]);
    pollIntervals[panel] = setInterval(async () => {
        try {
            const res = await fetch(`/api/admin/full-match/${currentMatchId}?type=${type}`, { headers: { 'X-Admin-Key': adminKey } });
            const json = await res.json();
            if (json.status === 'ready') {
                clearInterval(pollIntervals[panel]);
                const mb = json.file_size_mb ? ` · ${json.file_size_mb} MB` : '';
                const dur = json.duration_seconds ? ` · ${Math.round(json.duration_seconds / 60)} min` : '';
                showStatus(panel, 'success', panel === 'hl' ? 'Highlight ready!' : 'Full match ready!', `HLS saved${mb}${dur}`);
                document.getElementById(`${panel}-submitBtn`).disabled = false;
                resetBtnLabel(panel, panelTabs[panel]);
                loadMatches(currentPage, currentQuery);
            } else if (json.status === 'error') {
                clearInterval(pollIntervals[panel]);
                showStatus(panel, 'error', 'Processing failed.', 'Check server logs.');
                document.getElementById(`${panel}-submitBtn`).disabled = false;
                resetBtnLabel(panel, panelTabs[panel]);
            }
        } catch (_) {}
    }, 5000);
}

function resetBtnLabel(panel, tab) {
    const labels = {
        hl: { file: 'Set Highlight', youtube: 'Queue Highlight Download' },
        fm: { file: 'Upload Full Match', youtube: 'Queue Full Match Download' },
    };
    document.getElementById(`${panel}-submitBtn`).textContent = labels[panel][tab];
}

// ── Helpers ───────────────────────────────────────────────
function showStatus(panel, type, text, sub) {
    const box = document.getElementById(`${panel}-statusBox`);
    box.className = 'status-box ' + type;
    box.style.display = 'block';
    document.getElementById(`${panel}-statusText`).textContent = text;
    document.getElementById(`${panel}-statusSub`).textContent = sub || '';
}
function hideStatus(panel) {
    document.getElementById(`${panel}-statusBox`).style.display = 'none';
}
function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Init ──────────────────────────────────────────────────
const savedKey = localStorage.getItem('fh_admin_key');
if (savedKey) { adminKey = savedKey; showApp(); }
</script>
</body>
</html>
