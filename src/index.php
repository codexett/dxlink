<?php /* index.php – DXlink Dashboard (dev, upgraded)
 - Tabs: Status, SVXLink, SA818, APRS, Wi‑Fi, Logs, Terminal, Config
 - Equal-width tabs, hover lift -4px
 - STATUS: cards + mock refresh (kept)
 - SVXLink: structured editor with VALIDATION, SHOW/HIDE secrets, PROFILE MGMT (rename/duplicate/delete),
            unsaved changes indicator + unload guard, Save & Restart, live status polling (svx.php?action=status)
*/ ?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DXlink Dashboard (dev)</title>
  <style>
    :root{
      --bg:#ffffff;--text:#1a1a1a;--muted:#6b7280;--ring:#e5e7eb;
      --g1:#9333ea;--g2:#a855f7;--g3:#7c3aed;--ok:#10b981;--bad:#ef4444;--warn:#f59e0b;
    }
    *{box-sizing:border-box}
    html,body{height:100%;}
    body{margin:0;background:var(--bg);color:var(--text);font:16px/1.4 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif}
    .container{max-width:1200px;margin:32px auto;padding:0 20px}

    /* Header */
    .header{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
    .logo img{height:50px;width:auto;display:block}
    .brand{display:flex;align-items:baseline;gap:10px}
    .brand h1{margin:0;font-weight:800;letter-spacing:.2px;font-size:40px}
    .brand small{color:#6b7280;font-size:26px}

    /* Tabs */
    .tabs{display:flex;flex-wrap:wrap;margin:22px 0 14px 0}
    .tab{
      flex:1 1 0;text-align:center;cursor:pointer;user-select:none;
      padding:18px 0;border-radius:22px;background:#f3f4f6;color:#111827;font-weight:700;
      box-shadow:inset 0 0 0 1px #e5e7eb;transition:transform .15s ease, box-shadow .2s ease, background .15s ease;
      margin:4px;
    }
    .tab:hover{transform:translateY(-4px)}
    .tab.active{color:#fff;background:radial-gradient(140% 140% at 100% 0%,var(--g2),var(--g1));
      box-shadow:0 10px 30px rgba(124,58,237,.35), inset 0 0 0 1px rgba(255,255,255,.08)}

    .panel{display:none;border:1px solid var(--ring);border-radius:14px;padding:18px}
    .panel.active{display:block}

    /* Utility UI */
    .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    @media (max-width: 900px){ .grid{grid-template-columns:1fr} }
    .card{border:1px solid var(--ring);border-radius:14px;padding:14px;background:#fff}
    .card h3{margin:0 0 8px 0;font-size:18px}
    .kv{display:grid;grid-template-columns:180px 1fr;gap:8px;align-items:center;margin:6px 0}
    .kv .k{color:#374151}.kv .v{color:#111827;font-weight:700}
    .pill{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:6px 12px;font-weight:700}
    .pill.ok{background:#ecfdf5;color:#065f46}.pill.bad{background:#fef2f2;color:#991b1b}.pill.warn{background:#fffbeb;color:#92400e}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:10px 0}
    .btn{appearance:none;border:1px solid var(--ring);background:#fff;border-radius:12px;padding:10px 16px;font-weight:700;cursor:pointer}
    .btn:active{transform:translateY(1px)}
    .btn.primary{background:radial-gradient(140% 140% at 100% 0%,var(--g2),var(--g1));color:#fff;border-color:transparent}
    .btn.danger{background:#fee2e2;color:#991b1b;border-color:#fecaca}
    .btn.ghost{background:transparent}
    .input, textarea, select{width:100%;border:1px solid var(--ring);border-radius:12px;padding:10px 12px;font:inherit}
    textarea{min-height:120px;resize:vertical}
    label{font-weight:700}
    .muted{color:var(--muted)}
    .two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    @media (max-width: 720px){ .two{grid-template-columns:1fr} }
    .chips{display:flex;flex-wrap:wrap;gap:8px}
    .chip{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--ring);border-radius:999px;padding:6px 10px}
    .hint{font-size:12px;color:#6b7280}
    .error{color:#b91c1c;font-weight:700}
    .success{color:#065f46;font-weight:700}
    .right{margin-left:auto}
    .nowrap{white-space:nowrap}
    .separator{height:1px;background:var(--ring);margin:10px 0}
    .input-row{display:flex;gap:8px;align-items:center}
    .toggle-btn{border:1px solid var(--ring);border-radius:8px;padding:6px 10px;cursor:pointer;background:#f9fafb}
    .badge{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:6px 10px;font-weight:700;background:#f3f4f6}
    .badge.warn{background:#fffbeb;color:#92400e}
    .badge.info{background:#eef2ff;color:#3730a3}
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <div class="logo"><img src="dxlink-logo.png" alt="DXlink Logo" /></div>
      <div class="brand"><h1>Dashboard</h1><small>(dev)</small></div>
    </div>

    <!-- Tabs -->
    <nav class="tabs" role="tablist" aria-label="Pagini dashboard">
      <button class="tab active" role="tab" aria-controls="tab-status" id="btn-status">Status</button>
      <button class="tab" role="tab" aria-controls="tab-svxlink" id="btn-svxlink">SVXLink</button>
      <button class="tab" role="tab" aria-controls="tab-sa818" id="btn-sa818">SA818</button>
      <button class="tab" role="tab" aria-controls="tab-aprs" id="btn-aprs">APRS</button>
      <button class="tab" role="tab" aria-controls="tab-wifi" id="btn-wifi">Wi‑Fi</button>
      <button class="tab" role="tab" aria-controls="tab-logs" id="btn-logs">Logs</button>
      <button class="tab" role="tab" aria-controls="tab-terminal" id="btn-terminal">Terminal</button>
      <button class="tab" role="tab" aria-controls="tab-config" id="btn-config">Config</button>
    </nav>

    <!-- STATUS -->
    <section class="panel active" id="tab-status">
      <div class="row"><button class="btn" id="btn-refresh-status">Refresh</button><span class="muted" id="status-updated">—</span></div>
      <div class="grid">
        <div class="card" id="card-system"><h3>System</h3>
          <div class="kv"><div class="k">Hostname</div><div class="v" id="host">—</div></div>
          <div class="kv"><div class="k">Uptime</div><div class="v" id="uptime">—</div></div>
          <div class="kv"><div class="k">DXlink Version</div><div class="v" id="dxver">—</div></div>
          <div class="kv"><div class="k">FS Status</div><div class="v" id="fs">—</div></div>
        </div>
        <div class="card" id="card-resources"><h3>Resources</h3>
          <div class="kv"><div class="k">CPU Load</div><div class="v"><span id="cpu-load-val">—</span></div></div>
          <div style="height:10px;border-radius:999px;background:#f3f4f6;overflow:hidden"><span id="cpu-load-bar" style="display:block;height:100%;background:linear-gradient(90deg,var(--g1),var(--g2));width:0"></span></div>
          <div class="kv"><div class="k">CPU Temp</div><div class="v" id="cpu-temp">—</div></div>
          <div class="kv"><div class="k">Free Space</div><div class="v" id="free-space">—</div></div>
        </div>
        <div class="card" id="card-network"><h3>Network</h3>
          <div class="kv"><div class="k">WLAN IP</div><div class="v" id="wlan-ip">—</div></div>
          <div class="kv"><div class="k">SSID</div><div class="v" id="ssid">—</div></div>
          <div class="kv"><div class="k">External IP</div><div class="v" id="wan-ip">—</div></div>
        </div>
        <div class="card" id="card-services"><h3>Services</h3>
          <div class="row">
            <span class="pill" id="svc-sa818">SA818</span>
            <span class="pill" id="svc-svx">SVXLink</span>
            <span class="pill" id="svc-reflector">Reflector</span>
            <span class="pill" id="svc-aprs">APRS</span>
          </div>
          <div class="kv"><div class="k">SVX PID</div><div class="v" id="svx-pid">—</div></div>
          <div class="kv"><div class="k">Reflector</div><div class="v" id="svx-ref">—</div></div>
        </div>
        <div class="card" id="card-identity"><h3>Identity</h3>
          <div class="kv"><div class="k">Callsign</div><div class="v" id="callsign">—</div></div>
          <div class="kv"><div class="k">Node Type</div><div class="v" id="node-type">—</div></div>
        </div>
        <div class="card" id="card-gpio"><h3>GPIO</h3>
          <div class="row"><span class="pill" id="gpio-rx">RX</span><span class="pill" id="gpio-tx">TX</span><span class="pill" id="gpio-fan">FAN</span></div>
        </div>
      </div>
    </section>

    <!-- SVXLINK (upgraded) -->
    <section class="panel" id="tab-svxlink">
      <div class="row">
        <span class="badge info">SVXLink Config</span>
        <span class="badge warn right nowrap" id="unsaved-badge" style="display:none">Unsaved changes</span>
      </div>
      <div class="grid">
        <!-- Profile -->
        <div class="card">
          <h3>Profile</h3>
          <div class="two">
            <div>
              <label for="svx-profile">Select profile</label>
              <select id="svx-profile">
                <option value="portabil">portabil</option>
                <option value="fix">fix</option>
                <option value="test">test</option>
              </select>
            </div>
            <div>
              <label for="svx-new-profile">Create new profile</label>
              <div class="input-row">
                <input id="svx-new-profile" class="input" placeholder="ex: camp_Lipova" />
                <button class="btn" id="btn-create-profile">Create</button>
              </div>
              <div class="hint">Doar litere, cifre, „-”, „_”, „.” (max 64).</div>
            </div>
          </div>
          <div class="separator"></div>
          <div class="row">
            <div class="input-row">
              <input id="svx-rename-profile" class="input" placeholder="rename to..." />
              <button class="btn" id="btn-rename-profile">Rename</button>
            </div>
            <div class="input-row">
              <input id="svx-duplicate-profile" class="input" placeholder="duplicate as..." />
              <button class="btn" id="btn-duplicate-profile">Duplicate</button>
            </div>
            <button class="btn danger right" id="btn-delete-profile">Delete</button>
          </div>
          <div id="profile-msg" class="hint"></div>
        </div>

        <!-- General Settings -->
        <div class="card">
          <h3>Setări generale</h3>
          <div class="two">
            <div><label>Reflector</label><input id="svx-reflector" class="input" placeholder="svx.dxlink.ro" /></div>
            <div><label>Port</label><input id="svx-port" class="input" type="number" value="5300" min="1" max="65535" /></div>
          </div>
          <div class="two">
            <div><label>Callsign</label><input id="svx-callsign" class="input" placeholder="YO3THC-P" /></div>
            <div>
              <label>Auth Key</label>
              <div class="input-row">
                <input id="svx-auth" class="input" type="password" placeholder="••••••••" />
                <button class="toggle-btn" data-toggle="#svx-auth">Show</button>
              </div>
            </div>
          </div>
          <div class="two">
            <div><label>Callsign beacon</label><input id="svx-beacon" class="input" placeholder="YO3THC" /></div>
            <div><label>Roger beep</label>
              <select id="svx-roger"><option value="yes">Yes</option><option value="no">No</option></select>
            </div>
          </div>
          <div class="two">
            <div><label>Voice pack</label><input id="svx-voice" class="input" placeholder="ro_RO-alexandra" /></div>
            <div><label>Node type</label>
              <select id="svx-node"><option value="portabil">portabil</option><option value="fix">fix</option><option value="repeater">repeater</option></select>
            </div>
          </div>
          <div class="two">
            <div><label>Short Ident (min)</label><input id="svx-short-ident" class="input" type="number" value="10" min="0" /></div>
            <div><label>Long Ident (min)</label><input id="svx-long-ident" class="input" type="number" value="60" min="0" /></div>
          </div>
          <div>
            <label>Modules</label>
            <div class="row">
              <div class="chips" id="svx-modules">
                <label class="chip"><input type="checkbox" value="ModuleEchoLink" checked /> EchoLink</label>
                <label class="chip"><input type="checkbox" value="ModuleParrot" /> Parrot</label>
                <label class="chip"><input type="checkbox" value="ModuleMetarInfo" /> METAR</label>
                <label class="chip"><input type="checkbox" value="ModuleHelp" /> Help</label>
                <label class="chip"><input type="checkbox" value="ModuleTcl" /> TCL</label>
                <label class="chip"><input type="checkbox" value="ModuleDtmfRepeater" /> DTMF Repeater</label>
              </div>
              <button class="btn ghost" id="btn-mod-all">All</button>
              <button class="btn ghost" id="btn-mod-none">None</button>
            </div>
          </div>
          <div id="svx-validate" class="hint"></div>
        </div>

        <!-- EchoLink -->
        <div class="card">
          <h3>Setări EchoLink</h3>
          <div class="two">
            <div><label>Callsign</label><input id="el-callsign" class="input" placeholder="YO3THC-L" /></div>
            <div>
              <label>Password</label>
              <div class="input-row">
                <input id="el-pass" class="input" type="password" placeholder="••••••••" />
                <button class="toggle-btn" data-toggle="#el-pass">Show</button>
              </div>
            </div>
          </div>
          <div class="two">
            <div><label>Sysop</label><input id="el-sysop" class="input" placeholder="Nume operator" /></div>
            <div><label>Location</label><input id="el-location" class="input" placeholder="București, QTH" /></div>
          </div>
          <div class="two">
            <div><label>Timeout general (s)</label><input id="el-timeout" class="input" type="number" value="180" min="0" /></div>
            <div><label>Idle link (s)</label><input id="el-idle" class="input" type="number" value="600" min="0" /></div>
          </div>
          <div class="two">
            <div><label>Proxy server</label><input id="el-proxy-host" class="input" placeholder="proxy.example" /></div>
            <div><label>Proxy port</label><input id="el-proxy-port" class="input" type="number" placeholder="8080" min="0" max="65535" /></div>
          </div>
          <div class="two">
            <div><label>Proxy user</label><input id="el-proxy-user" class="input" /></div>
            <div>
              <label>Proxy pass</label>
              <div class="input-row">
                <input id="el-proxy-pass" class="input" type="password" />
                <button class="toggle-btn" data-toggle="#el-proxy-pass">Show</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Advanced / GPIO -->
        <div class="card">
          <h3>Advanced / GPIO</h3>
          <div class="two">
            <div><label>RX GPIO pin</label><input id="gpio-rx-pin" class="input" placeholder="ex: 17" /></div>
            <div><label>TX GPIO pin</label><input id="gpio-tx-pin" class="input" placeholder="ex: 27" /></div>
          </div>
          <div class="two">
            <div><label>Squelch Delay (ms)</label><input id="svx-sq-delay" class="input" type="number" value="120" min="0" /></div>
            <div class="row"><label class="chip"><input type="checkbox" id="ptt-invert" /> PTT invert</label><label class="chip"><input type="checkbox" id="cos-invert" /> COS invert</label></div>
          </div>
        </div>

        <!-- Actions -->
        <div class="card">
          <h3>Acțiuni</h3>
          <div class="row">
            <button class="btn" id="btn-svx-load">Load Config</button>
            <button class="btn primary" id="btn-svx-save">Save Config</button>
            <button class="btn" id="btn-svx-save-restart">Save & Restart</button>
            <span class="muted" id="svx-save-status">—</span>
          </div>
          <div class="row">
            <button class="btn" id="btn-svx-start">Start</button>
            <button class="btn" id="btn-svx-stop">Stop</button>
            <button class="btn" id="btn-svx-restart">Restart</button>
            <span class="pill warn" id="svx-runstate">SVXLink • unknown</span>
            <span class="hint" id="svx-runpid">PID: —</span>
          </div>
        </div>
      </div>
    </section>

    <!-- PLACEHOLDER PANELS -->
    <section class="panel" id="tab-sa818"><h2 class="section-title">SA818</h2><p class="muted">Programator canal (freq, CTCSS, squelch, volum, dev).</p></section>
    <section class="panel" id="tab-aprs"><h2 class="section-title">APRS</h2><p class="muted">Setări Direwolf + gpsd, Start/Stop și status.</p></section>
    <section class="panel" id="tab-wifi"><h2 class="section-title">Wi‑Fi</h2><p class="muted">Scan, saved networks, connect/disconnect, restart Wi‑Fi.</p></section>
    <section class="panel" id="tab-logs"><h2 class="section-title">Logs</h2><p class="muted">Viewer live cu selector (syslog, svxlink, aprs).</p></section>
    <section class="panel" id="tab-terminal"><h2 class="section-title">Terminal</h2><p class="muted">Consolă web (ttyd) sau „run command” cu whitelist.</p></section>
    <section class="panel" id="tab-config"><h2 class="section-title">Config</h2><p class="muted">Setări sistem (timezone, hostname, GPIO PTT, serial SA818, volum ALSA, FS RO/RW, update, parolă dashboard).</p></section>
  </div>

  <script>
    // ---------- Tabs
    const tabs = Array.from(document.querySelectorAll('.tab'));
    const panels = {
      status: document.getElementById('tab-status'),
      svxlink: document.getElementById('tab-svxlink'),
      sa818: document.getElementById('tab-sa818'),
      aprs: document.getElementById('tab-aprs'),
      wifi: document.getElementById('tab-wifi'),
      logs: document.getElementById('tab-logs'),
      terminal: document.getElementById('tab-terminal'),
      config: document.getElementById('tab-config')
    };
    function activateTab(name){
      tabs.forEach(btn=>btn.classList.toggle('active', btn.id===`btn-${name}`));
      Object.entries(panels).forEach(([key,el])=>el.classList.toggle('active', key===name));
      try { history.replaceState(null, '', `#${name}`); } catch(_){}
    }
    tabs.forEach(btn=>btn.addEventListener('click',()=>activateTab(btn.id.replace('btn-',''))));
    const initial = (location.hash||'#status').replace('#','');
    if(Object.keys(panels).includes(initial)) activateTab(initial);

    // ---------- Helpers: pills, toggles, modules
    function setPill(el, state, label){
      el.classList.remove('ok','bad','warn');
      if(state==='ok') el.classList.add('ok');
      if(state==='bad') el.classList.add('bad');
      if(state==='warn') el.classList.add('warn');
      el.textContent = label;
    }
    document.querySelectorAll('.toggle-btn').forEach(b=>{
      b.addEventListener('click',()=>{
        const id = b.getAttribute('data-toggle');
        const input = document.querySelector(id);
        if(!input) return;
        input.type = (input.type==='password') ? 'text' : 'password';
        b.textContent = (input.type==='password') ? 'Show' : 'Hide';
      });
    });
    function modulesSetAll(v=true){
      document.querySelectorAll('#svx-modules input[type=checkbox]').forEach(cb=>cb.checked=v);
      markUnsaved();
    }
    document.getElementById('btn-mod-all').addEventListener('click',()=>modulesSetAll(true));
    document.getElementById('btn-mod-none').addEventListener('click',()=>modulesSetAll(false));

    // ---------- STATUS: mock + map (you can switch to status.php as shown earlier)
    function loadStatus(){
      fetch('status.php').then(r=>r.json()).then(j=>{
        if(!j || !j.success) throw 0;
        const d = j.data;
        applyStatus({
          host: d.system.hostname,
          uptime: d.system.uptime || '—',
          dxver: d.system.version || '—',
          fs: d.system.fs || '—',
          cpuLoad: d.resources.cpuLoadPct ?? 0,
          cpuTemp: d.resources.cpuTempC ? (d.resources.cpuTempC + '°C') : '—',
          freeSpace: d.resources.freeSpaceGB ? (d.resources.freeSpaceGB + ' GB') : '—',
          wlanIp: d.network.wlanIp || '—',
          ssid: d.network.ssid || '—',
          wanIp: d.network.wanIp || '—',
          sa818: !!d.services.sa818Detected,
          svx: { running: !!d.services.svxlink.running, pid: d.services.svxlink.pid || null, reflector: d.services.svxlink.reflector || null },
          aprs: !!d.services.aprsActive,
          gpio: { rx: d.gpio.rx, tx: d.gpio.tx, fan: d.gpio.fan },
          callsign: d.identity.callsign || '—',
          nodeType: d.identity.nodeType || '—',
          updatedAt: d.updatedAt
        });
      }).catch(()=>{
        // fallback mock
        applyStatus({host:'dxlink-portabil',uptime:'—',dxver:'DXlink 1.0.0',fs:'RW',cpuLoad:25,cpuTemp:'—',freeSpace:'—',wlanIp:'—',ssid:'—',wanIp:'—',sa818:false,svx:{running:false,pid:null,reflector:null},aprs:false,gpio:{rx:null,tx:null,fan:null},callsign:'—',nodeType:'—',updatedAt:new Date().toLocaleString()});
      });
    }
    function applyStatus(d){
      document.getElementById('host').textContent = d.host;
      document.getElementById('uptime').textContent = d.uptime;
      document.getElementById('dxver').textContent = d.dxver;
      document.getElementById('fs').textContent = d.fs;
      document.getElementById('cpu-load-val').textContent = d.cpuLoad + '%';
      document.getElementById('cpu-load-bar').style.width = Math.max(0, Math.min(100, d.cpuLoad)) + '%';
      document.getElementById('cpu-temp').textContent = d.cpuTemp;
      document.getElementById('free-space').textContent = d.freeSpace;
      document.getElementById('wlan-ip').textContent = d.wlanIp;
      document.getElementById('ssid').textContent = d.ssid;
      document.getElementById('wan-ip').textContent = d.wanIp;
      setPill(document.getElementById('svc-sa818'), d.sa818?'ok':'bad', 'SA818 • ' + (d.sa818?'detected':'missing'));
      setPill(document.getElementById('svc-svx'), d.svx.running?'ok':'bad', 'SVXLink • ' + (d.svx.running?'running':'stopped'));
      setPill(document.getElementById('svc-reflector'), d.svx.reflector? 'ok':'warn', 'Reflector • ' + (d.svx.reflector||'none'));
      setPill(document.getElementById('svc-aprs'), d.aprs?'ok':'bad', 'APRS • ' + (d.aprs?'active':'inactive'));
      document.getElementById('svx-pid').textContent = d.svx.running ? d.svx.pid : '—';
      document.getElementById('svx-ref').textContent = d.svx.reflector || '—';
      document.getElementById('callsign').textContent = d.callsign;
      document.getElementById('node-type').textContent = d.nodeType;
      setPill(document.getElementById('gpio-rx'), d.gpio.rx?'ok':'bad', 'RX ' + (d.gpio.rx?'ON':'OFF'));
      setPill(document.getElementById('gpio-tx'), d.gpio.tx?'ok':'bad', 'TX ' + (d.gpio.tx?'ON':'OFF'));
      setPill(document.getElementById('gpio-fan'), d.gpio.fan?'ok':'warn', 'FAN ' + (d.gpio.fan?'ON':'OFF'));
      document.getElementById('status-updated').textContent = 'Updated: ' + d.updatedAt;
    }
    document.getElementById('btn-refresh-status').addEventListener('click', loadStatus);
    loadStatus();

    // ---------- SVXLINK: storage helpers (mock via localStorage)
    const SVX_STORAGE_KEY = 'dxlink_svx_profile_config';
    function collectModules(){ return Array.from(document.querySelectorAll('#svx-modules input[type=checkbox]:checked')).map(cb=>cb.value); }
    function setModules(values){ const set=new Set(values||[]); document.querySelectorAll('#svx-modules input[type=checkbox]').forEach(cb=>cb.checked=set.has(cb.value)); }
    function collectSvxForm(){
      return {
        profile: document.getElementById('svx-profile').value,
        reflector: document.getElementById('svx-reflector').value.trim(),
        port: parseInt(document.getElementById('svx-port').value||'5300',10),
        callsign: document.getElementById('svx-callsign').value.trim(),
        auth: document.getElementById('svx-auth').value,
        beacon: document.getElementById('svx-beacon').value.trim(),
        roger: document.getElementById('svx-roger').value,
        voice: document.getElementById('svx-voice').value.trim(),
        node: document.getElementById('svx-node').value,
        shortIdent: parseInt(document.getElementById('svx-short-ident').value||'10',10),
        longIdent: parseInt(document.getElementById('svx-long-ident').value||'60',10),
        modules: collectModules(),
        el: {
          callsign: document.getElementById('el-callsign').value.trim(),
          pass: document.getElementById('el-pass').value,
          sysop: document.getElementById('el-sysop').value.trim(),
          location: document.getElementById('el-location').value.trim(),
          timeout: parseInt(document.getElementById('el-timeout').value||'180',10),
          idle: parseInt(document.getElementById('el-idle').value||'600',10),
          proxyHost: document.getElementById('el-proxy-host').value.trim(),
          proxyPort: document.getElementById('el-proxy-port').value.trim(),
          proxyUser: document.getElementById('el-proxy-user').value.trim(),
          proxyPass: document.getElementById('el-proxy-pass').value
        },
        gpio: {
          rxPin: document.getElementById('gpio-rx-pin').value.trim(),
          txPin: document.getElementById('gpio-tx-pin').value.trim(),
          squelchDelay: parseInt(document.getElementById('svx-sq-delay').value||'120',10),
          pttInvert: document.getElementById('ptt-invert').checked,
          cosInvert: document.getElementById('cos-invert').checked
        }
      };
    }
    function applySvxForm(d){
      if(!d) return;
      document.getElementById('svx-profile').value = d.profile || 'portabil';
      document.getElementById('svx-reflector').value = d.reflector || 'svx.dxlink.ro';
      document.getElementById('svx-port').value = d.port ?? 5300;
      document.getElementById('svx-callsign').value = d.callsign || 'YO3THC-P';
      document.getElementById('svx-auth').value = d.auth || '';
      document.getElementById('svx-beacon').value = d.beacon || 'YO3THC';
      document.getElementById('svx-roger').value = d.roger || 'yes';
      document.getElementById('svx-voice').value = d.voice || 'ro_RO-alexandra';
      document.getElementById('svx-node').value = d.node || 'portabil';
      document.getElementById('svx-short-ident').value = d.shortIdent ?? 10;
      document.getElementById('svx-long-ident').value = d.longIdent ?? 60;
      setModules(d.modules || ['ModuleEchoLink','ModuleHelp']);
      document.getElementById('el-callsign').value = d.el?.callsign || 'YO3THC-L';
      document.getElementById('el-pass').value = d.el?.pass || '';
      document.getElementById('el-sysop').value = d.el?.sysop || '';
      document.getElementById('el-location').value = d.el?.location || 'București';
      document.getElementById('el-timeout').value = d.el?.timeout ?? 180;
      document.getElementById('el-idle').value = d.el?.idle ?? 600;
      document.getElementById('el-proxy-host').value = d.el?.proxyHost || '';
      document.getElementById('el-proxy-port').value = d.el?.proxyPort || '';
      document.getElementById('el-proxy-user').value = d.el?.proxyUser || '';
      document.getElementById('el-proxy-pass').value = d.el?.proxyPass || '';
      document.getElementById('gpio-rx-pin').value = d.gpio?.rxPin || '17';
      document.getElementById('gpio-tx-pin').value = d.gpio?.txPin || '27';
      document.getElementById('svx-sq-delay').value = d.gpio?.squelchDelay ?? 120;
      document.getElementById('ptt-invert').checked = !!d.gpio?.pttInvert;
      document.getElementById('cos-invert').checked = !!d.gpio?.cosInvert;
      clearUnsaved();
    }

    // ---------- Validation
    function validateSvx(){
      const out = [];
      const port = parseInt(document.getElementById('svx-port').value||'0',10);
      if(!(port>=1 && port<=65535)) out.push('Port must be between 1 and 65535.');
      const cs = document.getElementById('svx-callsign').value.trim();
      if(cs && !/^[A-Z0-9\-]{3,15}$/i.test(cs)) out.push('Callsign has invalid format.');
      const csb = document.getElementById('svx-beacon').value.trim();
      if(csb && !/^[A-Z0-9\-]{3,15}$/i.test(csb)) out.push('Beacon callsign invalid.');
      const shortI = parseInt(document.getElementById('svx-short-ident').value||'0',10);
      const longI  = parseInt(document.getElementById('svx-long-ident').value||'0',10);
      if(shortI<0 || longI<0) out.push('Ident intervals must be >= 0.');
      const proxyp = document.getElementById('el-proxy-port').value.trim();
      if(proxyp && (parseInt(proxyp,10)<0 || parseInt(proxyp,10)>65535)) out.push('Proxy port invalid.');
      const msg = document.getElementById('svx-validate');
      msg.innerHTML = out.length ? '<span class=\"error\">'+out.join(' ')+'</span>' : '<span class=\"success\">OK</span>';
      return out.length===0;
    }
    ['input','change'].forEach(ev=>{
      document.getElementById('tab-svxlink').addEventListener(ev, e=>{
        if(e.target.matches('input,select,textarea')){ markUnsaved(); validateSvx(); }
      });
    });

    // ---------- Unsaved changes indicator + unload guard
    let unsaved = false;
    const badge = document.getElementById('unsaved-badge');
    function markUnsaved(){ unsaved = true; badge.style.display='inline-flex'; }
    function clearUnsaved(){ unsaved = false; badge.style.display='none'; }
    window.addEventListener('beforeunload', (e)=>{ if(unsaved){ e.preventDefault(); e.returnValue=''; } });

    // ---------- Profile Ops (mock via localStorage namespace list)
    const PROFILES_KEY = 'dxlink_profiles_list';
    function loadProfilesList(){
      const raw = localStorage.getItem(PROFILES_KEY);
      return raw ? JSON.parse(raw) : ['portabil','fix','test'];
    }
    function saveProfilesList(list){ localStorage.setItem(PROFILES_KEY, JSON.stringify(Array.from(new Set(list)))); }
    function refreshProfileSelect(){
      const sel = document.getElementById('svx-profile');
      const current = sel.value;
      sel.innerHTML='';
      loadProfilesList().forEach(p=>{
        const opt=document.createElement('option'); opt.value=p; opt.textContent=p; sel.appendChild(opt);
      });
      if(loadProfilesList().includes(current)) sel.value=current;
    }
    function sanitizeProfileName(name){
      name=(name||'').trim(); if(!name) return '';
      if(name.length>64) name=name.slice(0,64);
      if(!/^[A-Za-z0-9._-]+$/.test(name)) return '';
      return name;
    }
    document.getElementById('btn-create-profile').addEventListener('click',()=>{
      const name=sanitizeProfileName(document.getElementById('svx-new-profile').value);
      if(!name) return setMsg('profile-msg','Invalid profile name','error');
      const list=loadProfilesList(); if(!list.includes(name)) list.push(name); saveProfilesList(list); refreshProfileSelect();
      document.getElementById('svx-profile').value=name; setMsg('profile-msg','Profile created & selected','success'); clearUnsaved();
    });
    document.getElementById('btn-rename-profile').addEventListener('click',()=>{
      const from=document.getElementById('svx-profile').value;
      const to=sanitizeProfileName(document.getElementById('svx-rename-profile').value);
      if(!to) return setMsg('profile-msg','Invalid target name','error');
      if(!confirm(`Rename profile "${from}" to "${to}"?`)) return;
      const list=loadProfilesList().map(p=>p===from?to:p); saveProfilesList(list);
      // Move stored config (mock)
      const old=localStorage.getItem(SVX_STORAGE_KEY+':'+from);
      if(old) { localStorage.setItem(SVX_STORAGE_KEY+':'+to, old); localStorage.removeItem(SVX_STORAGE_KEY+':'+from); }
      refreshProfileSelect(); document.getElementById('svx-profile').value=to; setMsg('profile-msg','Profile renamed','success'); clearUnsaved();
    });
    document.getElementById('btn-duplicate-profile').addEventListener('click',()=>{
      const from=document.getElementById('svx-profile').value;
      const to=sanitizeProfileName(document.getElementById('svx-duplicate-profile').value);
      if(!to) return setMsg('profile-msg','Invalid new name','error');
      const list=loadProfilesList(); if(!list.includes(to)) list.push(to); saveProfilesList(list);
      const current=collectSvxForm();
      current.profile=to;
      localStorage.setItem(SVX_STORAGE_KEY+':'+to, JSON.stringify(current));
      refreshProfileSelect(); document.getElementById('svx-profile').value=to; applySvxForm(current);
      setMsg('profile-msg','Profile duplicated & selected','success'); clearUnsaved();
    });
    document.getElementById('btn-delete-profile').addEventListener('click',()=>{
      const p=document.getElementById('svx-profile').value;
      if(!confirm(`Delete profile "${p}"?`)) return;
      let list=loadProfilesList().filter(x=>x!==p); if(list.length===0) list=['default'];
      saveProfilesList(list);
      localStorage.removeItem(SVX_STORAGE_KEY+':'+p);
      refreshProfileSelect(); document.getElementById('svx-profile').value=list[0];
      setMsg('profile-msg','Profile deleted','success'); clearUnsaved();
    });
    function setMsg(id, text, type){ const el=document.getElementById(id); el.textContent=text; el.className = (type==='error'?'error':type==='success'?'success':'hint'); }

    // ---------- Save/Load (mock) + Save & Restart
    function mockLoadSvx(){
      const p=document.getElementById('svx-profile').value;
      const raw = localStorage.getItem(SVX_STORAGE_KEY+':'+p);
      const d = raw ? JSON.parse(raw) : null;
      if(d) applySvxForm(d);
      document.getElementById('svx-save-status').textContent = 'Loaded';
      setTimeout(()=>document.getElementById('svx-save-status').textContent='—',1500);
    }
    function mockSaveSvx(cb){
      if(!validateSvx()){ document.getElementById('svx-save-status').textContent='Fix validation errors'; return; }
      const d = collectSvxForm();
      const p=d.profile;
      localStorage.setItem(SVX_STORAGE_KEY+':'+p, JSON.stringify(d));
      document.getElementById('svx-save-status').textContent = 'Saved';
      clearUnsaved();
      setTimeout(()=>document.getElementById('svx-save-status').textContent='—',1500);
      if(cb) cb();
    }
    document.getElementById('btn-svx-load').addEventListener('click', mockLoadSvx);
    document.getElementById('btn-svx-save').addEventListener('click', ()=>mockSaveSvx());
    document.getElementById('btn-svx-save-restart').addEventListener('click', ()=>mockSaveSvx(()=>svxRestart()));
    document.getElementById('svx-profile').addEventListener('change', ()=>{ mockLoadSvx(); });

    // ---------- Service controls + live status polling
    let svxRunning=false, svxPid=null, statusTimer=null;
    function updateRunStatePill(){
      const pill=document.getElementById('svx-runstate');
      const pidEl=document.getElementById('svx-runpid');
      setPill(pill, svxRunning?'ok':'bad', 'SVXLink • ' + (svxRunning?'running':'stopped'));
      pidEl.textContent = 'PID: ' + (svxPid || '—');
    }
    function svxPollStatus(){
      fetch('svx.php?action=status').then(r=>r.json()).then(j=>{
        if(j && j.success && j.data){
          svxRunning = (j.data.status==='active') || !!j.data.running;
          svxPid = j.data.pid || null;
          updateRunStatePill();
        }
      }).catch(()=>{});
    }
    function svxStart(){ fetch('svx.php?action=start').finally(svxPollStatus); }
    function svxStop(){ fetch('svx.php?action=stop').finally(svxPollStatus); }
    function svxRestart(){ fetch('svx.php?action=restart').finally(()=>setTimeout(svxPollStatus, 800)); }
    document.getElementById('btn-svx-start').addEventListener('click', svxStart);
    document.getElementById('btn-svx-stop').addEventListener('click', svxStop);
    document.getElementById('btn-svx-restart').addEventListener('click', svxRestart);
    statusTimer = setInterval(svxPollStatus, 4000);
    svxPollStatus();

    // ---------- Init
    (function init(){
      // default form & profile list
      refreshProfileSelect();
      applySvxForm({});
      mockLoadSvx();
      validateSvx();
    })();
  </script>
</body>
</html>
