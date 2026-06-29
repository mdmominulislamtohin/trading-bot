<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRTS Pro Multi-Directional Monitor | ADA 3m</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #0b0e11; --surf: #181a20; --border: #2b3139; --text: #eaecef; 
            --accent-long: #3d79f2; --accent-short: #f6465d; 
            --up: #0ecb81; --down: #f6465d; --warn: #f39c12; --purple: #8a2be2;
        }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; padding-bottom: 50px; }
        .container { max-width: 1350px; margin: 0 auto; padding: 20px; }
        
        /* Header Stats */
        .header-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .ticker-card { background: var(--surf); border: 1px solid var(--border); padding: 15px 20px; border-radius: 12px; }
        .t-label { font-size: 0.75rem; color: #848e9c; display: block; margin-bottom: 5px; font-weight: 600; text-transform: uppercase; }
        .t-val { font-size: 1.3rem; font-weight: 700; display: flex; align-items: center; gap: 8px;}

        /* Tab Navigation */
        .tab-nav { display: flex; gap: 10px; margin-bottom: 25px; background: var(--surf); padding: 8px; border-radius: 12px; border: 1px solid var(--border); }
        .tab-btn { flex: 1; padding: 12px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; background: transparent; color: #848e9c; text-transform: uppercase; letter-spacing: 0.5px;}
        .tab-btn:hover { background: rgba(255,255,255,0.05); color: var(--text); }
        
        .tab-btn.active.long-tab { background: rgba(61, 121, 242, 0.15); color: var(--accent-long); border: 1px solid rgba(61, 121, 242, 0.3); }
        .tab-btn.active.short-tab { background: rgba(246, 70, 93, 0.15); color: var(--accent-short); border: 1px solid rgba(246, 70, 93, 0.3); }

        .view-section { display: none; animation: fadeIn 0.3s ease-in-out; }
        .view-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .section-title { margin: 20px 0 15px 5px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .pulse { height: 8px; width: 8px; background: var(--purple); border-radius: 50%; animation: blink 1s infinite; }
        .pulse-long { background: var(--accent-long); }
        .pulse-short { background: var(--down); }
        .pulse-warn { background: var(--warn); }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }

        /* Multi-RR Grid */
        .rr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 30px; }
        .rr-card { background: linear-gradient(180deg, rgba(24,26,32,1) 0%, rgba(30,35,41,1) 100%); border: 1px solid var(--border); border-radius: 12px; padding: 12px; position: relative; overflow: hidden; transition: 0.3s;}
        .rr-card.best-performer { border-color: var(--purple); box-shadow: 0 4px 15px rgba(138,43,226,0.15); }
        .best-badge { position: absolute; top: 0; right: 0; background: var(--purple); color: #fff; font-size: 0.55rem; font-weight: 800; padding: 3px 6px; border-radius: 0 0 0 8px; text-transform: uppercase;}
        
        .rr-title { font-size: 1rem; font-weight: 800; margin-bottom: 12px; text-align: center; border-bottom: 1px dashed rgba(255,255,255,0.05); padding-bottom: 8px;}
        .long-title { color: var(--accent-long); }
        .short-title { color: var(--accent-short); }
        .rr-card.best-performer .rr-title { color: var(--purple); }
        
        .rr-stats-row { display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 6px; color: #848e9c; font-weight: 600;}
        .rr-pnl-box { background: rgba(0,0,0,0.2); border-radius: 8px; padding: 8px; margin-top: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.02);}
        .rr-net-pts { font-size: 0.9rem; font-weight: 700; margin-bottom: 4px; }
        .rr-net-usd { font-size: 1.2rem; font-weight: 800; }

        /* Tables */
        .table-container { overflow-x: auto; border-radius: 12px; border: 1px solid var(--border); background: var(--surf); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: top; }
        th { background: #1e2329; color: #848e9c; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        
        tr.is-running { background: rgba(243, 156, 18, 0.03); }
        tr.is-running td:first-child { border-left: 3px solid var(--warn); }

        .price-tag { display: block; font-size: 0.85rem; margin-bottom: 4px; font-family: monospace; }
        .p-entry-long { color: var(--accent-long); font-weight: 700; }
        .p-entry-short { color: var(--warn); font-weight: 700; }
        .p-sl { color: var(--down); font-weight: 700; }
        .p-tp { color: var(--up); font-weight: 500; }

        .badge-mini { display: inline-block; padding: 4px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; margin: 2px; border: 1px solid transparent; min-width: 45px; text-align: center;}
        .b-win { background: rgba(14,203,129,0.1); color: var(--up); border-color: rgba(14,203,129,0.3); }
        .b-loss { background: rgba(246,70,93,0.1); color: var(--down); border-color: rgba(246,70,93,0.3); }
        .b-run { background: rgba(243,156,18,0.1); color: var(--warn); border-color: rgba(243,156,18,0.3); animation: blink 1.5s infinite;}

        .duration-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; margin-top: 8px; font-weight: 600; background: rgba(255,255,255,0.05); color: var(--text); }
        .duration-badge.live { background: rgba(243, 156, 18, 0.15); color: var(--warn); border: 1px solid rgba(243, 156, 18, 0.3); }

        .analysis-box { background: rgba(255,255,255,0.02); padding: 10px; border-radius: 8px; font-size: 0.8rem; border-left: 3px solid var(--border); line-height: 1.5; }
        .analysis-box.long-box { border-left-color: var(--accent-long); }
        .analysis-box.short-box { border-left-color: var(--down); }
        
        .text-up { color: var(--up); }
        .text-down { color: var(--down); }
        .text-warn { color: var(--warn); }
    </style>
</head>
<body>

<div class="container">
    <div class="header-stats">
        <div class="ticker-card">
            <span class="t-label">ADA/USDT Live</span>
            <span class="t-val" id="cur-price">0.00000</span>
        </div>
        <div class="ticker-card">
            <span class="t-label">24h High / Low</span>
            <span class="t-val"><span id="high-24h" style="color:var(--up); font-size:1.1rem;">0.00</span> <span style="color:#848e9c; font-size:0.9rem;">/</span> <span id="low-24h" style="color:var(--down); font-size:1.1rem;">0.00</span></span>
        </div>
        <div class="ticker-card">
            <span class="t-label">Active Signals (Long)</span>
            <span class="t-val"><span class="pulse pulse-long"></span> <span id="active-long" style="color: var(--accent-long);">0</span></span>
        </div>
        <div class="ticker-card">
            <span class="t-label">Active Signals (Short)</span>
            <span class="t-val"><span class="pulse pulse-down"></span> <span id="active-short" style="color: var(--down);">0</span></span>
        </div>
    </div>

    <div class="tab-nav">
        <button class="tab-btn active long-tab" onclick="switchTab('long')">🟢 Bullish Setup (Long)</button>
        <button class="tab-btn short-tab" onclick="switchTab('short')">🔴 Bearish Setup (Short)</button>
    </div>

    <div id="long-view" class="view-section active">
        <div class="section-title">
            <div class="pulse pulse-up"></div>
            <b>Multi-Ratio Simulation (Long 1000 ADA)</b>
        </div>
        <div class="rr-grid" id="long-grid-container"></div>

        <div class="section-title">
            <div class="pulse pulse-warn"></div>
            <b>Bullish Breakdown (Long Buying)</b>
            <span style="font-size:0.8rem; color:#848e9c; margin-left:auto;">Total: <span id="total-long">0</span></span>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Entry & Duration</th>
                        <th style="width: 15%;">Primary Levels (Long)</th>
                        <th style="width: 25%;">Take Profit (TP) Levels</th>
                        <th style="width: 20%;">Ratio Results</th>
                        <th style="width: 25%;">Analysis</th>
                    </tr>
                </thead>
                <tbody id="long-log-body">
                    <tr><td colspan="5" style="text-align:center; padding: 40px; color: #848e9c;">Syncing data for Bullish Patterns...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="short-view" class="view-section">
        <div class="section-title">
            <div class="pulse pulse-down"></div>
            <b>Multi-Ratio Simulation (Short 1000 ADA)</b>
        </div>
        <div class="rr-grid" id="short-grid-container"></div>

        <div class="section-title">
            <div class="pulse pulse-warn"></div>
            <b>Bearish Breakdown (Short Selling)</b>
            <span style="font-size:0.8rem; color:#848e9c; margin-left:auto;">Total: <span id="total-short">0</span></span>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Entry & Duration</th>
                        <th style="width: 15%;">Primary Levels (Short)</th>
                        <th style="width: 25%;">Take Profit (TP) Levels</th>
                        <th style="width: 20%;">Ratio Results</th>
                        <th style="width: 25%;">Analysis</th>
                    </tr>
                </thead>
                <tbody id="short-log-body">
                    <tr><td colspan="5" style="text-align:center; padding: 40px; color: #848e9c;">Syncing data for Bearish Patterns...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    const POINT_SIZE = 0.0001; 
    const ADA_VOL = 1000;
    const USD_PER_POINT = POINT_SIZE * ADA_VOL; 
    const RR_TARGETS = [1, 2, 3, 4, 5, 7, 10, 15]; 

    function switchTab(type) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.view-section').forEach(sec => sec.classList.remove('active'));
        
        document.querySelector(`.${type}-tab`).classList.add('active');
        document.getElementById(`${type}-view`).classList.add('active');
    }

    function formatDuration(startSec, endSec) {
        let diff = endSec - startSec;
        if (diff <= 0) return "< 1m";
        let h = Math.floor(diff / 3600);
        let m = Math.floor((diff % 3600) / 60);
        if (h > 0) return `${h}h ${m}m`;
        return `${m}m`;
    }

    async function updateData() {
        try {
            const res = await fetch('crts-api.php?t=' + Date.now());
            const data = await res.json();
            if (data.status === 'success' && data.candles) {
                if (data.ticker) {
                    let curPr = parseFloat(data.ticker.lastPr);
                    let openUtc = parseFloat(data.ticker.openUtc);
                    let color = curPr >= openUtc ? 'var(--up)' : 'var(--down)';
                    
                    let priceEl = document.getElementById('cur-price');
                    priceEl.innerText = curPr.toFixed(5);
                    priceEl.style.color = color;
                    
                    document.getElementById('high-24h').innerText = parseFloat(data.ticker.high24h).toFixed(5);
                    document.getElementById('low-24h').innerText = parseFloat(data.ticker.low24h).toFixed(5);
                }

                const chartData = data.candles.map(c => ({
                    time: parseInt(c[0]) / 1000,
                    open: parseFloat(c[1]), high: parseFloat(c[2]), low: parseFloat(c[3]), close: parseFloat(c[4])
                }));

                runBullishSimulation(chartData);
                runBearishSimulation(chartData);
            }
        } catch(e) { console.error("Sync error", e); }
    }

    // ================= LONG SIMULATION =================
    function runBullishSimulation(data) {
        const trades = [];
        let stats = {};
        RR_TARGETS.forEach(rr => { stats[rr] = { wins: 0, losses: 0, running: 0, gainPts: 0, lossPts: 0 }; });

        for (let i = 1; i < data.length - 1; i++) {
            let c1 = data[i-1], c2 = data[i];
            
            if (c1.close >= c1.open && c2.close >= c2.open && c2.low > c1.low) {
                let entryCandle = data[i+1];
                if (!entryCandle) continue;

                let entryPrice = entryCandle.open;
                let sl = c1.low - POINT_SIZE; 
                let riskPts = (entryPrice - sl) / POINT_SIZE;
                if (riskPts <= 0) continue;

                let trade = { entryTime: entryCandle.time, entryPrice, sl, riskPts, maxPrice: entryPrice, overallExitTime: null, rrResults: {} };

                RR_TARGETS.forEach(rr => {
                    trade.rrResults[rr] = { tp: entryPrice + (riskPts * POINT_SIZE * rr), status: 'Running', result: null };
                });

                let isDead = false;
                for (let j = i + 1; j < data.length; j++) {
                    let f = data[j];
                    if (!isDead && f.high > trade.maxPrice) trade.maxPrice = f.high; 
                    
                    if (!isDead && f.low <= sl) {
                        isDead = true; trade.overallExitTime = f.time;
                        RR_TARGETS.forEach(rr => { if (trade.rrResults[rr].status === 'Running') { trade.rrResults[rr].status = 'Closed'; trade.rrResults[rr].result = 'Loss'; } });
                        break;
                    }
                    if (!isDead) {
                        RR_TARGETS.forEach(rr => { if (trade.rrResults[rr].status === 'Running' && f.high >= trade.rrResults[rr].tp) { trade.rrResults[rr].status = 'Closed'; trade.rrResults[rr].result = 'Win'; trade.overallExitTime = f.time; } });
                    }
                }

                RR_TARGETS.forEach(rr => {
                    let tr = trade.rrResults[rr], st = stats[rr];
                    if (tr.result === 'Win') { st.wins++; st.gainPts += (riskPts * rr); }
                    else if (tr.result === 'Loss') { st.losses++; st.lossPts += riskPts; }
                    else { st.running++; }
                });
                trades.push(trade);
                i += 1; 
            }
        }
        renderLongUI(trades, stats);
    }

    // ================= SHORT SIMULATION =================
    function runBearishSimulation(data) {
        const trades = [];
        let stats = {};
        RR_TARGETS.forEach(rr => { stats[rr] = { wins: 0, losses: 0, running: 0, gainPts: 0, lossPts: 0 }; });

        for (let i = 1; i < data.length - 1; i++) {
            let c1 = data[i-1], c2 = data[i];
            
            if (c1.close <= c1.open && c2.close <= c2.open && c2.high < c1.high) {
                let entryCandle = data[i+1];
                if (!entryCandle) continue;

                let entryPrice = entryCandle.open;
                let sl = c1.high + POINT_SIZE; 
                let riskPts = (sl - entryPrice) / POINT_SIZE;
                if (riskPts <= 0) continue;

                let trade = { entryTime: entryCandle.time, entryPrice, sl, riskPts, minPrice: entryPrice, overallExitTime: null, rrResults: {} };

                RR_TARGETS.forEach(rr => {
                    trade.rrResults[rr] = { tp: entryPrice - (riskPts * POINT_SIZE * rr), status: 'Running', result: null };
                });

                let isDead = false;
                for (let j = i + 1; j < data.length; j++) {
                    let f = data[j];
                    if (!isDead && f.low < trade.minPrice) trade.minPrice = f.low; 
                    
                    if (!isDead && f.high >= sl) {
                        isDead = true; trade.overallExitTime = f.time;
                        RR_TARGETS.forEach(rr => { if (trade.rrResults[rr].status === 'Running') { trade.rrResults[rr].status = 'Closed'; trade.rrResults[rr].result = 'Loss'; } });
                        break;
                    }
                    if (!isDead) {
                        RR_TARGETS.forEach(rr => { if (trade.rrResults[rr].status === 'Running' && f.low <= trade.rrResults[rr].tp) { trade.rrResults[rr].status = 'Closed'; trade.rrResults[rr].result = 'Win'; trade.overallExitTime = f.time; } });
                    }
                }

                RR_TARGETS.forEach(rr => {
                    let tr = trade.rrResults[rr], st = stats[rr];
                    if (tr.result === 'Win') { st.wins++; st.gainPts += (riskPts * rr); }
                    else if (tr.result === 'Loss') { st.losses++; st.lossPts += riskPts; }
                    else { st.running++; }
                });
                trades.push(trade);
                i += 1; 
            }
        }
        renderShortUI(trades, stats);
    }

    // ================= RENDERING HELPERS =================
    function renderLongUI(trades, stats) {
        document.getElementById('total-long').innerText = trades.length;
        let gridHtml = ''; let highestPnl = -999999; let bestRR = null;
        
        RR_TARGETS.forEach(rr => {
            let pnl = (stats[rr].gainPts - stats[rr].lossPts) * USD_PER_POINT;
            if (pnl > highestPnl && trades.length > 0) { highestPnl = pnl; bestRR = rr; }
        });
        
        RR_TARGETS.forEach(rr => {
            let s = stats[rr], netPts = s.gainPts - s.lossPts, pnl = netPts * USD_PER_POINT;
            let ptClass = netPts >= 0 ? 'text-up' : 'text-down';
            gridHtml += `<div class="rr-card ${rr === bestRR ? 'best-performer' : ''}">${rr === bestRR ? '<div class="best-badge">🏆 Most Profitable</div>' : ''}<div class="rr-title long-title">Ratio 1:${rr}</div><div class="rr-stats-row"><span>Win (TP):</span><span class="text-up">${s.wins}</span></div><div class="rr-stats-row"><span>Loss (SL):</span><span class="text-down">${s.losses}</span></div><div class="rr-pnl-box"><div class="rr-net-pts ${ptClass}">Pts: ${netPts>=0?'+':''}${netPts.toFixed(1)}</div><div class="rr-net-usd ${ptClass}">PnL: ${pnl>=0?'+$':'-$'}${Math.abs(pnl).toFixed(2)}</div></div></div>`;
        });
        document.getElementById('long-grid-container').innerHTML = gridHtml;

        let logHtml = ''; let activeCount = 0; const currentUnixTime = Math.floor(Date.now() / 1000);
        
        trades.sort((a, b) => {
            let aR = Object.values(a.rrResults).some(r => r.status === 'Running'), bR = Object.values(b.rrResults).some(r => r.status === 'Running');
            if (aR && !bR) return -1; if (!aR && bR) return 1; return b.entryTime - a.entryTime; 
        });

        trades.forEach(t => {
            let isOverallRunning = Object.values(t.rrResults).some(r => r.status === 'Running');
            if (isOverallRunning) activeCount++;
            
            let maxGainPts = ((t.maxPrice - t.entryPrice) / POINT_SIZE).toFixed(1);
            let entryDate = new Date(t.entryTime*1000).toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});
            let durationInfo = isOverallRunning ? `<div class="duration-badge live">⏱️ Live: ${formatDuration(t.entryTime, currentUnixTime)}</div>` : `<div class="duration-badge">Resolved: ${formatDuration(t.entryTime, t.overallExitTime)}</div>`;

            let tpListHtml = '';
            RR_TARGETS.forEach(rr => {
                let isHit = t.rrResults[rr].result === 'Win' ? 'style="color:var(--up); font-weight:700;"' : 'style="color:#848e9c;"';
                tpListHtml += `<span class="price-tag" ${isHit}>TP (1:${rr}): ${t.rrResults[rr].tp.toFixed(5)}</span>`;
            });

            let badgesHtml = '';
            RR_TARGETS.forEach(rr => {
                let res = t.rrResults[rr].result;
                let bClass = res === 'Win' ? 'b-win' : (res === 'Loss' ? 'b-loss' : 'b-run');
                let bText = res === 'Win' ? `1:${rr} ✓` : (res === 'Loss' ? `1:${rr} ✗` : `1:${rr} ⌛`);
                badgesHtml += `<span class="badge-mini ${bClass}">${bText}</span>`;
            });

            logHtml += `
                <tr class="${isOverallRunning ? 'is-running' : ''}">
                    <td><div style="font-weight:700;">${entryDate}</div>${durationInfo}</td>
                    <td><span class="price-tag p-entry-long">LONG: ${t.entryPrice.toFixed(5)}</span><span class="price-tag p-sl">SL: ${t.sl.toFixed(5)}</span><div style="font-size:0.75rem; color:#848e9c; margin-top:5px;">Risk: ${t.riskPts.toFixed(1)} Pts</div></td>
                    <td><div style="display:flex; flex-direction:column; gap:2px;">${tpListHtml}</div></td>
                    <td><div style="display:flex; flex-wrap:wrap; gap:4px;">${badgesHtml}</div></td>
                    <td><div class="analysis-box long-box">Max Peak reached: <b>+${maxGainPts} Pts</b>.<br>Market High: $${t.maxPrice.toFixed(5)}</div></td>
                </tr>`;
        });
        document.getElementById('long-log-body').innerHTML = logHtml || '<tr><td colspan="5" style="text-align:center;">No bullish patterns found.</td></tr>';
        document.getElementById('active-long').innerText = activeCount;
    }

    function renderShortUI(trades, stats) {
        document.getElementById('total-short').innerText = trades.length;
        let gridHtml = ''; let highestPnl = -999999; let bestRR = null;
        
        RR_TARGETS.forEach(rr => {
            let pnl = (stats[rr].gainPts - stats[rr].lossPts) * USD_PER_POINT;
            if (pnl > highestPnl && trades.length > 0) { highestPnl = pnl; bestRR = rr; }
        });
        
        RR_TARGETS.forEach(rr => {
            let s = stats[rr], netPts = s.gainPts - s.lossPts, pnl = netPts * USD_PER_POINT;
            let ptClass = netPts >= 0 ? 'text-up' : 'text-down';
            gridHtml += `<div class="rr-card ${rr === bestRR ? 'best-performer' : ''}">${rr === bestRR ? '<div class="best-badge">🏆 Most Profitable</div>' : ''}<div class="rr-title short-title">Ratio 1:${rr}</div><div class="rr-stats-row"><span>Win (TP):</span><span class="text-up">${s.wins}</span></div><div class="rr-stats-row"><span>Loss (SL):</span><span class="text-down">${s.losses}</span></div><div class="rr-pnl-box"><div class="rr-net-pts ${ptClass}">Pts: ${netPts>=0?'+':''}${netPts.toFixed(1)}</div><div class="rr-net-usd ${ptClass}">PnL: ${pnl>=0?'+$':'-$'}${Math.abs(pnl).toFixed(2)}</div></div></div>`;
        });
        document.getElementById('short-grid-container').innerHTML = gridHtml;

        let logHtml = ''; let activeCount = 0; const currentUnixTime = Math.floor(Date.now() / 1000);
        
        trades.sort((a, b) => {
            let aR = Object.values(a.rrResults).some(r => r.status === 'Running'), bR = Object.values(b.rrResults).some(r => r.status === 'Running');
            if (aR && !bR) return -1; if (!aR && bR) return 1; return b.entryTime - a.entryTime; 
        });

        trades.forEach(t => {
            let isOverallRunning = Object.values(t.rrResults).some(r => r.status === 'Running');
            if (isOverallRunning) activeCount++;
            
            let maxGainPts = ((t.entryPrice - t.minPrice) / POINT_SIZE).toFixed(1);
            let entryDate = new Date(t.entryTime*1000).toLocaleString([], {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});
            let durationInfo = isOverallRunning ? `<div class="duration-badge live">⏱️ Live: ${formatDuration(t.entryTime, currentUnixTime)}</div>` : `<div class="duration-badge">Resolved: ${formatDuration(t.entryTime, t.overallExitTime)}</div>`;

            let tpListHtml = '';
            RR_TARGETS.forEach(rr => {
                let isHit = t.rrResults[rr].result === 'Win' ? 'style="color:var(--up); font-weight:700;"' : 'style="color:#848e9c;"';
                tpListHtml += `<span class="price-tag" ${isHit}>TP (1:${rr}): ${t.rrResults[rr].tp.toFixed(5)}</span>`;
            });

            let badgesHtml = '';
            RR_TARGETS.forEach(rr => {
                let res = t.rrResults[rr].result;
                let bClass = res === 'Win' ? 'b-win' : (res === 'Loss' ? 'b-loss' : 'b-run');
                let bText = res === 'Win' ? `1:${rr} ✓` : (res === 'Loss' ? `1:${rr} ✗` : `1:${rr} ⌛`);
                badgesHtml += `<span class="badge-mini ${bClass}">${bText}</span>`;
            });

            logHtml += `
                <tr class="${isOverallRunning ? 'is-running' : ''}">
                    <td><div style="font-weight:700;">${entryDate}</div>${durationInfo}</td>
                    <td><span class="price-tag p-entry-short">SHORT: ${t.entryPrice.toFixed(5)}</span><span class="price-tag p-sl">SL: ${t.sl.toFixed(5)}</span><div style="font-size:0.75rem; color:#848e9c; margin-top:5px;">Risk: ${t.riskPts.toFixed(1)} Pts</div></td>
                    <td><div style="display:flex; flex-direction:column; gap:2px;">${tpListHtml}</div></td>
                    <td><div style="display:flex; flex-wrap:wrap; gap:4px;">${badgesHtml}</div></td>
                    <td><div class="analysis-box short-box">Max Drop reached: <b>+${maxGainPts} Pts</b>.<br>Market Low: $${t.minPrice.toFixed(5)}</div></td>
                </tr>`;
        });
        document.getElementById('short-log-body').innerHTML = logHtml || '<tr><td colspan="5" style="text-align:center;">No bearish patterns found.</td></tr>';
        document.getElementById('active-short').innerText = activeCount;
    }

    updateData();
    setInterval(updateData, 5000);
</script>
</body>
</html>