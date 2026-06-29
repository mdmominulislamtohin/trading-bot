<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPS Bot Dashboard | Bitget</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0b0e11;
            --surface-color: #181a20;
            --surface-light: #1e2329;
            --border-color: #2b3139;
            --text-primary: #eaecef;
            --text-secondary: #848e9c;
            --accent-color: #fcd535;
            --up-color: #0ecb81;
            --down-color: #f6465d;
            --warn-color: #f39c12;
        }

        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        .container { width: 100%; max-width: 1100px; padding: 2rem; box-sizing: border-box; }
        
        header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.8rem; margin: 0; }
        .header-actions { display: flex; gap: 15px; align-items: center; }
        .btn-validate { background: rgba(252, 213, 53, 0.1); border: 1px solid var(--accent-color); color: var(--accent-color); padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem;}
        
        .bot-card { background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; position: relative;}
        .bot-card.paused { border-color: rgba(243, 156, 18, 0.5); }
        .bot-card.terminated { border-color: var(--border-color); opacity: 0.8; filter: grayscale(50%); } 

        .bot-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
        .bot-title h2 { margin: 0; font-size: 1.4rem; color: var(--accent-color); }
        .bot-id { font-size: 0.8rem; color: var(--text-secondary); display: block; margin-top: 2px;}
        .runtime-badge { font-size: 0.75rem; color: var(--text-secondary); margin-top: 5px; display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.03); padding: 4px 8px; border-radius: 4px; display: inline-block;}
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-running { background: rgba(14, 203, 129, 0.1); color: var(--up-color); border: 1px solid var(--up-color); }
        .status-paused { background: rgba(243, 156, 18, 0.1); color: var(--warn-color); border: 1px solid var(--warn-color); }
        .status-terminated { background: #3b424d; color: #fff; border: 1px solid #5a626f; }

        .actions-wrapper { position: relative; display: inline-block; }
        .menu-btn { background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer; padding: 0 10px; }
        .menu-btn:hover { color: var(--text-primary); }
        
        .action-dropdown { position: absolute; right: 0; top: 30px; background: var(--surface-light); border: 1px solid var(--border-color); border-radius: 8px; width: 200px; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.5); overflow: hidden; }
        .action-dropdown button { width: 100%; text-align: left; padding: 12px 15px; background: none; border: none; color: var(--text-primary); cursor: pointer; font-family: inherit; font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .action-dropdown button:hover { background: rgba(255,255,255,0.05); }
        .action-dropdown button.term-btn { color: var(--down-color); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1rem; }
        .stat-box { background: rgba(255, 255, 255, 0.03); padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .stat-label { font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 5px; text-transform: uppercase; }
        .stat-val { font-size: 1.2rem; font-weight: 700; }

        .comp-toggle-btn { background: var(--surface-light); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; margin-top: 10px; }
        .comp-content { display: none; margin-top: 15px; }
        .coin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
        .coin-small-card { background: #13151a; border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 6px; }
        .card-row { display: flex; justify-content: space-between; align-items: center; }
        .c-sym { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
        .c-val { font-size: 0.85rem; font-weight: 600; }
        .divider { height: 1px; background: rgba(255,255,255,0.05); margin: 2px 0; }

        .text-up { color: var(--up-color); }
        .text-down { color: var(--down-color); }
        
        #loader-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); z-index: 9999; display: none; justify-content: center; align-items: center; color: var(--accent-color); font-weight: 600; font-size: 1.2rem;}

        /* Modals */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: none; justify-content: center; align-items: center; z-index: 1000;}
        .modal-content { background: var(--surface-color); width: 95%; border-radius: 16px; display: flex; flex-direction: column; max-height: 90vh; animation: fadeIn 0.2s ease-out;}
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        
        .modal-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;}
        .modal-header h3 { margin: 0; color: var(--text-primary); font-size: 1.2rem;}
        .close-modal { background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;}
        .modal-body { flex: 1; overflow-y: auto; padding: 20px;}
        
        .details-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #13151a; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color);}
        .details-table th, .details-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
        .details-table th { color: var(--text-secondary); font-weight: 500; font-size: 0.75rem; background: var(--surface-light);}
        .details-table tr:last-child td { border-bottom: none; }

        .history-card { background: var(--surface-light); border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; margin-bottom: 15px; }
        .history-card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; margin-bottom: 10px; }
        .rebalance-id { font-weight: 700; color: var(--accent-color); font-size: 1.1rem; }
        .rebalance-time { font-size: 0.8rem; color: var(--text-secondary); }
        .trade-row { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 8px 0; border-bottom: 1px dashed rgba(255,255,255,0.05); }

        .btn-term-option { width: 100%; padding: 14px; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; border: none; margin-bottom: 10px;}
        .btn-sell-market { background: var(--down-color); color: #fff; }
        .btn-sell-manual { background: var(--surface-light); border: 1px solid var(--border-color); color: var(--text-primary); }
    </style>
</head>
<body>

    <div id="loader-overlay">Processing... Please wait...</div>

    <div class="container">
        <header>
            <h1>SPS Strategy Manager</h1>
            <div class="header-actions">
                <button class="btn-validate" onclick="window.location.href='sps-create.php'">+ Create Bot</button>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                    <span style="color: var(--up-color); animation: blink 1.5s infinite;">&#9679;</span> Live Sync
                </div>
            </div>
        </header>

        <div id="bot-container">
            <div style="text-align: center; padding: 3rem;">Loading bots...</div>
        </div>
    </div>

    <div class="modal-overlay" id="details-modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Portfolio Details</h3>
                <button class="close-modal" onclick="closeDetailsModal()">&#10005;</button>
            </div>
            <div class="modal-body">
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
                    <div class="stat-box">
                        <span class="stat-label">Rebalancing Target</span>
                        <span class="stat-val" id="det-reb-pct">0%</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Initial Invest</span>
                        <span class="stat-val" id="det-inv-amt">$0.00</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Current Value</span>
                        <span class="stat-val" id="det-cur-val">$0.00</span>
                    </div>
                </div>
                <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                    <table class="details-table">
                        <thead><tr><th>Asset</th><th>Amount (Qty)</th><th>Target Alloc.</th><th>Current Alloc.</th><th>Live Price</th><th>Current Value</th></tr></thead>
                        <tbody id="details-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="history-modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="history-modal-title">Rebalancing History</h3>
                <button class="close-modal" onclick="closeHistoryModal()">&#10005;</button>
            </div>
            <div class="modal-body" id="history-modal-body" style="background: var(--bg-color);"></div>
        </div>
    </div>

    <div class="modal-overlay" id="term-choice-modal">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <div class="modal-body" style="padding: 30px 20px;">
                <h3 style="color: var(--down-color); margin-top: 0; font-size: 1.4rem;">Terminate Strategy</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 25px;">How would you like to handle your portfolio assets?</p>
                
                <button class="btn-term-option btn-sell-market" onclick="executeTermination('market_sell')">Sell for me (Market Sell)</button>
                <button class="btn-term-option btn-sell-manual" onclick="executeTermination('manual')">Sell Manual (Keep Coins)</button>
                
                <button onclick="closeTermChoice()" style="background: none; color: var(--text-secondary); padding: 10px; border: none; cursor: pointer; margin-top: 10px; font-weight: 500;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        const expandedBots = new Set();
        let openDropdownId = null;
        let globalBots = []; 
        let globalTickers = [];
        let botToTerminate = null; // Holds the ID during termination flow

        window.toggleComp = function(botId) {
            const content = document.getElementById(`comp-${botId}`);
            if (expandedBots.has(botId)) { expandedBots.delete(botId); content.style.display = 'none'; } 
            else { expandedBots.add(botId); content.style.display = 'block'; }
        };

        window.toggleDropdown = function(botId) {
            const drop = document.getElementById(`dropdown-${botId}`);
            if (openDropdownId && openDropdownId !== botId) { document.getElementById(`dropdown-${openDropdownId}`).style.display = 'none'; }
            drop.style.display = drop.style.display === 'block' ? 'none' : 'block';
            openDropdownId = drop.style.display === 'block' ? botId : null;
        };

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.actions-wrapper')) {
                if (openDropdownId) { document.getElementById(`dropdown-${openDropdownId}`).style.display = 'none'; openDropdownId = null; }
            }
        });

        function formatDuration(start, end) {
            if (!start) return 'N/A';
            const diffMs = (end ? new Date(end) : new Date()) - new Date(start);
            if (diffMs < 0) return 'Just started';
            const days = Math.floor(diffMs / 86400000);
            const hours = Math.floor((diffMs % 86400000) / 3600000);
            const mins = Math.floor((diffMs % 3600000) / 60000);
            let p = [];
            if(days>0) p.push(days+'d'); if(hours>0) p.push(hours+'h'); if(mins>0) p.push(mins+'m');
            return p.length > 0 ? p.join(' ') : '< 1m';
        }

        // --- Status Toggle (Pause/Resume) ---
        async function toggleBotStatus(botId, newStatus) {
            const msg = newStatus === 'Paused' ? 'Are you sure you want to pause rebalancing?' : 'Resume rebalancing?';
            if(!confirm(msg)) return;
            
            if(openDropdownId) { document.getElementById(`dropdown-${openDropdownId}`).style.display = 'none'; openDropdownId = null; }
            document.getElementById('loader-overlay').style.display = 'flex';
            
            try {
                const res = await fetch('sps-toggle-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bot_id: botId, status: newStatus })
                });
                const data = await res.json();
                document.getElementById('loader-overlay').style.display = 'none';
                if(data.status === 'success') { updateDashboard(); } 
                else { alert(data.message); }
            } catch(e) {
                document.getElementById('loader-overlay').style.display = 'none';
                alert("Network error.");
            }
        }

        // --- Termination Flow ---
        function promptTermination(botId) {
            if(openDropdownId) { document.getElementById(`dropdown-${openDropdownId}`).style.display = 'none'; openDropdownId = null; }
            botToTerminate = botId;
            document.getElementById('term-choice-modal').style.display = 'flex';
        }

        function closeTermChoice() {
            document.getElementById('term-choice-modal').style.display = 'none';
            botToTerminate = null;
        }

        async function executeTermination(type) {
            if (!botToTerminate) return;
            document.getElementById('term-choice-modal').style.display = 'none';
            document.getElementById('loader-overlay').style.display = 'flex';
            
            try {
                const res = await fetch('sps-termination.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bot_id: botToTerminate, type: type })
                });
                const data = await res.json();
                document.getElementById('loader-overlay').style.display = 'none';
                botToTerminate = null;
                
                if (data.status === 'success' || data.status === 'warning') {
                    alert(data.message + (data.final_pnl ? `\nFinal PNL: $${data.final_pnl.toFixed(2)}` : ''));
                    updateDashboard();
                } else {
                    alert("Error: " + data.message);
                }
            } catch (err) {
                document.getElementById('loader-overlay').style.display = 'none';
                botToTerminate = null;
                alert("Network error during termination.");
            }
        }

        // Modals Open/Close (Details & History same as previous version)
        function showBotDetails(botId) { /* Omitted for brevity, fully implemented in template */ }
        function closeDetailsModal() { document.getElementById('details-modal').style.display = 'none'; }
        async function showHistoryModal(botId, botName) { /* Omitted for brevity */ }
        function closeHistoryModal() { document.getElementById('history-modal').style.display = 'none'; }
        
        // Modal function injections (Details)
        showBotDetails = function(botId) {
            const bot = globalBots.find(b => b.bot_id === botId);
            if (!bot) return;
            const isTerminated = bot.status === 'Terminated';
            let totalCurrentValue = 0;
            bot.coins_details.forEach(c => {
                if(isTerminated) totalCurrentValue += c.returned_usdt || 0;
                else {
                    const t = globalTickers.find(x => x.symbol === c.coin_name + 'USDT');
                    totalCurrentValue += c.amount * (t ? parseFloat(t.lastPr) : 0);
                }
            });

            document.getElementById('det-reb-pct').innerText = bot.rebalancing_percentage + '%';
            document.getElementById('det-inv-amt').innerText = '$' + bot.initial_investment.toFixed(2);
            document.getElementById('det-cur-val').innerText = '$' + totalCurrentValue.toFixed(2);
            
            // Color Logic for Details Modal
            document.getElementById('det-cur-val').className = 'stat-val ' + (totalCurrentValue >= bot.initial_investment ? 'text-up' : 'text-down');

            let th = '';
            let sc = [...bot.coins_details].sort((a,b) => b.initial_investment - a.initial_investment);
            sc.forEach(c => {
                let lp, cv;
                if(isTerminated){ lp=c.sell_price||0; cv=c.returned_usdt||0; }
                else{ const t=globalTickers.find(x=>x.symbol===c.coin_name+'USDT'); lp=t?parseFloat(t.lastPr):0; cv=c.amount*lp; }
                const tp = (c.initial_investment/bot.initial_investment)*100;
                const cp = totalCurrentValue>0 ? (cv/totalCurrentValue)*100 : 0;
                let cCol = 'var(--text-primary)';
                if(Math.abs(cp-tp) >= (bot.rebalancing_percentage*0.8)) cCol = (cp-tp)>0 ? 'var(--up-color)':'var(--down-color)';
                th += `<tr><td style="font-weight:700; color:var(--accent-color);">${c.coin_name}</td><td>${c.amount.toFixed(6)}</td><td>${tp.toFixed(1)}%</td><td style="color:${cCol}; font-weight:600;">${cp.toFixed(2)}%</td><td>$${lp.toLocaleString(undefined,{minimumFractionDigits:2})}</td><td style="font-weight:600;">$${cv.toFixed(2)}</td></tr>`;
            });
            document.getElementById('details-table-body').innerHTML = th;
            document.getElementById('details-modal').style.display = 'flex';
            if(openDropdownId) { document.getElementById(`dropdown-${openDropdownId}`).style.display = 'none'; openDropdownId = null; }
            document.getElementById('details-modal').setAttribute('data-active-bot', botId);
        };

        // History injection
        showHistoryModal = async function(botId, botName) {
            document.getElementById('history-modal-title').innerText = `Rebalancing History - ${botName}`;
            const b = document.getElementById('history-modal-body');
            b.innerHTML = '<div style="text-align:center; padding: 20px;">Loading...</div>';
            document.getElementById('history-modal').style.display = 'flex';
            if(openDropdownId) { document.getElementById(`dropdown-${openDropdownId}`).style.display='none'; openDropdownId=null; }
            try{
                const r = await fetch('rebalance-history.json?t='+Date.now());
                if(!r.ok){ b.innerHTML='<div style="text-align:center; color:var(--text-secondary);">No history found.</div>'; return; }
                const d = await r.json(); const h = d[botId];
                if(!h || h.length===0){ b.innerHTML='<div style="text-align:center; color:var(--text-secondary);">No rebalancing yet.</div>'; return; }
                let ht='';
                [...h].reverse().forEach(x=>{
                    let sh='', bh='';
                    if(x.sells&&x.sells.length>0){ sh+=`<div class="trade-section"><h4 style="color:var(--text-secondary);">Sells (Profit Taken)</h4>`; x.sells.forEach(s=>{ sh+=`<div class="trade-row"><span style="color:var(--down-color); font-weight:600;">Sold ${s.amount} ${s.coin}</span> <span>$${s.usdt}</span></div>`; }); sh+=`</div>`; }
                    if(x.buys&&x.buys.length>0){ bh+=`<div class="trade-section" style="margin-top:15px;"><h4 style="color:var(--text-secondary);">Buys (Re-invested)</h4>`; x.buys.forEach(by=>{ bh+=`<div class="trade-row"><span style="color:var(--up-color); font-weight:600;">Bought ${by.amount} ${by.coin}</span> <span>$${by.usdt}</span></div>`; }); bh+=`</div>`; }
                    ht+=`<div class="history-card"><div class="history-card-header"><span class="rebalance-id">${x.rebalance_id}</span><span class="rebalance-time">${new Date(x.timestamp).toLocaleString()}</span></div>${sh}${bh}</div>`;
                });
                b.innerHTML=ht;
            }catch(e){ b.innerHTML='<div style="text-align:center; color:var(--down-color);">Failed to load history.</div>'; }
        }

        // --- Dashboard Update ---
        async function updateDashboard() {
            try {
                const botRes = await fetch('bot-list-data.txt?t=' + Date.now());
                if (!botRes.ok) return;
                const bots = await botRes.json();
                globalBots = bots;

                const priceRes = await fetch('../../price/price-api.php');
                const marketData = await priceRes.json();
                globalTickers = marketData.status === 'success' ? marketData.data : [];

                const container = document.getElementById('bot-container');
                let htmlContent = '';

                bots.reverse().forEach(bot => { 
                    const isTerminated = bot.status === 'Terminated';
                    const isPaused = bot.status === 'Paused';
                    let currentPortfolioValue = 0;
                    let coinCardsHtml = '';

                    let processedCoins = bot.coins_details.map(coin => {
                        let livePrice, currentVal, coinPnl;
                        if (isTerminated) {
                            livePrice = coin.sell_price || 0;
                            currentVal = coin.returned_usdt || 0;
                            coinPnl = currentVal - coin.initial_investment;
                        } else {
                            const ticker = globalTickers.find(t => t.symbol === coin.coin_name + 'USDT');
                            livePrice = ticker ? parseFloat(ticker.lastPr) : 0;
                            currentVal = coin.amount * livePrice;
                            coinPnl = currentVal - coin.initial_investment;
                        }
                        currentPortfolioValue += currentVal; 
                        return { ...coin, livePrice, currentVal, coinPnl };
                    });

                    processedCoins.sort((a, b) => b.coinPnl - a.coinPnl);

                    processedCoins.forEach(coin => {
                        const pnlClass = coin.coinPnl >= 0 ? 'text-up' : 'text-down';
                        const pnlSign = coin.coinPnl >= 0 ? '+' : '';
                        coinCardsHtml += `
                            <div class="coin-small-card">
                                <div class="card-row"><span class="c-sym">${coin.coin_name}</span><span style="font-size:0.8rem; color:var(--text-secondary);">${coin.amount.toFixed(6)}</span></div>
                                <div class="divider"></div>
                                <div class="card-row"><span style="font-size:0.7rem; color:var(--text-secondary);">PRICE:</span><span class="c-val">$${coin.livePrice.toLocaleString(undefined, {minimumFractionDigits: 2})}</span></div>
                                <div class="card-row"><span style="font-size:0.7rem; color:var(--text-secondary);">VAL:</span><span class="c-val">$${coin.currentVal.toFixed(2)}</span></div>
                                <div class="card-row"><span style="font-size:0.7rem; color:var(--text-secondary);">PNL:</span><span class="c-val ${pnlClass}">${pnlSign}$${coin.coinPnl.toFixed(2)}</span></div>
                            </div>`;
                    });

                    const totalPnl = isTerminated ? bot.total_pnl : (currentPortfolioValue - bot.initial_investment);
                    const pnlPercent = isTerminated ? bot.pnl_percentage : ((totalPnl / bot.initial_investment) * 100);
                    const botPnlClass = totalPnl >= 0 ? 'text-up' : 'text-down';
                    const botPnlSign = totalPnl >= 0 ? '+' : '';
                    
                    // NEW: Color logic for Current Value
                    const cvClass = currentPortfolioValue >= bot.initial_investment ? 'text-up' : 'text-down';

                    let statusClass = 'status-running';
                    let cardClass = 'bot-card';
                    if (isPaused) { statusClass = 'status-paused'; cardClass = 'bot-card paused'; }
                    if (isTerminated) { statusClass = 'status-terminated'; cardClass = 'bot-card terminated'; }

                    let runtimeHtml = '';
                    if (bot.created_at) {
                        const runPrefix = isTerminated ? 'Ran for' : 'Running time';
                        const runDuration = formatDuration(bot.created_at, isTerminated ? bot.terminated_at : null);
                        runtimeHtml = `<span class="runtime-badge">⏳ ${runPrefix}: <strong>${runDuration}</strong></span>`;
                    }

                    const dropStyle = (openDropdownId === bot.bot_id) ? 'display: block;' : 'display: none;';
                    const safeBotName = bot.bot_name.replace(/'/g, "\\'");
                    
                    // Action Menu Logic (Pause/Start Toggling)
                    let actionMenuHtml = '';
                    if (!isTerminated) {
                        const pauseStartBtn = isPaused 
                            ? `<button onclick="toggleBotStatus('${bot.bot_id}', 'Running')" style="color: var(--up-color);">▶️ Start Rebalancing</button>`
                            : `<button onclick="toggleBotStatus('${bot.bot_id}', 'Paused')" style="color: var(--warn-color);">⏸️ Pause Rebalancing</button>`;

                        actionMenuHtml = `
                            <div class="actions-wrapper">
                                <button class="menu-btn" onclick="toggleDropdown('${bot.bot_id}')">&#8942;</button>
                                <div class="action-dropdown" id="dropdown-${bot.bot_id}" style="${dropStyle}">
                                    <button onclick="showBotDetails('${bot.bot_id}')">ℹ️ Details</button>
                                    <button onclick="showHistoryModal('${bot.bot_id}', '${safeBotName}')">⏳ Rebalancing History</button>
                                    ${pauseStartBtn}
                                    <button class="term-btn" onclick="promptTermination('${bot.bot_id}')">🛑 Termination</button>
                                </div>
                            </div>
                        `;
                    } else {
                        actionMenuHtml = `
                            <div class="actions-wrapper">
                                <button class="menu-btn" onclick="toggleDropdown('${bot.bot_id}')">&#8942;</button>
                                <div class="action-dropdown" id="dropdown-${bot.bot_id}" style="${dropStyle}">
                                    <button onclick="showBotDetails('${bot.bot_id}')">ℹ️ Details</button>
                                    <button onclick="showHistoryModal('${bot.bot_id}', '${safeBotName}')">⏳ Rebalancing History</button>
                                </div>
                            </div>
                        `;
                    }

                    const isExpanded = expandedBots.has(bot.bot_id);
                    const displayStyle = isExpanded ? 'block' : 'none';

                    htmlContent += `
                        <div class="${cardClass}">
                            <div class="bot-header">
                                <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
                                    <div>
                                        <h2>${bot.bot_name}</h2>
                                        <span class="bot-id">ID: ${bot.bot_id}</span>
                                        ${runtimeHtml}
                                    </div>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <span class="status-badge ${statusClass}">${bot.status}</span>
                                        ${actionMenuHtml}
                                    </div>
                                </div>
                            </div>

                            ${isTerminated && bot.terminated_at ? `<div style="color:var(--text-secondary); font-size:0.8rem; margin-bottom:1rem; text-align:right;">Terminated on: ${new Date(bot.terminated_at).toLocaleString()}</div>` : ''}

                            <div class="stats-grid">
                                <div class="stat-box">
                                    <span class="stat-label">Initial Invest</span>
                                    <span class="stat-val">$${bot.initial_investment.toFixed(2)}</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">${isTerminated ? 'Final Return' : 'Current Value'}</span>
                                    <span class="stat-val ${cvClass}">$${currentPortfolioValue.toFixed(2)}</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">Total PNL</span>
                                    <span class="stat-val ${botPnlClass}">${botPnlSign}$${Math.abs(totalPnl).toFixed(2)}</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">PNL %</span>
                                    <span class="stat-val ${botPnlClass}">${botPnlSign}${Math.abs(pnlPercent).toFixed(2)}%</span>
                                </div>
                            </div>

                            <div class="comp-toggle-btn" onclick="toggleComp('${bot.bot_id}')">
                                <span>Portfolio Assets (${bot.coins_details.length})</span>
                                <span>&#9662;</span>
                            </div>

                            <div id="comp-${bot.bot_id}" class="comp-content" style="display: ${displayStyle};">
                                <div class="coin-grid">
                                    ${coinCardsHtml}
                                </div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = htmlContent;
                
                const openModalBotId = document.getElementById('details-modal').getAttribute('data-active-bot');
                if (document.getElementById('details-modal').style.display === 'flex' && openModalBotId) {
                    showBotDetails(openModalBotId); 
                }

            } catch (err) { console.error(err); }
        }

        updateDashboard();
        setInterval(updateDashboard, 5000);
    </script>
</body>
</html>