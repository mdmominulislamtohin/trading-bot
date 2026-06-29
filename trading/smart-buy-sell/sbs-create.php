<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create SBS Bot | Bitget</title>
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
            --info-color: #3d79f2;
        }

        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding-bottom: 90px;}
        .container { width: 100%; max-width: 650px; padding: 1rem; box-sizing: border-box; }
        
        header { width: 100%; text-align: center; padding: 15px 0; border-bottom: 1px solid var(--border-color); background-color: var(--bg-color); position: sticky; top: 0; z-index: 50;}
        header h1 { font-size: 1.3rem; margin: 0; color: var(--text-primary); display: flex; justify-content: center; align-items: center; gap: 8px;}
        .badge-sbs { background: rgba(138, 43, 226, 0.2); color: var(--accent-color); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; border: 1px solid var(--accent-color);}

        .form-group { margin-top: 1.5rem; background: var(--surface-color); padding: 1.2rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .form-label { display: block; font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 10px; font-weight: 500;}
        
        .form-input { width: 100%; box-sizing: border-box; background: var(--surface-light); border: 1px solid var(--border-color); padding: 14px; border-radius: 8px; color: var(--text-primary); font-size: 1rem; font-family: inherit;}
        .form-input:focus { outline: none; border-color: var(--accent-color); }

        .coins-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .coins-title { font-size: 1.05rem; font-weight: 600; color: var(--text-primary); }
        .allocate-btn { color: var(--info-color); font-size: 0.85rem; cursor: pointer; font-weight: 600; background: rgba(61,121,242,0.1); border: 1px solid rgba(61,121,242,0.3); padding: 6px 12px; border-radius: 6px; transition: 0.2s;}
        .allocate-btn:hover { background: rgba(61,121,242,0.2); }
        
        .asset-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px; }
        
        .hybrid-card { background: #13151a; border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; transition: border-color 0.3s;}
        .hybrid-card:focus-within { border-color: var(--accent-color); }
        
        .hc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;}
        .hc-name { font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 8px; color: var(--accent-color);}
        .hc-price { font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;}
        .delete-btn { color: var(--down-color); font-size: 1.3rem; cursor: pointer; opacity: 0.8; background: none; border: none; padding: 0;}
        
        .hc-balance { font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 12px; display: flex; justify-content: space-between;}
        .hc-balance strong { color: var(--text-primary); }
        
        .hc-inputs { display: flex; gap: 10px; margin-bottom: 12px;}
        .hc-input-group { flex: 1; background: var(--surface-light); border-radius: 8px; border: 1px solid var(--border-color); padding: 6px 10px; position: relative;}
        .hc-input-group label { display: block; font-size: 0.7rem; color: var(--text-secondary); margin-bottom: 4px;}
        .hc-input { width: 100%; background: none; border: none; color: var(--text-primary); font-size: 0.95rem; font-weight: 600; font-family: inherit;}
        .hc-input:focus { outline: none; }
        .max-btn { position: absolute; right: 8px; bottom: 8px; font-size: 0.65rem; background: rgba(138,43,226,0.2); color: var(--accent-color); padding: 2px 6px; border-radius: 4px; cursor: pointer; border: none; font-weight: 700;}

        .hc-footer { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); padding: 8px 10px; border-radius: 6px; font-size: 0.85rem;}
        .hc-val { font-weight: 600; color: var(--text-primary); }
        .hc-pct { font-weight: 700; color: var(--info-color); }
        .hc-error { color: var(--down-color); font-size: 0.75rem; display: none; margin-top: 5px; font-weight: 500;}

        .btn-add-coin { width: 100%; background: rgba(255,255,255,0.05); border: 1px dashed var(--text-secondary); color: var(--text-primary); padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: 0.2s;}
        .btn-add-coin:hover { border-color: var(--accent-color); color: var(--accent-color); }

        .warning-text { font-size: 0.8rem; color: var(--down-color); margin-top: 10px; display: none; font-weight: 500;}
        
        .input-wrap-icon { position: relative; }
        .input-icon-right { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-weight: 600;}

        .grand-total-box { background: linear-gradient(135deg, var(--surface-light), var(--surface-color)); border: 1px solid var(--accent-color); padding: 15px; border-radius: 12px; text-align: center; margin-top: 15px;}
        .gt-label { font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 5px;}
        .gt-val { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); }

        .footer-action { position: fixed; bottom: 0; left: 0; width: 100%; background: var(--surface-color); border-top: 1px solid var(--border-color); display: flex; justify-content: center; padding: 15px 0; z-index: 100;}
        .btn-create { width: 100%; max-width: 620px; background: var(--accent-color); color: #fff; border: none; padding: 16px; border-radius: 8px; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: 0.2s;}
        .btn-create:disabled { background: #2b3139; color: var(--text-secondary); cursor: not-allowed; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: none; justify-content: center; align-items: flex-end; z-index: 1000;}
        .modal-content { background: var(--surface-color); width: 100%; max-width: 650px; height: 80vh; border-radius: 20px 20px 0 0; display: flex; flex-direction: column; animation: slideUp 0.3s ease-out;}
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        
        .modal-header { padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;}
        .modal-header h3 { margin: 0; color: var(--text-primary); font-size: 1.1rem;}
        .close-modal { background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;}
        
        .coin-search { padding: 15px 20px; border-bottom: 1px solid var(--border-color); }
        .search-input { width: 100%; box-sizing: border-box; background: var(--surface-light); border: 1px solid var(--border-color); padding: 10px 15px; border-radius: 8px; color: var(--text-primary);}
        
        .modal-body { flex: 1; overflow-y: auto; padding: 0 20px;}
        .modal-coin-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer;}
        .modal-coin-item:hover { background: rgba(255,255,255,0.02); }
        .left-sec { display: flex; align-items: center; gap: 12px; }
        .checkbox-custom { width: 18px; height: 18px; accent-color: var(--accent-color); cursor: pointer;}
        .modal-coin-sym { font-weight: 600; font-size: 1rem; color: var(--text-primary);}
        .right-sec { text-align: right; }
        .live-price { font-weight: 600; font-size: 0.95rem; color: var(--text-primary);}
        .price-change { font-size: 0.75rem; font-weight: 500; margin-top: 2px;}
        .text-up { color: var(--up-color); }
        .text-down { color: var(--down-color); }
        
        .modal-footer { padding: 15px 20px; border-top: 1px solid var(--border-color); background: var(--surface-light); }
        .btn-confirm { width: 100%; background: var(--accent-color); color: #fff; padding: 14px; border: none; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer;}
        
        #loader-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; display: none; flex-direction: column; justify-content: center; align-items: center; color: var(--accent-color); font-weight: 600; font-size: 1.2rem;}
        .spinner { border: 4px solid rgba(138, 43, 226, 0.3); border-top: 4px solid var(--accent-color); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 15px;}
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div id="loader-overlay">
        <div class="spinner"></div>
        <div id="loader-text">Configuring Strategy...</div>
    </div>

    <header>
        <h1>Smart Buy Sell <span class="badge-sbs">SBS</span></h1>
    </header>

    <div class="container">

        <div class="form-group" style="margin-top: 1rem;">
            <label class="form-label">Strategy Name</label>
            <input type="text" class="form-input" id="strategy-name" placeholder="e.g. SBS Perfect Reversion Bot">
        </div>

        <div class="form-group">
            <div class="coins-header">
                <span class="coins-title">Portfolio Assets</span>
                <button class="allocate-btn" onclick="allocateEquallyByValue()">Allocate Equally ($)</button>
            </div>
            
            <div class="asset-list" id="selected-coins-list"></div>
            
            <button class="btn-add-coin" onclick="openCoinModal()">+ Select Coins for Bot</button>
            
            <div class="warning-text" id="min-coin-warning" style="display: block;">
                Please select at least 2 coins to build a portfolio.
            </div>
            
            <div class="grand-total-box">
                <div class="gt-label">Total Strategy Value</div>
                <div class="gt-val">$<span id="grand-total-display">0.00</span></div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 8px;">
                    Holdings: $<span id="total-holding-val">0.00</span> &nbsp;|&nbsp; Buy New: $<span id="total-new-usdt">0.00</span>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-primary); margin-top: 10px; font-weight: 600; background: rgba(255,255,255,0.05); padding: 6px; border-radius: 6px;">
                    Available Balance: <span id="avail-usdt-balance" style="color: var(--accent-color);">0.00</span> USDT
                </div>
            </div>
            
            <div class="warning-text" id="min-invest-warning" style="text-align: center; margin-top: 15px;">
                Individual coin values are below the safe limits to run the bot.
            </div>

            <div class="warning-text" id="insufficient-usdt-warning" style="text-align: center; margin-top: 15px;">
                Insufficient USDT Balance! You need $<span id="req-usdt">0.00</span> but have $<span id="has-usdt">0.00</span>.
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" title="Difference between Highest and Lowest coin to trigger a trade">Value Gap Trigger (USDT)</label>
            <div class="input-wrap-icon">
                <input type="number" class="form-input" id="gap-trigger" placeholder="Loading min limits..." step="0.1" readonly>
                <span class="input-icon-right">USDT</span>
            </div>
            
            <div class="warning-text" id="gap-limit-warning" style="margin-top: 8px;">
                Value Gap must be at least $<span id="min-gap-req">0.00</span> based on selected coins!
            </div>
            
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 12px; line-height: 1.5; background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px; border-left: 3px solid var(--accent-color);">
                💡 <b>How it works:</b> When the value difference between your top and bottom coin reaches <b>$<span id="hint-gap">X</span></b>, the bot will execute a <b>50% Perfect Reversion</b>.<br><br> It will automatically execute a trade of <b>50% of the actual Gap (Minimum $<span id="hint-trade">Y</span>)</b> from the Top coin to the Bottom coin to balance them perfectly.
            </div>
        </div>

    </div>

    <div class="footer-action">
        <button class="btn-create" id="btn-submit" onclick="createSBS()" disabled>Launch SBS Strategy</button>
    </div>

    <div class="modal-overlay" id="coin-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Coins</h3>
                <button class="close-modal" onclick="closeCoinModal()">&#10005;</button>
            </div>
            <div class="coin-search">
                <input type="text" class="search-input" id="search-coin" placeholder="Search coin..." onkeyup="filterCoins()">
            </div>
            <div class="modal-body" id="modal-coin-list">
                <div id="modal-loading-text" style="text-align: center; color: var(--text-secondary); margin-top: 20px;">Fetching live data...</div>
            </div>
            <div class="modal-footer">
                <button class="btn-confirm" onclick="confirmCoinSelection()">Confirm Selection</button>
            </div>
        </div>
    </div>

    <script>
        let liveTickersData = [];
        let selectedCoins = [];
        let tempModalSelections = new Set();
        let globalWalletBalances = {}; 
        let usdtBalance = 0; // Globally track USDT balance
        
        function getMinTradeSizeUSDT(coinSymbol) {
            const limits = {
                'BTC': 1.05,
                'ETH': 1.15,
                'PAXG': 1.45,
                'LTC': 1.01
            };
            return limits[coinSymbol] || 1.05; 
        }

        const SPOT_API_URL = '../../assets/spot-api.php';
        const PRICE_API_URL = '../../price/price-api.php';

        async function fetchBalance() {
            try {
                const res = await fetch(SPOT_API_URL + '?t=' + Date.now());
                const data = await res.json();
                if (data.status === 'success' && data.data) {
                    data.data.forEach(a => {
                        globalWalletBalances[a.coin] = { available: parseFloat(a.available), frozen: parseFloat(a.frozen) };
                    });
                    usdtBalance = globalWalletBalances['USDT'] ? globalWalletBalances['USDT'].available : 0;
                    if(document.getElementById('avail-usdt-balance')) {
                        document.getElementById('avail-usdt-balance').innerText = usdtBalance.toFixed(2);
                    }
                }
            } catch (e) { console.error("Balance fetch error", e); }
        }

        async function fetchLivePrices() {
            try {
                const res = await fetch(PRICE_API_URL + '?t=' + Date.now());
                const textResponse = await res.text();
                let data;
                try { data = JSON.parse(textResponse); } catch(err) { return; }

                if (data.status === 'success' && data.data) {
                    liveTickersData = data.data.filter(t => t.symbol.endsWith('USDT'));
                    if (document.getElementById('modal-loading-text')) {
                        renderModalCoins(liveTickersData);
                    } else if (document.getElementById('coin-modal').style.display === 'flex') {
                        updatePricesInModalUI();
                    }
                    
                    if (selectedCoins.length > 0) {
                        updateCardLivePrices();
                        calculateAllocations();
                    }
                }
            } catch (e) { console.error(e); }
        }

        function updatePricesInModalUI() {
            liveTickersData.forEach(ticker => {
                const coinName = ticker.symbol.replace('USDT', '');
                const priceEl = document.getElementById(`price-${coinName}`);
                const changeEl = document.getElementById(`change-${coinName}`);
                if (priceEl && changeEl) {
                    const price = parseFloat(ticker.lastPr);
                    const change = parseFloat(ticker.change24h || ticker.chgUtc || 0) * 100; 
                    priceEl.innerText = `$${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6})}`;
                    const changeStr = change >= 0 ? `+${change.toFixed(2)}%` : `${change.toFixed(2)}%`;
                    changeEl.innerText = changeStr;
                    changeEl.className = `price-change ${change >= 0 ? 'text-up' : 'text-down'}`;
                }
            });
        }

        function updateCardLivePrices() {
            selectedCoins.forEach(coin => {
                const ticker = liveTickersData.find(t => t.symbol === coin + 'USDT');
                if(ticker) {
                    const el = document.getElementById(`card-price-${coin}`);
                    if(el) el.innerText = '$' + parseFloat(ticker.lastPr).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6});
                }
            });
        }

        function openCoinModal() {
            tempModalSelections = new Set(selectedCoins);
            document.getElementById('coin-modal').style.display = 'flex';
            document.getElementById('search-coin').value = '';
            if (liveTickersData.length > 0) renderModalCoins(liveTickersData);
        }

        function closeCoinModal() { document.getElementById('coin-modal').style.display = 'none'; }

        function filterCoins() {
            const query = document.getElementById('search-coin').value.toUpperCase();
            const filtered = liveTickersData.filter(t => t.symbol.replace('USDT','').includes(query));
            renderModalCoins(filtered);
        }

        function renderModalCoins(tickersArray) {
            const listObj = document.getElementById('modal-coin-list');
            listObj.innerHTML = '';
            if (tickersArray.length === 0) return;

            const sortedTickers = [...tickersArray].sort((a,b) => a.symbol.localeCompare(b.symbol));

            sortedTickers.forEach(ticker => {
                const coin = ticker.symbol.replace('USDT', '');
                const isChecked = tempModalSelections.has(coin) ? 'checked' : '';
                const price = parseFloat(ticker.lastPr);
                const change = parseFloat(ticker.change24h || ticker.chgUtc || 0) * 100;
                const changeClass = change >= 0 ? 'text-up' : 'text-down';
                const changeSign = change >= 0 ? '+' : '';

                const div = document.createElement('label');
                div.className = 'modal-coin-item';
                div.innerHTML = `
                    <div class="left-sec">
                        <input type="checkbox" class="checkbox-custom" value="${coin}" ${isChecked} onchange="toggleModalSelection(this)">
                        <span class="modal-coin-sym">${coin}</span>
                    </div>
                    <div class="right-sec">
                        <div class="live-price" id="price-${coin}">$${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6})}</div>
                        <div class="price-change ${changeClass}" id="change-${coin}">${changeSign}${change.toFixed(2)}%</div>
                    </div>
                `;
                listObj.appendChild(div);
            });
        }

        function toggleModalSelection(checkbox) {
            if (checkbox.checked) tempModalSelections.add(checkbox.value);
            else tempModalSelections.delete(checkbox.value);
        }

        function confirmCoinSelection() {
            selectedCoins = Array.from(tempModalSelections);
            closeCoinModal();
            renderSelectedCoins();
            calculateAllocations();
            updateGapLimits();
        }

        function removeCoin(coin) {
            selectedCoins = selectedCoins.filter(c => c !== coin);
            renderSelectedCoins();
            calculateAllocations();
            updateGapLimits();
        }

        function setMaxHolding(coin) {
            const avail = globalWalletBalances[coin] ? globalWalletBalances[coin].available : 0;
            const input = document.getElementById(`use-qty-${coin}`);
            if(input) {
                input.value = avail > 0 ? avail : '';
                calculateAllocations();
            }
        }

        function renderSelectedCoins() {
            const listObj = document.getElementById('selected-coins-list');
            listObj.innerHTML = '';
            
            selectedCoins.forEach(coin => {
                const div = document.createElement('div');
                div.className = 'hybrid-card';
                
                const avail = globalWalletBalances[coin] ? globalWalletBalances[coin].available : 0;
                const ticker = liveTickersData.find(t => t.symbol === coin + 'USDT');
                const price = ticker ? parseFloat(ticker.lastPr) : 0;
                
                div.innerHTML = `
                    <div class="hc-header">
                        <span class="hc-name">${coin}</span>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <span class="hc-price" id="card-price-${coin}">$${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6})}</span>
                            <button class="delete-btn" onclick="removeCoin('${coin}')">&#215;</button>
                        </div>
                    </div>
                    <div class="hc-balance">
                        <span>Spot Balance: <strong>${avail.toLocaleString(undefined, {maximumFractionDigits:6})}</strong> ${coin}</span>
                    </div>
                    
                    <div class="hc-inputs">
                        <div class="hc-input-group">
                            <label>Use Holding (Qty)</label>
                            <input type="number" class="hc-input" id="use-qty-${coin}" placeholder="0" step="any" min="0" oninput="calculateAllocations()">
                            <button class="max-btn" onclick="setMaxHolding('${coin}')">MAX</button>
                        </div>
                        <div class="hc-input-group">
                            <label>Buy New (USDT)</label>
                            <input type="number" class="hc-input" id="buy-usdt-${coin}" placeholder="0" step="any" min="0" oninput="calculateAllocations()">
                        </div>
                    </div>
                    <div class="hc-error" id="err-${coin}">Not enough spot balance!</div>
                    
                    <div class="hc-footer">
                        <span>Value: <span class="hc-val">$<span id="val-${coin}">0.00</span></span></span>
                        <span>Alloc: <span class="hc-pct" id="pct-${coin}">0%</span></span>
                    </div>
                `;
                listObj.appendChild(div);
            });
        }

        function allocateEquallyByValue() {
            if (selectedCoins.length === 0) return;
            
            // Calculate Maximum possible investment
            let maxHoldingValForSelected = 0;
            selectedCoins.forEach(coin => {
                const ticker = liveTickersData.find(t => t.symbol === coin + 'USDT');
                const price = ticker ? parseFloat(ticker.lastPr) : 0;
                const availQty = globalWalletBalances[coin] ? globalWalletBalances[coin].available : 0;
                maxHoldingValForSelected += (availQty * price);
            });
            const maxPossibleInvestment = maxHoldingValForSelected + usdtBalance;

            const totalStr = prompt(`Enter Total Portfolio Target Value in USDT to allocate equally among ${selectedCoins.length} coins:\n\nMax Available: $${maxPossibleInvestment.toFixed(2)} (USDT + Selected Holdings)`);
            const total = parseFloat(totalStr);
            if (!total || isNaN(total) || total <= 0) return;
            
            if (total > maxPossibleInvestment) {
                alert(`Error: Target value ($${total.toFixed(2)}) exceeds your max available balance ($${maxPossibleInvestment.toFixed(2)}).`);
                return;
            }

            const targetPerCoin = total / selectedCoins.length;

            selectedCoins.forEach(coin => {
                const ticker = liveTickersData.find(t => t.symbol === coin + 'USDT');
                const price = ticker ? parseFloat(ticker.lastPr) : 0;
                if(price <= 0) return;

                const availQty = globalWalletBalances[coin] ? globalWalletBalances[coin].available : 0;
                const availUsd = availQty * price;

                const useQtyInput = document.getElementById(`use-qty-${coin}`);
                const buyUsdtInput = document.getElementById(`buy-usdt-${coin}`);
                
                const minTradeLimit = getMinTradeSizeUSDT(coin) + 0.05;

                if (availUsd >= targetPerCoin) {
                    const neededQty = targetPerCoin / price;
                    useQtyInput.value = neededQty.toFixed(6);
                    buyUsdtInput.value = '';
                } else {
                    useQtyInput.value = availQty > 0 ? availQty : '';
                    let remainingUsd = targetPerCoin - availUsd;
                    
                    if (remainingUsd > 0 && remainingUsd < minTradeLimit) {
                        remainingUsd = minTradeLimit;
                    }
                    
                    buyUsdtInput.value = remainingUsd.toFixed(2);
                }
            });
            calculateAllocations();
        }

        let coinValuesMap = {};
        let grandTotalValue = 0;
        let dynamicMinGap = 2.10; 
        let totalBuyUsdtGlobal = 0; // Track Total USDT needed

        function updateGapLimits() {
            if (selectedCoins.length < 2) {
                document.getElementById('gap-trigger').readOnly = true;
                document.getElementById('gap-trigger').placeholder = 'Select coins first';
                return;
            }

            let maxMinTrade = 0;
            selectedCoins.forEach(coin => {
                const limit = getMinTradeSizeUSDT(coin);
                if (limit > maxMinTrade) maxMinTrade = limit; 
            });

            const safeTradeAmount = maxMinTrade + 0.05; 
            dynamicMinGap = parseFloat((safeTradeAmount * 2).toFixed(2));
            
            const gapInput = document.getElementById('gap-trigger');
            gapInput.readOnly = false;
            gapInput.min = dynamicMinGap;
            gapInput.placeholder = `Min. ${dynamicMinGap.toFixed(2)}`;
            document.getElementById('min-gap-req').innerText = dynamicMinGap.toFixed(2);

            validateForm();
        }

        function calculateAllocations() {
            grandTotalValue = 0;
            coinValuesMap = {};
            let hasError = false;
            
            let totalHoldingUsdt = 0;
            totalBuyUsdtGlobal = 0;

            selectedCoins.forEach(coin => {
                const useQtyInput = document.getElementById(`use-qty-${coin}`);
                const buyUsdtInput = document.getElementById(`buy-usdt-${coin}`);
                const errBox = document.getElementById(`err-${coin}`);
                
                const useQty = useQtyInput ? (parseFloat(useQtyInput.value) || 0) : 0;
                const buyUsdt = buyUsdtInput ? (parseFloat(buyUsdtInput.value) || 0) : 0;
                
                const avail = globalWalletBalances[coin] ? globalWalletBalances[coin].available : 0;
                const minTradeLimit = getMinTradeSizeUSDT(coin) + 0.05;
                
                let coinError = '';

                if (useQty > avail) {
                    coinError = 'Not enough spot balance!';
                    if(useQtyInput) useQtyInput.style.color = 'var(--down-color)';
                } else {
                    if(useQtyInput) useQtyInput.style.color = 'var(--text-primary)';
                }
                
                if (buyUsdt > 0 && buyUsdt < minTradeLimit) {
                    coinError = `Min buy is $${minTradeLimit.toFixed(2)}!`;
                    if(buyUsdtInput) buyUsdtInput.style.color = 'var(--down-color)';
                } else {
                    if(buyUsdtInput) buyUsdtInput.style.color = 'var(--text-primary)';
                }

                if (coinError !== '') {
                    errBox.innerText = coinError;
                    errBox.style.display = 'block';
                    hasError = true;
                } else {
                    errBox.style.display = 'none';
                }

                const ticker = liveTickersData.find(t => t.symbol === coin + 'USDT');
                const price = ticker ? parseFloat(ticker.lastPr) : 0;
                
                const holdingValueUsdt = useQty * price;
                const totalCoinValueUsdt = holdingValueUsdt + buyUsdt;
                
                totalHoldingUsdt += holdingValueUsdt;
                totalBuyUsdtGlobal += buyUsdt;
                
                coinValuesMap[coin] = totalCoinValueUsdt;
                grandTotalValue += totalCoinValueUsdt;
                
                const valDisplay = document.getElementById(`val-${coin}`);
                if(valDisplay) valDisplay.innerText = totalCoinValueUsdt.toFixed(2);
            });

            document.getElementById('grand-total-display').innerText = grandTotalValue.toFixed(2);
            document.getElementById('total-holding-val').innerText = totalHoldingUsdt.toFixed(2);
            document.getElementById('total-new-usdt').innerText = totalBuyUsdtGlobal.toFixed(2);

            selectedCoins.forEach(coin => {
                const pctDisplay = document.getElementById(`pct-${coin}`);
                if (grandTotalValue > 0) {
                    const pct = Math.round((coinValuesMap[coin] / grandTotalValue) * 100);
                    if(pctDisplay) pctDisplay.innerText = pct + '%';
                } else {
                    if(pctDisplay) pctDisplay.innerText = '0%';
                }
            });

            window.hybridHasError = hasError;
            validateForm();
        }

        document.getElementById('gap-trigger').addEventListener('input', function() {
            const gap = parseFloat(this.value) || 0;
            document.getElementById('hint-gap').innerText = gap > 0 ? gap.toFixed(2) : 'X';
            
            const tradeAmount = gap / 2;
            document.getElementById('hint-trade').innerText = gap > 0 ? tradeAmount.toFixed(2) : 'X/2';
            document.getElementById('hint-trade2').innerText = gap > 0 ? tradeAmount.toFixed(2) : 'X/2';
            
            validateForm();
        });

        document.getElementById('strategy-name').addEventListener('input', validateForm);

        function validateForm() {
            const name = document.getElementById('strategy-name').value.trim();
            const gapTrigger = parseFloat(document.getElementById('gap-trigger').value) || 0;
            const tradeAmount = gapTrigger / 2;
            
            const minCoinWarning = document.getElementById('min-coin-warning');
            const gapWarning = document.getElementById('gap-limit-warning');
            const minInvestWarning = document.getElementById('min-invest-warning');
            const usdtWarning = document.getElementById('insufficient-usdt-warning');

            if (selectedCoins.length < 2) { minCoinWarning.style.display = 'block'; } 
            else { minCoinWarning.style.display = 'none'; }

            gapWarning.style.display = (gapTrigger > 0 && gapTrigger < dynamicMinGap) ? 'block' : 'none';

            let hasSufficientValue = true;
            if (gapTrigger >= dynamicMinGap) {
                selectedCoins.forEach(coin => {
                    if (coinValuesMap[coin] < (tradeAmount * 2)) {
                        hasSufficientValue = false;
                    }
                });
            } else {
                hasSufficientValue = false;
            }

            minInvestWarning.style.display = (selectedCoins.length >= 2 && !hasSufficientValue) ? 'block' : 'none';

            // Checking USDT Balance
            if (totalBuyUsdtGlobal > usdtBalance) {
                usdtWarning.style.display = 'block';
                document.getElementById('req-usdt').innerText = totalBuyUsdtGlobal.toFixed(2);
                document.getElementById('has-usdt').innerText = usdtBalance.toFixed(2);
            } else {
                usdtWarning.style.display = 'none';
            }

            const isValid = name !== "" && 
                            selectedCoins.length >= 2 && 
                            !window.hybridHasError &&
                            gapTrigger >= dynamicMinGap && 
                            hasSufficientValue &&
                            (totalBuyUsdtGlobal <= usdtBalance); // Must have enough USDT

            document.getElementById('btn-submit').disabled = !isValid;
        }

        async function createSBS() {
            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            document.getElementById('loader-overlay').style.display = 'flex';

            const payload = {
                strategy_name: document.getElementById('strategy-name').value.trim(),
                gap_trigger: parseFloat(document.getElementById('gap-trigger').value),
                trade_amount: parseFloat(document.getElementById('gap-trigger').value) / 2, 
                total_strategy_value: grandTotalValue,
                coins: []
            };

            selectedCoins.forEach(coin => {
                const useQtyInput = document.getElementById(`use-qty-${coin}`);
                const buyUsdtInput = document.getElementById(`buy-usdt-${coin}`);
                
                payload.coins.push({
                    coin_name: coin,
                    use_holding_qty: useQtyInput ? (parseFloat(useQtyInput.value) || 0) : 0,
                    buy_usdt: buyUsdtInput ? (parseFloat(buyUsdtInput.value) || 0) : 0,
                    calculated_usd_value: coinValuesMap[coin]
                });
            });

            try {
                const response = await fetch('sbs-create-process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.status === 'success' || data.status === 'warning') {
                    document.getElementById('loader-text').innerText = "Strategy Successfully Configured!";
                    if(data.status === 'warning') alert(data.message + "\nErrors: " + JSON.stringify(data.errors));
                    
                    setTimeout(() => {
                        window.location.href = 'sbs-bot-list.php';
                    }, 1000);
                } else {
                    document.getElementById('loader-overlay').style.display = 'none';
                    alert("Error: " + data.message + (data.errors ? "\n\nDetails: " + JSON.stringify(data.errors) : ""));
                    btn.disabled = false;
                }

            } catch (error) {
                document.getElementById('loader-overlay').style.display = 'none';
                alert("Network error. Please try again.");
                btn.disabled = false;
            }
        }

        fetchBalance();
        fetchLivePrices();
        setInterval(fetchLivePrices, 5000); 
    </script>
</body>
</html>