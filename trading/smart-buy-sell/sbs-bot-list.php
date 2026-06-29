<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SBS Bot Dashboard | Bitget</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0b0e11;
            --surface-color: #181a20;
            --surface-light: #1e2329;
            --border-color: #2b3139;
            --text-primary: #eaecef;
            --text-secondary: #848e9c;
            --accent-color: #8a2be2;
            --up-color: #0ecb81;
            --down-color: #f6465d;
            --warn-color: #f39c12;
            --buy-color: #00bcd4;
        }

        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding-bottom: 50px;}
        .container { width: 100%; max-width: 1100px; padding: 2rem; box-sizing: border-box; }
        
        header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.8rem; margin: 0; display: flex; align-items: center; gap: 10px;}
        .badge-sbs { background: rgba(138, 43, 226, 0.2); color: var(--accent-color); padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; border: 1px solid var(--accent-color);}
        
        .header-actions { display: flex; gap: 15px; align-items: center; }
        .btn-validate { background: rgba(138, 43, 226, 0.1); border: 1px solid var(--accent-color); color: var(--accent-color); padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem;}
        
        .bot-card { background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; position: relative;}
        .bot-card.paused { border-color: rgba(243, 156, 18, 0.5); }
        .bot-card.terminated { border-color: var(--border-color); opacity: 0.8; filter: grayscale(50%); } 

        .bot-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
        .bot-title h2 { margin: 0; font-size: 1.4rem; color: var(--text-primary); }
        .bot-id { font-size: 0.8rem; color: var(--text-secondary); display: block; margin-top: 2px;}
        .runtime-badge { font-size: 0.75rem; color: var(--text-secondary); margin-top: 5px; display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.03); padding: 4px 8px; border-radius: 4px; display: inline-block;}
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-running { background: rgba(14, 203, 129, 0.1); color: var(--up-color); border: 1px solid var(--up-color); }
        .status-paused { background: rgba(243, 156, 18, 0.1); color: var(--warn-color); border: 1px solid var(--warn-color); }
        .status-terminated { background: #3b424d; color: #fff; border: 1px solid #5a626f; }

        .actions-wrapper { position: relative; display: inline-block; }
        .menu-btn { background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer; padding: 0 10px; }
        .menu-btn:hover { color: var(--text-primary); }
        
        .action-dropdown { position: absolute; right: 0; top: 30px; background: var(--surface-light); border: 1px solid var(--border-color); border-radius: 8px; width: 200px; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.5); overflow: hidden; display: none; }
        .action-dropdown button { width: 100%; text-align: left; padding: 12px 15px; background: none; border: none; color: var(--text-primary); cursor: pointer; font-family: inherit; font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .action-dropdown button:hover { background: rgba(255,255,255,0.05); }
        .action-dropdown button.term-btn { color: var(--down-color); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .stat-box { background: rgba(255, 255, 255, 0.03); padding: 1rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .stat-label { font-size: 0.75rem; color: var(--text-secondary); display: block; margin-bottom: 5px; text-transform: uppercase; font-weight: 600;}
        .stat-val { font-size: 1.2rem; font-weight: 700; }

        .gap-tracker-box { background: rgba(138, 43, 226, 0.05); border: 1px dashed var(--accent-color); padding: 15px; border-radius: 12px; margin-bottom: 15px; }
        .gap-header { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 8px; font-weight: 600;}
        .gap-coins { display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 10px;}
        .progress-bg { width: 100%; height: 8px; background: var(--surface-light); border-radius: 4px; overflow: hidden; position: relative;}
        .progress-bar { height: 100%; background: linear-gradient(90deg, #3d79f2, var(--accent-color)); border-radius: 4px; transition: width 0.5s ease;}

        .comp-toggle-btn { background: var(--surface-light); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; margin-top: 10px; font-weight: 600; font-size: 0.9rem;}
        .comp-content { display: none; margin-top: 15px; }
        .coin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
        .coin-small-card { background: #13151a; border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 6px; position: relative;}
        .card-row { display: flex; justify-content: space-between; align-items: center; }
        .c-sym { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
        .c-val { font-size: 0.85rem; font-weight: 600; }
        .divider { height: 1px; background: rgba(255,255,255,0.05); margin: 2px 0; }
        
        .rank-badge { position: absolute; top: -8px; right: -8px; font-size: 0.65rem; font-weight: 700; padding: 3px 6px; border-radius: 10px; color: #fff;}
        .rank-highest { background: var(--up-color); box-shadow: 0 0 8px rgba(14,203,129,0.5);}
        .rank-lowest { background: var(--down-color); box-shadow: 0 0 8px rgba(246,70,93,0.5);}

        .text-up { color: var(--up-color); }
        .text-down { color: var(--down-color); }
        
        #loader-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); z-index: 9999; display: none; justify-content: center; align-items: center; color: var(--accent-color); font-weight: 600; font-size: 1.2rem;}

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: none; justify-content: center; align-items: center; z-index: 1000;}
        .modal-content { background: var(--surface-color); width: 95%; max-width: 800px; border-radius: 16px; display: flex; flex-direction: column; max-height: 90vh; animation: fadeIn 0.2s ease-out;}
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        
        .modal-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;}
        .modal-header h3 { margin: 0; color: var(--text-primary); font-size: 1.2rem;}
        .close-modal { background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;}
        .modal-body { flex: 1; overflow-y: auto; padding: 20px;}
        
        .details-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #13151a; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color);}
        .details-table th, .details-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
        .details-table th { color: var(--text-secondary); font-weight: 500; font-size: 0.75rem; background: var(--surface-light); text-transform: uppercase;}
        .details-table tr:last-child td { border-bottom: none; }

        .btn-term-option { width: 100%; padding: 14px; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; border: none; margin-bottom: 10px;}
        .btn-sell-market { background: var(--down-color); color: #fff; }
        .btn-sell-manual { background: var(--surface-light); border: 1px solid var(--border-color); color: var(--text-primary); }

        @media (max-width: 600px) {
            .container { padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-val { font-size: 1.05rem; }
            .details-table { white-space: nowrap; } 
            header h1 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

    <div id="loader-overlay">Processing... Please wait...</div>

    <div class="container">
        <header>
            <h1>SBS Manager <span class="badge-sbs">Live</span></h1>
            <div class="header-actions">
                <button class="btn-validate" onclick="window.location.href='sbs-create.php'">+ Create SBS Bot</button>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                    <span style="color: var(--up-color); animation: blink 1.5s infinite;">&#9679;</span> Syncing
                </div>
            </div>
        </header>

        <div id="bot-container">
            <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">Loading SBS strategies...</div>
        </div>
    </div>

    <div class="modal-overlay" id="details-modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Portfolio Breakdown</h3>
                <button class="close-modal" onclick="closeDetailsModal()">&#10005;</button>
            </div>
            <div class="modal-body">
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
                    <div class="stat-box">
                        <span class="stat-label">Gap Trigger</span>
                        <span class="stat-val" style="color: var(--accent-color);" id="det-gap-trig">$0.00</span>
                    </div>
                    <div class="stat-box">
                       <span class="stat-label">Min. Trade Amount</span>
                       <span class="stat-val" id="det-trd-amt">$0.00</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Current Total Value</span>
                        <span class="stat-val text-up" id="det-cur-val">$0.00</span>
                    </div>
                </div>
                <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                    <table class="details-table">
                        <thead><tr><th>Asset</th><th>Holding (Qty)</th><th>Avg. Buy Price</th><th>Live Price</th><th>Current Value</th><th>Status</th></tr></thead>
                        <tbody id="details-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="history-modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="history-modal-title">Transaction history</h3>
                <button class="close-modal" onclick="closeHistoryModal()">&#10005;</button>
            </div>
            <div class="modal-body" id="history-modal-body" style="background: var(--bg-color); padding: 20px;"></div>
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
        let botToTerminate = null;
        let currentBotHistory = []; 

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

        async function toggleBotStatus(botId, newStatus) {
            if(!confirm(newStatus === 'Paused' ? 'Pause SBS algorithm?' : 'Resume SBS algorithm?')) return;
            if(openDropdownId) { document.getElementById(`dropdown-${openDropdownId}`).style.display = 'none'; openDropdownId = null; }
            document.getElementById('loader-overlay').style.display = 'flex';
            try {
                const res = await fetch('sbs-toggle-status.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bot_id: botId, status: newStatus })
                });
                const data = await res.json();
                document.getElementById('loader-overlay').style.display = 'none';
                if(data.status === 'success') { updateDashboard(); } else { alert(data.message); }
            } catch(e) { document.getElementById('loader-overlay').style.display = 'none'; alert("Network error."); }
        }

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
                const res = await fetch('sbs-termination.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bot_id: botToTerminate, type: type })
                });
                const data = await res.json();
                document.getElementById('loader-overlay').style.display = 'none';
                botToTerminate = null;
                if (data.status === 'success' || data.status === 'warning') {
                    alert(data.message); updateDashboard();
                } else { alert("Error: " + data.message); }
            } catch (err) { document.getElementById('loader-overlay').style.display = 'none'; botToTerminate = null; alert("Error."); }
        }

        function showBotDetails(botId) {
            if (!globalBots || globalBots.length === 0) return;

            const bot = globalBots.find(b => b.bot_id === botId);
            if (!bot) return;

            const isTerminated = bot.status === 'Terminated';
            let totalCurrentValue = 0;
            let coinsData = [];

            bot.coins_details.forEach(c => {
                let livePrice = 0, currentVal = 0;
                if(isTerminated) {
                    livePrice = c.sell_price || 0;
                    currentVal = c.returned_usdt || 0;
                } else {
                    const t = globalTickers.find(x => x.symbol === c.coin_name + 'USDT');
                    livePrice = t ? parseFloat(t.lastPr) : 0;
                    currentVal = c.amount * livePrice;
                }
                totalCurrentValue += currentVal;
                coinsData.push({ ...c, livePrice, currentVal });
            });

            coinsData.sort((a,b) => b.currentVal - a.currentVal);
            const highestCoin = coinsData.length > 0 ? coinsData[0].coin_name : null;
            const lowestCoin = coinsData.length > 0 ? coinsData[coinsData.length - 1].coin_name : null;

            document.getElementById('det-gap-trig').innerText = '$' + bot.gap_trigger.toFixed(2);
            document.getElementById('det-trd-amt').innerText = '$' + bot.trade_amount.toFixed(2);
            document.getElementById('det-cur-val').innerText = '$' + totalCurrentValue.toFixed(2);
            document.getElementById('det-cur-val').className = 'stat-val ' + (totalCurrentValue >= bot.initial_investment ? 'text-up' : 'text-down');

            let th = '';
            coinsData.forEach(c => {
                let statusBadge = `<span style="color:var(--text-secondary); font-size:0.8rem;">Stable</span>`;
                if (isTerminated) {
                    statusBadge = (c.sell_order_id === 'MANUAL_KEPT' || c.sell_order_id === 'FAILED_KEPT') 
                        ? `<span style="color:var(--warn-color); font-weight:700;">Kept in Spot</span>` 
                        : `<span style="color:var(--down-color); font-weight:700;">Sold</span>`;
                } else {
                    if(c.coin_name === highestCoin) statusBadge = `<span style="color:var(--up-color); font-weight:700;">Highest ↑</span>`;
                    if(c.coin_name === lowestCoin) statusBadge = `<span style="color:var(--down-color); font-weight:700;">Lowest ↓</span>`;
                }

                th += `<tr>
                    <td style="font-weight:700; color:var(--text-primary);">${c.coin_name}</td>
                    <td>${c.amount.toFixed(6)}</td>
                    <td>$${c.buy_price ? c.buy_price.toLocaleString(undefined, {minimumFractionDigits:2}) : '0'}</td>
                    <td>$${c.livePrice.toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                    <td style="font-weight:700;">$${c.currentVal.toFixed(2)}</td>
                    <td>${statusBadge}</td>
                </tr>`;
            });
            
            document.getElementById('details-table-body').innerHTML = th;
            
            const modal = document.getElementById('details-modal');
            modal.style.display = 'flex';
            modal.setAttribute('data-active-bot', botId);
        }

        function closeDetailsModal() { document.getElementById('details-modal').style.display = 'none'; document.getElementById('details-modal').removeAttribute('data-active-bot'); }

        async function showHistoryModal(botId) {
            document.getElementById('history-modal-title').innerText = `Transaction history`;
            const b = document.getElementById('history-modal-body');
            b.innerHTML = '<div style="text-align:center; padding: 20px;">Loading...</div>';
            document.getElementById('history-modal').style.display = 'flex';
            
            if(openDropdownId) { document.getElementById(`dropdown-${openDropdownId}`).style.display='none'; openDropdownId=null; }
            
            try {
                const r = await fetch('sbs-history.json?t='+Date.now());
                if(!r.ok){ b.innerHTML='<div style="text-align:center; color:var(--text-secondary);">No history found yet.</div>'; return; }
                const d = await r.json(); 
                const h = d[botId];
                if(!h || h.length === 0){ b.innerHTML='<div style="text-align:center; color:var(--text-secondary);">No rebalancing has triggered.</div>'; return; }
                
                currentBotHistory = h; 
                renderHistoryList();

            } catch(e) { b.innerHTML='<div style="text-align:center; color:var(--down-color);">Failed to load history.</div>'; }
        }

        function renderOrderSection(order, isSell) {
            if (!order) return '';
            const dirColor = isSell ? 'var(--down-color)' : 'var(--buy-color)';
            const dirText = isSell ? 'Sell' : 'Buy';

            return `
                <div style="margin-bottom: 20px; padding-bottom: 20px; ${isSell ? 'border-bottom: 1px solid rgba(255,255,255,0.05);' : ''}">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.9rem;">
                        <div><div style="color:var(--text-secondary); font-size:0.8rem;">Direction</div><div style="color:${dirColor}; font-weight:600;">${dirText}</div></div>
                        <div><div style="color:var(--text-secondary); font-size:0.8rem;">Coin</div><div style="font-weight:600;">${order.coin || 'N/A'}</div></div>
                        <div><div style="color:var(--text-secondary); font-size:0.8rem;">Time</div><div style="font-weight:600;">${order.time || 'N/A'}</div></div>
                        <div><div style="color:var(--text-secondary); font-size:0.8rem;">Price</div><div style="font-weight:600;">${order.avg_price ? parseFloat(order.avg_price).toFixed(6) : '0.00'} USDT</div></div>
                        <div><div style="color:var(--text-secondary); font-size:0.8rem;">Amount</div><div style="font-weight:600;">${order.amount_usdt ? parseFloat(order.amount_usdt).toFixed(2) : '0.00'} USDT</div></div>
                        <div><div style="color:var(--text-secondary); font-size:0.8rem;">Volume</div><div style="font-weight:600;">${order.volume_coin ? parseFloat(order.volume_coin).toFixed(6) : '0.00'}</div></div>
                        <div><div style="color:var(--text-secondary); font-size:0.8rem;">Fee</div><div style="font-weight:600;">${order.fee ? parseFloat(order.fee).toFixed(8) : '0.00'} ${order.fee_coin || ''}</div></div>
                    </div>
                </div>
            `;
        }

        function renderHistoryList() {
            const b = document.getElementById('history-modal-body');
            b.innerHTML = `
                <div id="history-list-view" style="display:block;">
                    <div style="display:flex; justify-content:space-between; color:var(--text-secondary); font-size:0.85rem; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                        <span style="flex:1;">Completed</span>
                        <span style="flex:1; text-align:center;">Event</span>
                        <span style="flex:1; text-align:right;">Action</span>
                    </div>
                    <div id="list-content" style="max-height: 65vh; overflow-y: auto;"></div>
                </div>
                <div id="history-details-view" style="display:none;"></div>
            `;

            const listContent = document.getElementById('list-content');
            let html = '';

            [...currentBotHistory].reverse().forEach((x, revIndex) => {
                const originalIndex = currentBotHistory.length - 1 - revIndex;
                let eventName = x.trade_id.replace('#Trade ', 'Rebalancing#');
                let timeStr = x.timestamp.replace('T', ' ').substring(0, 19);

                html += `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.05);">
                        <span style="flex:1; font-weight:600; font-size:0.9rem;">${timeStr}</span>
                        <span style="flex:1; text-align:center; font-weight:600; font-size:0.95rem;">${eventName}</span>
                        <span style="flex:1; text-align:right;">
                            <a href="javascript:void(0)" onclick="showTradeDetails(${originalIndex})" style="color:var(--buy-color); text-decoration:none; font-weight:600;">Details</a>
                        </span>
                    </div>
                `;
            });
            listContent.innerHTML = html;
        }

        function showTradeDetails(index) {
            const trade = currentBotHistory[index];
            const listView = document.getElementById('history-list-view');
            const detailsView = document.getElementById('history-details-view');

            if (listView) listView.style.display = 'none';
            if (detailsView) {
                let detailHtml = '';
                
                // ১. যদি এটি Initial Setup হয়
                if (trade.event_type === 'setup') {
                    detailHtml = `<div style="margin-bottom: 15px; font-weight: 600; color: var(--accent-color); text-align: center;">Initial Portfolio Setup</div>`;
                    if (trade.details && trade.details.length > 0) {
                        trade.details.forEach(c => {
                            detailHtml += `
                                <div style="margin-bottom: 10px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color);">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                        <span style="font-weight:bold; color:var(--text-primary); font-size:1.05rem;">${c.coin_name}</span>
                                        <span style="color:var(--text-secondary); font-size:0.85rem;">Qty: ${parseFloat(c.amount).toFixed(6)}</span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                                        <span style="color:var(--text-secondary);">Avg Price: $${parseFloat(c.buy_price).toFixed(4)}</span>
                                        <span style="color:var(--text-primary); font-weight:600;">Value: $${parseFloat(c.initial_investment).toFixed(2)}</span>
                                    </div>
                                </div>
                            `;
                        });
                    }
                } 
                // ২. 💡 যদি এটি Termination Event হয়
                else if (trade.event_type === 'termination') {
                    let termTypeStr = trade.termination_type === 'manual' ? 'Manual (Kept in Spot)' : 'Market Sell';
                    let pnlColor = trade.total_pnl >= 0 ? 'var(--up-color)' : 'var(--down-color)';
                    let pnlSign = trade.total_pnl >= 0 ? '+' : '';

                    detailHtml = `<div style="margin-bottom: 15px; font-weight: 600; color: var(--down-color); text-align: center; font-size: 1.1rem;">Strategy Terminated</div>`;
                    detailHtml += `
                        <div style="margin-bottom: 15px; padding: 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color);">
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                <span style="color:var(--text-secondary);">Type:</span>
                                <span style="color:var(--text-primary); font-weight:600;">${termTypeStr}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                <span style="color:var(--text-secondary);">Final Value:</span>
                                <span style="color:var(--text-primary); font-weight:600;">$${parseFloat(trade.final_value || 0).toFixed(2)}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">Total PNL:</span>
                                <span style="color:${pnlColor}; font-weight:600;">
                                    ${pnlSign}$${parseFloat(trade.total_pnl || 0).toFixed(2)} (${pnlSign}${parseFloat(trade.pnl_percentage || 0).toFixed(2)}%)
                                </span>
                            </div>
                        </div>
                    `;

                    if (trade.details && trade.details.length > 0) {
                        trade.details.forEach(c => {
                            let statusText = (c.sell_order_id === 'MANUAL_KEPT' || c.sell_order_id === 'FAILED_KEPT') ? 'Kept' : 'Sold';
                            let statusColor = statusText === 'Sold' ? 'var(--down-color)' : 'var(--warn-color)';
                            
                            detailHtml += `
                                <div style="margin-bottom: 10px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px dashed var(--border-color);">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                        <span style="font-weight:bold; color:var(--text-primary); font-size:1.05rem;">${c.coin_name}</span>
                                        <span style="color:var(--text-secondary); font-size:0.85rem;">Status: <span style="color:${statusColor}; font-weight:600;">${statusText}</span></span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                                        <span style="color:var(--text-secondary);">Sell Price: $${parseFloat(c.sell_price || 0).toFixed(4)}</span>
                                        <span style="color:var(--text-primary); font-weight:600;">Returned: $${parseFloat(c.returned_usdt || 0).toFixed(2)}</span>
                                    </div>
                                </div>
                            `;
                        });
                    }
                }
                // ৩. আর যদি রেগুলার Rebalancing হয়
                else {
                    detailHtml = `${renderOrderSection(trade.sell, true)} ${renderOrderSection(trade.buy, false)}`;
                }

                detailsView.innerHTML = `
                    <div style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                        <button onclick="document.getElementById('history-details-view').style.display='none'; document.getElementById('history-list-view').style.display='block';" 
                                style="background:none; border:none; color:var(--text-primary); cursor:pointer; font-weight:600; font-size:1rem; padding:0; display:flex; align-items:center; gap:5px;">
                            <span style="font-size:1.2rem;">←</span> Back
                        </button>
                    </div>
                    ${detailHtml}
                `;
                detailsView.style.display = 'block';
            }
        }
        
        function closeHistoryModal() { 
            document.getElementById('history-modal').style.display = 'none'; 
        }

        async function updateDashboard() {
            try {
                const botRes = await fetch('sbs-bot-data.txt?t=' + Date.now());
                if (!botRes.ok) {
                    document.getElementById('bot-container').innerHTML = '<div style="text-align: center; padding: 3rem; color: var(--text-secondary);">No bots found. Create one!</div>';
                    return;
                }
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
                    let maxCoinValue = -Infinity;
                    let minCoinValue = Infinity;
                    let maxCoinName = '';
                    let minCoinName = '';
                    
                    let coinCardsHtml = '';
                    let processedCoins = [];

                    bot.coins_details.forEach(coin => {
                        let livePrice, currentVal, coinPnl;
                        if (isTerminated) {
                            livePrice = coin.sell_price || 0;
                            currentVal = coin.returned_usdt || 0;
                        } else {
                            const ticker = globalTickers.find(t => t.symbol === coin.coin_name + 'USDT');
                            livePrice = ticker ? parseFloat(ticker.lastPr) : 0;
                            currentVal = coin.amount * livePrice;
                        }
                        
                        coinPnl = currentVal - (coin.initial_investment || 0);
                        currentPortfolioValue += currentVal; 
                        
                        if (currentVal > maxCoinValue) { maxCoinValue = currentVal; maxCoinName = coin.coin_name; }
                        if (currentVal < minCoinValue) { minCoinValue = currentVal; minCoinName = coin.coin_name; }

                        processedCoins.push({ ...coin, livePrice, currentVal, coinPnl });
                    });

                    processedCoins.sort((a, b) => b.currentVal - a.currentVal);

                    processedCoins.forEach(coin => {
                        const pnlClass = coin.coinPnl >= 0 ? 'text-up' : 'text-down';
                        const pnlSign = coin.coinPnl >= 0 ? '+' : '';
                        
                        let badgeHtml = '';
                        if(!isTerminated) {
                            if(coin.coin_name === maxCoinName) badgeHtml = `<span class="rank-badge rank-highest">Top</span>`;
                            if(coin.coin_name === minCoinName) badgeHtml = `<span class="rank-badge rank-lowest">Low</span>`;
                        }

                        coinCardsHtml += `
                            <div class="coin-small-card">
                                ${badgeHtml}
                                <div class="card-row"><span class="c-sym">${coin.coin_name}</span><span style="font-size:0.8rem; color:var(--text-secondary);">${coin.amount.toFixed(6)}</span></div>
                                <div class="divider"></div>
                                <div class="card-row"><span style="font-size:0.7rem; color:var(--text-secondary);">PRICE:</span><span class="c-val">$${coin.livePrice.toLocaleString(undefined, {minimumFractionDigits: 2})}</span></div>
                                <div class="card-row"><span style="font-size:0.7rem; color:var(--text-secondary);">VAL:</span><span class="c-val">$${coin.currentVal.toFixed(2)}</span></div>
                                <div class="card-row"><span style="font-size:0.7rem; color:var(--text-secondary);">PNL:</span><span class="c-val ${pnlClass}">${pnlSign}$${Math.abs(coin.coinPnl).toFixed(2)}</span></div>
                            </div>`;
                    });

                    const totalPnl = isTerminated ? bot.total_pnl : (currentPortfolioValue - bot.initial_investment);
                    const pnlPercent = isTerminated ? bot.pnl_percentage : ((totalPnl / bot.initial_investment) * 100);
                    const botPnlClass = totalPnl >= 0 ? 'text-up' : 'text-down';
                    const botPnlSign = totalPnl >= 0 ? '+' : '';
                    const cvClass = currentPortfolioValue >= bot.initial_investment ? 'text-up' : 'text-down';

                    let currentGap = maxCoinValue - minCoinValue;
                    if(currentGap < 0) currentGap = 0;
                    let gapProgressPct = isTerminated ? 0 : (currentGap / bot.gap_trigger) * 100;
                    if(gapProgressPct > 100) gapProgressPct = 100;

                    let gapTrackerHtml = '';
                    if (!isTerminated) {
                        gapTrackerHtml = `
                            <div class="gap-tracker-box">
                                <div class="gap-header">
                                    <span style="color: var(--text-secondary);">Value Gap Tracker</span>
                                    <span style="color: var(--accent-color); font-weight: 700;">$${currentGap.toFixed(2)} / $${bot.gap_trigger.toFixed(2)}</span>
                                </div>
                                <div class="progress-bg">
                                    <div class="progress-bar" style="width: ${gapProgressPct}%;"></div>
                                </div>
                                <div class="gap-coins" style="margin-top: 6px; margin-bottom: 0;">
                                    <span>${maxCoinName} (High)</span>
                                    <span>${minCoinName} (Low)</span>
                                </div>
                            </div>
                        `;
                    }

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
                    
                    let actionMenuHtml = '';
                    if (!isTerminated) {
                        const pauseStartBtn = isPaused 
                            ? `<button onclick="toggleBotStatus('${bot.bot_id}', 'Running')" style="color: var(--up-color);">▶️ Start SBS Logic</button>`
                            : `<button onclick="toggleBotStatus('${bot.bot_id}', 'Paused')" style="color: var(--warn-color);">⏸️ Pause SBS Logic</button>`;

                        actionMenuHtml = `
                            <div class="actions-wrapper">
                                <button class="menu-btn" onclick="toggleDropdown('${bot.bot_id}')">&#8942;</button>
                                <div class="action-dropdown" id="dropdown-${bot.bot_id}" style="${dropStyle}">
                                    <button onclick="showBotDetails('${bot.bot_id}')">ℹ️ Breakdown</button>
                                    <button onclick="showHistoryModal('${bot.bot_id}')">⏳ Trade History</button>
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
                                    <button onclick="showBotDetails('${bot.bot_id}')">ℹ️ Breakdown</button>
                                    <button onclick="showHistoryModal('${bot.bot_id}')">⏳ Trade History</button>
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

                            ${gapTrackerHtml}

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
                                    <span class="stat-label">Net PNL</span>
                                    <span class="stat-val ${botPnlClass}">${botPnlSign}$${Math.abs(totalPnl).toFixed(2)}</span>
                                    <span style="font-size:0.8rem; margin-left:5px;" class="${botPnlClass}">(${botPnlSign}${Math.abs(pnlPercent).toFixed(2)}%)</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">Reversions Completed</span>
                                    <span class="stat-val" style="color: var(--accent-color);">${bot.cycle_count || 0}</span>
                                </div>
                            </div>

                            <div class="comp-toggle-btn" onclick="toggleComp('${bot.bot_id}')">
                                <span>Tracked Assets (${bot.coins_details.length})</span>
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