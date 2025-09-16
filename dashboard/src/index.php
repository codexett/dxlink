<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>DXlink Dashboard (dev)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root{--br:12px}
    body{font-family:system-ui,Segoe UI,Arial;margin:0;background:#fff}
    .container{max-width:1100px;margin:0 auto;padding:24px}

    /* ---- Tabs ---- */
    .tabs{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0 16px;border-bottom:1px solid #eee}
    .tab {
      padding: 10px 16px;
      border: none;
      border-radius: 8px 8px 0 0;
      background: linear-gradient(135deg, #9b30ff, #000000);
      color: white;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.2s ease, color 0.2s ease;
    }
    .tab.active {
      background: #f2f2f2;
      color: #333;
      font-weight: 700;
    }
    .tab:hover:not(.active) { opacity: 0.8; }

    /* ---- Cards / common ---- */
    h1 {
      background: linear-gradient(135deg, #9b30ff, #000000);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-size: 2.2em;
      margin: 16px 0;
    }
    .card{border:1px solid #ddd;border-radius:var(--br);padding:16px;margin-bottom:16px;background:#fff}
    .btn{display:inline-block;padding:.5rem 1rem;border:1px solid #aaa;border-radius:8px;cursor:pointer;margin-right:.5rem;background:#f7f7f7}
    .btn:active{transform:scale(.98)}
    pre{background:#f7f7f7;border-radius:8px;padding:8px;max-height:320px;overflow:auto;margin-top:8px}
    .row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    table{border-collapse:collapse;width:100%;margin-top:8px}
    th,td{border:1px solid #eee;padding:6px 8px;text-align:left}
    th{background:#fafafa}
    input[type=text],input[type=password],select,textarea{padding:.4rem .6rem;border:1px solid #ccc;border-radius:6px}
    small{color:#666}
    .section-title{font-size:1.2rem;font-weight:700;margin-bottom:8px}
    .panel{margin-top:8px}
    .hide{display:none}
  </style>
</head>
<body>
  <div class="container">
    <h1>DXlink Dashboard (dev)</h1>

    <!-- Tabs bar -->
    <div class="tabs" id="tabs">
      <button class="tab" data-tab="status">Status</button>
      <button class="tab" data-tab="svx">SVXLink</button>
      <button class="tab" data-tab="sa818">SA818</button>
      <button class="tab" data-tab="aprs">APRS</button>
      <button class="tab" data-tab="wifi">Wi‑Fi</button>
      <button class="tab" data-tab="logs">Logs</button>
      <button class="tab" data-tab="term">Terminal</button>
      <button class="tab" data-tab="config">Config</button>
    </div>

    <!-- ===== STATUS ===== -->
    <div id="p-status" class="panel">
      <div class="card" id="sys">Loading…</div>
    </div>

    <!-- ===== SVXLINK ===== -->
    <div id="p-svx" class="panel hide">
      <div class="card">
        <div class="section-title">SVXLink</div>
        <div class="row" style="margin-bottom:8px">
          <button class="btn" id="svxStart">Start</button>
          <button class="btn" id="svxStop">Stop</button>
          <button class="btn" id="svxRestart">Restart</button>
          <small id="svxMsg"></small>
        </div>
        <div class="row" style="margin-bottom:8px">
          <label>Profile:
            <select id="svxProfile">
              <option value="default">default</option>
            </select>
          </label>
          <button class="btn" id="svxProfileLoad">Load</button>
          <button class="btn" id="svxProfileSave">Save</button>
        </div>

        <!-- Raw config (mock, din data/svxlink.conf) -->
        <div class="section-title">SVXLink Config (raw)</div>
        <div class="row" style="margin-bottom:8px">
          <button class="btn" id="cfgLoad">Load</button>
          <button class="btn" id="cfgSave">Save</button>
          <small id="cfgMsg"></small>
        </div>
        <textarea id="cfgText" style="width:100%;height:260px;font-family:ui-monospace,Consolas,monospace"></textarea>
      </div>
    </div>

    <!-- ===== SA818 ===== -->
    <div id="p-sa818" class="panel hide">
      <div class="card">
        <div class="section-title">SA818</div>
        <small>Programator canal – în lucru (mock în pasul următor).</small>
      </div>
    </div>

    <!-- ===== APRS ===== -->
    <div id="p-aprs" class="panel hide">
      <div class="card">
        <div class="section-title">APRS</div>
        <small>Config Direwolf + gpsd – în lucru (mock în pasul următor).</small>
      </div>
    </div>

    <!-- ===== WIFI ===== -->
    <div id="p-wifi" class="panel hide">
      <div class="card" id="wifi">
        <div class="section-title">Wi‑Fi</div>
        <div id="wstatus" style="margin:8px 0;"><small>Loading…</small></div>
        <div class="row">
          <button class="btn" id="wscan">Scan</button>
          <button class="btn" id="wdisconnect">Disconnect</button>
        </div>
        <div id="wnets"></div>
        <div id="wconnect" style="margin-top:10px;display:none">
          <div class="row">
            <input id="ssid" type="text" placeholder="SSID" />
            <input id="pass" type="password" placeholder="Password (leave empty for Open)" />
            <button class="btn" id="wconnectBtn">Connect</button>
          </div>
          <small id="whelp"></small>
        </div>
      </div>
    </div>

    <!-- ===== LOGS ===== -->
    <div id="p-logs" class="panel hide">
      <div class="card" id="logs">
        <div class="section-title">Logs live</div>
        <div class="row" style="margin-top:8px">
          <button class="btn" id="logStart">Start</button>
          <button class="btn" id="logStop">Stop</button>
          <button class="btn" id="logClear">Clear</button>
          <label><input type="checkbox" id="autoscroll" checked> Auto‑scroll</label>
        </div>
        <pre id="logbox"></pre>
      </div>
    </div>

    <!-- ===== TERMINAL ===== -->
    <div id="p-term" class="panel hide">
      <div class="card">
        <div class="section-title">Terminal</div>
        <small>Integrare ttyd / run‑command – în lucru.</small>
      </div>
    </div>

    <!-- ===== CONFIG ===== -->
    <div id="p-config" class="panel hide">
      <div class="card" id="config">
        <div class="section-title">Config</div>

        <div class="row" style="margin-bottom:8px">
          <label>Hostname: <input type="text" id="hostName" placeholder="dxlink-node"></label>
          <label>Timezone:
            <select id="tz">
              <option value="UTC">UTC</option>
              <option value="Europe/Bucharest">Europe/Bucharest</option>
              <option value="Europe/Berlin">Europe/Berlin</option>
              <option value="Europe/London">Europe/London</option>
            </select>
          </label>
          <button class="btn" id="setTz">Set TZ</button>
          <small id="ctrlMsg"></small>
        </div>

        <div class="row" style="margin-bottom:8px">
          <button class="btn" id="fstoggle">Switch FS RO/RW</button>
          <span>FS: <b><span id="fsval">RW</span></b></span>
        </div>

        <div class="row" style="margin-bottom:8px">
          <button class="btn" id="btnRestartSvx">Restart SVXLink</button>
          <button class="btn" id="updateDash">Update Dashboard</button>
        </div>

        <div class="row" style="margin-bottom:8px">
          <button class="btn" id="reboot">Reboot</button>
          <button class="btn" id="halt">Shutdown</button>
        </div>
      </div>
    </div>

  </div> <!-- /.container -->

  <script>
  // ===== Tabs switching (robust) =====
  document.addEventListener('DOMContentLoaded', function(){
    const tabs = Array.from(document.querySelectorAll('#tabs .tab[data-tab]'));
    const panels = Array.from(document.querySelectorAll('.panel'));
    if (!tabs.length) return;

    function showTab(name) {
      panels.forEach(p => p.classList.add('hide'));
      const panel = document.getElementById('p-' + name);
      if (panel) { panel.classList.remove('hide'); }
      else { const st = document.getElementById('p-status'); if (st) st.classList.remove('hide'); }

      tabs.forEach(t => t.classList.remove('active'));
      const btn = document.querySelector('#tabs .tab[data-tab="'+name+'"]');
      if (btn) btn.classList.add('active');

      try { localStorage.setItem('dx_tab', name); } catch(e){}
      if (history.replaceState) history.replaceState(null, '', '#'+name);
    }

    tabs.forEach(t => t.addEventListener('click', function(){ showTab(this.dataset.tab); }));
    const fromHash = (location.hash || '').replace('#','');
    const initial = fromHash || localStorage.getItem('dx_tab') || 'status';
    showTab(initial);
  });

  // ===== STATUS =====
  const el = document.getElementById("sys");
  function renderStatus(d, fsState) {
    el.innerHTML =
      "<b>Status</b><br>" +
      "Uptime: " + d.uptime + "<br>" +
      "CPU Temp: " + d.cpu_temp + "<br>" +
      'FS: <span id="fsvalStatus">' + (fsState?.fs || d.fs || "RW") + "</span>";
  }
  function loadStatus() {
    Promise.all([
      fetch("api/system/summary.php?t=" + Date.now()).then(r=>r.json()),
      fetch("api/system/fs.php?t=" + Date.now()).then(r=>r.json())
    ]).then(([summary, fs]) => renderStatus(summary, fs))
     .catch(e => { el.textContent = "Eroare: " + e; });
  }
  loadStatus();

  // ===== CONFIG (FS + control mock) =====
  const fsBtn = document.getElementById("fstoggle");
  function updateFsVal(val){ const v1=document.getElementById("fsval"); if(v1) v1.textContent=val; const v2=document.getElementById("fsvalStatus"); if(v2) v2.textContent=val; }
  if (fsBtn) {
    fsBtn.onclick = () => {
      fsBtn.disabled = true;
      fetch("api/system/fs.php", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"action=toggle"
      }).then(r=>r.json())
        .then(res => updateFsVal(res.fs))
        .finally(()=> fsBtn.disabled=false);
    };
  }
  const ctrlMsg = document.getElementById("ctrlMsg");
  function callCtrl(body){
    if (!ctrlMsg) return;
    ctrlMsg.textContent = "Working…";
    return fetch("api/system/control.php", {
      method:"POST",
      headers:{"Content-Type":"application/x-www-form-urlencoded"},
      body:new URLSearchParams(body).toString()
    }).then(r=>r.json()).then(j=>{
      ctrlMsg.textContent = j.ok ? "Done" : ("Error: " + (j.error||""));
      return j;
    }).catch(e=>{ ctrlMsg.textContent = "Error: " + e; });
  }
  const btnRestartSvx = document.getElementById("btnRestartSvx");
  if (btnRestartSvx) btnRestartSvx.onclick = ()=> callCtrl({action:"svx-restart"});
  const btnUpd = document.getElementById("updateDash");
  if (btnUpd) btnUpd.onclick = ()=> callCtrl({action:"update-dashboard"});
  const btnReboot = document.getElementById("reboot");
  if (btnReboot) btnReboot.onclick = ()=> callCtrl({action:"reboot"});
  const btnHalt = document.getElementById("halt");
  if (btnHalt) btnHalt.onclick = ()=> callCtrl({action:"halt"});
  const btnTZ = document.getElementById("setTz");
  if (btnTZ) btnTZ.onclick = ()=> callCtrl({action:"timezone", tz: document.getElementById("tz").value});

  // ===== WIFI (mock) =====
  const wstatus = document.getElementById("wstatus");
  const wnets   = document.getElementById("wnets");
  const wscan   = document.getElementById("wscan");
  const wdisc   = document.getElementById("wdisconnect");
  const wform   = document.getElementById("wconnect");
  const wbtn    = document.getElementById("wconnectBtn");
  const ssidInp = document.getElementById("ssid");
  const passInp = document.getElementById("pass");
  const whelp   = document.getElementById("whelp");
  let selected = null, selectedSecure = "WPA2";

  function wifiStatus(){
    fetch("api/wifi/status.php?t="+Date.now()).then(r=>r.json()).then(s=>{
      if (s.connected) {
        wstatus.innerHTML = "Connected to <b>"+s.ssid+"</b> ("+(s.rssi||"?")+" dBm), IP <code>"+s.ip+"</code>";
        wform.style.display = "none";
      } else {
        wstatus.innerHTML = "Not connected.";
      }
    });
  }
  function renderScan(list){
    if (!list.networks?.length){ wnets.innerHTML = "<small>No networks found.</small>"; return; }
    let html = "<table><thead><tr><th>SSID</th><th>Signal</th><th>Security</th><th></th></tr></thead><tbody>";
    list.networks.forEach(n=>{
      html += "<tr><td>"+n.ssid+"</td><td>"+n.rssi+" dBm</td><td>"+n.secure+
              '</td><td><button class="btn" data-ssid="'+n.ssid.replace(/\"/g,"&quot;")+'" data-sec="'+n.secure+'">Select</button></td></tr>';
    });
    html += "</tbody></table>";
    wnets.innerHTML = html;
    wnets.querySelectorAll("button[data-ssid]").forEach(btn=>{
      btn.onclick = ()=>{
        selected = btn.getAttribute("data-ssid");
        selectedSecure = btn.getAttribute("data-sec") || "WPA2";
        ssidInp.value = selected; passInp.value = "";
        wform.style.display = "block";
        whelp.textContent = (selectedSecure==="Open") ? "Network is Open: leave password empty." : "Enter password (>= 8 chars).";
      };
    });
  }
  function scan(){
    wnets.innerHTML = "<small>Scanning…</small>";
    fetch("api/wifi/scan.php?t="+Date.now()).then(r=>r.json()).then(renderScan);
  }
  function disconnect(){
    fetch("api/wifi/connect.php", {
      method:"POST",
      headers:{"Content-Type":"application/x-www-form-urlencoded"},
      body:"action=disconnect"
    }).then(r=>r.json()).then(()=>{ wifiStatus(); });
  }
  function doConnect(){
    if (!ssidInp.value){ alert("Select an SSID from the list first."); return; }
    wbtn.disabled = true;
    const params = new URLSearchParams({action:"connect", ssid:ssidInp.value, pass:passInp.value, secure:selectedSecure});
    fetch("api/wifi/connect.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body: params.toString() })
      .then(r=>r.json())
      .then(res=>{ if (res.ok) { wform.style.display = "none"; wifiStatus(); } else { alert("Connect failed: " + (res.error||"unknown")); } })
      .finally(()=> wbtn.disabled=false);
  }
  if (wscan) wscan.onclick = scan;
  if (wdisc) wdisc.onclick = disconnect;
  if (wbtn)  wbtn.onclick  = doConnect;
  wifiStatus();

  // ===== LOGS (SSE mock) =====
  let es = null;
  const logbox = document.getElementById("logbox");
  const autoscroll = document.getElementById("autoscroll");
  function appendLine(txt){ if(!logbox) return; logbox.textContent += (txt+"\n"); if (autoscroll && autoscroll.checked){ logbox.scrollTop = logbox.scrollHeight; } }
  function startLogs(){
    if (es) return;
    es = new EventSource("api/system/logs.php");
    es.addEventListener("log", (ev)=>{ try{ appendLine(JSON.parse(ev.data).line); }catch(e){ appendLine("parse error"); } });
    es.onerror = ()=>{ appendLine("[eventsource closed]"); stopLogs(); };
    appendLine("--- logs started ---");
  }
  function stopLogs(){ if (es){ es.close(); es=null; } }
  const bStart = document.getElementById("logStart");
  const bStop  = document.getElementById("logStop");
  const bClear = document.getElementById("logClear");
  if (bStart) bStart.onclick = startLogs;
  if (bStop)  bStop.onclick  = stopLogs;
  if (bClear) bClear.onclick = ()=>{ if(logbox) logbox.textContent=""; };

  // ===== SVX CONFIG (raw editor mock) =====
  const cfgText = document.getElementById('cfgText');
  const cfgMsg  = document.getElementById('cfgMsg');
  function cfgLoad(){
    if(!cfgMsg || !cfgText) return;
    cfgMsg.textContent = 'Loading...';
    fetch('api/svx/config.php')
      .then(r=>r.json())
      .then(j=>{ cfgText.value = j.content || ''; cfgMsg.textContent = 'Loaded.'; })
      .catch(e=>{ cfgMsg.textContent = 'Error: ' + e; });
  }
  function cfgSave(){
    if(!cfgMsg || !cfgText) return;
    cfgMsg.textContent = 'Saving...';
    const body = new URLSearchParams({content: cfgText.value}).toString();
    fetch('api/svx/config.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
      .then(r=>r.json())
      .then(j=>{ cfgMsg.textContent = j.ok ? 'Saved.' : ('Error: ' + j.error); })
      .catch(e=>{ cfgMsg.textContent = 'Error: ' + e; });
  }
  const bCfgLoad = document.getElementById('cfgLoad');
  const bCfgSave = document.getElementById('cfgSave');
  if (bCfgLoad) bCfgLoad.onclick = cfgLoad;
  if (bCfgSave) bCfgSave.onclick = cfgSave;
  // auto-load cfg când intri în tab SVX (opțional putem adăuga un observer de schimbare tab)
  </script>
</body>
</html>
