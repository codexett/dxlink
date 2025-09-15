<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>DXlink Dashboard (dev)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{--b:#e5e7eb;--muted:#666}
    body{font-family:system-ui,Segoe UI,Arial;margin:2rem}
    .tabs{display:flex;gap:8px;border-bottom:1px solid var(--b);margin-bottom:12px}
    .tab{padding:.6rem 1rem;border:1px solid var(--b);border-bottom:none;border-top-left-radius:10px;border-top-right-radius:10px;cursor:pointer;background:#fafafa}
    .tab.active{background:white;font-weight:600}
    .panel{border:1px solid var(--b);border-radius:12px;padding:16px}
    .hide{display:none}
    .btn{display:inline-block;padding:.5rem 1rem;border:1px solid #aaa;border-radius:8px;cursor:pointer;margin-right:.5rem}
    .btn:active{transform:scale(.98)}
    pre{background:#f7f7f7;border-radius:8px;padding:8px;max-height:320px;overflow:auto;margin-top:8px}
    .row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    table{border-collapse:collapse;width:100%;margin-top:8px}
    th,td{border:1px solid #eee;padding:6px 8px;text-align:left}
    th{background:#fafafa}
    input[type=text],input[type=password]{padding:.4rem .6rem;border:1px solid #ccc;border-radius:6px}
    small{color:var(--muted)}
  </style>
</head>
<body>
  <h1>DXlink Dashboard (dev)</h1>

  <!-- NAV -->
  <div class="tabs" id="tabs">
    <div class="tab active" data-target="p-status">Status</div>
    <div class="tab" data-target="p-control">Control</div>
    <div class="tab" data-target="p-wifi">Wi-Fi</div>
    <div class="tab" data-target="p-logs">Logs</div>
  </div>

  <!-- PANELS -->
  <div id="p-status"  class="panel">Loading</div>

  <div id="p-control" class="panel hide">
    <b>Control</b><br>
    <div style="margin-top:8px">
      FS: <span id="fsval">RW</span>
      <button class="btn" id="fstoggle">Switch FS RO/RW</button>
      <small>Inspirat de RoLink (ro/rw).</small>
    </div>
    <div style="margin-top:12px">
      <button class="btn" id="mockReboot">Reboot (mock)</button>
      <button class="btn" id="mockShutdown">Shutdown (mock)</button>
      <small>În varianta pentru Orange Pi, aici vom apela comenzi reale cu sudo.</small>
    </div>
  </div>

<div class="card" id="svx">
  <div class="section-title">SVXLink Config</div>
  <div class="row" style="margin-bottom:8px">
    <button class="btn" id="cfgLoad">Load</button>
    <button class="btn" id="cfgSave">Save</button>
    <small id="cfgMsg"></small>
  </div>
  <textarea id="cfgText" style="width:100%;height:260px;border:1px solid #ccc;border-radius:8px;padding:8px;font-family:ui-monospace,Consolas,monospace"></textarea>
</div>

  <div id="p-wifi" class="panel hide">
    <b>Wi-Fi</b>
    <div id="wstatus" style="margin:8px 0;"><small>Loading</small></div>
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

  <div id="p-logs" class="panel hide">
    <b>Logs live</b>
    <div class="row" style="margin-top:8px">
      <button class="btn" id="logStart">Start</button>
      <button class="btn" id="logStop">Stop</button>
      <button class="btn" id="logClear">Clear</button>
      <label><input type="checkbox" id="autoscroll" checked> Auto-scroll</label>
    </div>
    <pre id="logbox"></pre>
  </div>

  <script>
    // ===== Tabs =====
    const tabs = document.querySelectorAll('.tab');
    const panels = ['p-status','p-control','p-wifi','p-logs'].map(id=>document.getElementById(id));
    tabs.forEach(t => t.onclick = () => {
      tabs.forEach(x=>x.classList.remove('active'));
      t.classList.add('active');
      panels.forEach(p=>p.classList.add('hide'));
      document.getElementById(t.dataset.target).classList.remove('hide');
    });

    // ===== STATUS (summary) =====
    const pStatus = document.getElementById('p-status');
    function loadSummary(){
      Promise.all([
        fetch('api/system/summary.php?t='+Date.now()).then(r=>r.json()),
        fetch('api/system/fs.php?t='+Date.now()).then(r=>r.json())
      ]).then(([s,fs])=>{
        pStatus.innerHTML =
          '<b>Status</b><br>' +
          'Uptime: ' + s.uptime + '<br>' +
          'CPU Temp: ' + s.cpu_temp + '<br>' +
          'FS: ' + (fs.fs||'RW');
        // sincronizeaza si in tab-ul Control
        document.getElementById('fsval').textContent = fs.fs || 'RW';
      }).catch(e=>{ pStatus.textContent = 'Eroare: '+e; });
    }
    loadSummary();

    // ===== CONTROL (FS toggle + mock reboot/shutdown) =====
    function toggleFS(){
      const btn = document.getElementById('fstoggle'); btn.disabled=true;
      fetch('api/system/fs.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=toggle'})
       .then(r=>r.json())
       .then(res=>{
         document.getElementById('fsval').textContent = res.fs;
         // si in Status
         loadSummary();
       })
       .finally(()=>btn.disabled=false);
    }
    document.getElementById('fstoggle').onclick = toggleFS;
    document.getElementById('mockReboot').onclick   = ()=>alert('Reboot (mock)  in prod: systemctl reboot');
    document.getElementById('mockShutdown').onclick = ()=>alert('Shutdown (mock)  in prod: systemctl poweroff');

    // ===== WIFI (mock) =====
    const wstatus = document.getElementById('wstatus');
    const wnets   = document.getElementById('wnets');
    const wscan   = document.getElementById('wscan');
    const wdisc   = document.getElementById('wdisconnect');
    const wform   = document.getElementById('wconnect');
    const wbtn    = document.getElementById('wconnectBtn');
    const ssidInp = document.getElementById('ssid');
    const passInp = document.getElementById('pass');
    const whelp   = document.getElementById('whelp');
    let selected = null, selectedSecure = "WPA2";

    function wifiStatus(){
      fetch('api/wifi/status.php?t='+Date.now()).then(r=>r.json()).then(s=>{
        if (s.connected) { wstatus.innerHTML = 'Connected to <b>'+s.ssid+'</b> ('+(s.rssi||'?')+' dBm), IP <code>'+s.ip+'</code>'; wform.style.display='none'; }
        else { wstatus.innerHTML='Not connected.'; }
      });
    }
    function renderScan(list){
      if (!list.networks?.length){ wnets.innerHTML = '<small>No networks found.</small>'; return; }
      let html='<table><thead><tr><th>SSID</th><th>Signal</th><th>Security</th><th></th></tr></thead><tbody>';
      list.networks.forEach(n=>{
        html += '<tr><td>'+n.ssid+'</td><td>'+n.rssi+' dBm</td><td>'+n.secure+'</td>'+
                '<td><button class="btn" data-ssid="'+n.ssid.replace(/"/g,'&quot;')+'" data-sec="'+n.secure+'">Select</button></td></tr>';
      }); html+='</tbody></table>';
      wnets.innerHTML=html;
      wnets.querySelectorAll('button[data-ssid]').forEach(btn=>{
        btn.onclick=()=>{ selected=btn.dataset.ssid; selectedSecure=btn.dataset.sec||"WPA2";
          ssidInp.value=selected; passInp.value=''; wform.style.display='block';
          whelp.textContent=(selectedSecure==='Open')?'Network is Open: leave password empty.':'Enter password (>= 8 chars).';
        };
      });
    }
    function scan(){ wnets.innerHTML='<small>Scanning</small>'; fetch('api/wifi/scan.php?t='+Date.now()).then(r=>r.json()).then(renderScan); }
    function disconnect(){ fetch('api/wifi/connect.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=disconnect'})
                             .then(r=>r.json()).then(()=>{ wifiStatus(); }); }
    function doConnect(){
      if(!ssidInp.value){ alert('Select an SSID from the list first.'); return; }
      wbtn.disabled=true;
      const p=new URLSearchParams(); p.set('action','connect'); p.set('ssid',ssidInp.value); p.set('pass',passInp.value); p.set('secure',selectedSecure);
      fetch('api/wifi/connect.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p.toString()})
        .then(r=>r.json()).then(res=>{ if(res.ok){ wform.style.display='none'; wifiStatus(); } else { alert('Connect failed: '+(res.error||'unknown')); } })
        .finally(()=>wbtn.disabled=false);
    }
    document.getElementById('wscan').onclick = scan;
    document.getElementById('wdisconnect').onclick = disconnect;
    document.getElementById('wconnectBtn').onclick = doConnect;
    wifiStatus();

    // ===== LOGS (SSE) =====
    let es=null; const logbox=document.getElementById('logbox'); const autoscroll=document.getElementById('autoscroll');
    function appendLine(txt){ logbox.textContent+=(txt+"\\n"); if(autoscroll.checked){ logbox.scrollTop=logbox.scrollHeight; } }
    function startLogs(){ if(es) return; es=new EventSource('api/system/logs.php');
      es.addEventListener('log',ev=>{ try{ appendLine(JSON.parse(ev.data).line);}catch(e){appendLine('parse error');} });
      es.onerror=()=>{ appendLine('[eventsource closed]'); stopLogs(); }; appendLine('--- logs started ---'); }
    function stopLogs(){ if(es){ es.close(); es=null; } }
    document.getElementById('logStart').onclick=startLogs;
    document.getElementById('logStop').onclick=stopLogs;
    document.getElementById('logClear').onclick=()=>{ logbox.textContent=''; };
// ===== SVX CONFIG (mock) =====
const cfgText = document.getElementById('cfgText');
const cfgMsg  = document.getElementById('cfgMsg');

function cfgLoad(){
  cfgMsg.textContent = 'Loading...';
  fetch('api/svx/config.php')
    .then(r=>r.json())
    .then(j=>{
      cfgText.value = j.content || '';
      cfgMsg.textContent = 'Loaded.';
    })
    .catch(e=>{
      cfgMsg.textContent = 'Error: ' + e;
    });
}

function cfgSave(){
  cfgMsg.textContent = 'Saving...';
  const body = new URLSearchParams({content: cfgText.value}).toString();
  fetch('api/svx/config.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body
  })
    .then(r=>r.json())
    .then(j=>{
      cfgMsg.textContent = j.ok ? 'Saved.' : ('Error: ' + j.error);
    })
    .catch(e=>{
      cfgMsg.textContent = 'Error: ' + e;
    });
}

document.getElementById('cfgLoad').onclick = cfgLoad;
document.getElementById('cfgSave').onclick = cfgSave;

cfgLoad(); // auto-load la deschiderea paginii

  </script>
</body>
</html>