<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Infinity Grid Pro | Bitget</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0b0e11;
            --surface-color: #181a20;
            --surface-light: #1e2329;
            --border-color: #2b3139;
            --text-primary: #eaecef;
            --text-secondary: #848e9c;
            --accent-color: #3b82f6;
            --up-color: #0ecb81;
            --down-color: #f6465d;
            --info-color: #3d79f2;
        }

        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding-bottom: 100px;}
        .container { width: 100%; max-width: 700px; padding: 1rem; box-sizing: border-box; }
        
        header { width: 100%; text-align: center; padding: 15px 0; border-bottom: 1px solid var(--border-color); background-color: var(--bg-color); position: sticky; top: 0; z-index: 50;}
        header h1 { font-size: 1.3rem; margin: 0; color: var(--text-primary); display: flex; justify-content: center; align-items: center; gap: 8px;}
        .badge-igp { background: rgba(59, 130, 246, 0.2); color: var(--accent-color); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; border: 1px solid var(--accent-color);}

        .form-group { margin-top: 1.5rem; background: var(--surface-color); padding: 1.2rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .form-label { display: block; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600; text-transform: uppercase;}
        
        .form-input { width: 100%; box-sizing: border-box; background: var(--surface-light); border: 1px solid var(--border-color); padding: 14px; border-radius: 8px; color: var(--text-primary); font-size: 1rem; font-family: inherit;}
        .form-input:focus { outline: none; border-color: var(--accent-color); }
        .form-input::placeholder { color: #475569; }

        .btn-add-coin { width: 100%; background: rgba(255,255,255,0.05); border: 1px dashed var(--text-secondary); color: var(--text-primary); padding: 14px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: 0.2s; margin-bottom: 10px;}
        .btn-add-coin:hover { border-color: var(--accent-color); color: var(--accent-color); background: rgba(59, 130, 246, 0.05);}

        .hybrid-card { background: #13151a; border: 1px solid var(--accent-color); border-radius: 12px; padding: 15px; margin-bottom: 15px; display: none;}
        .hc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;}
        .hc-name { font-weight: 700; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; color: var(--accent-color);}
        .hc-price { font-size: 0.9rem; color: var(--text-secondary); font-weight: 600;}
        .delete-btn { color: var(--text-secondary); font-size: 1.2rem; cursor: pointer; background: none; border: none; padding: 0;}
        .delete-btn:hover { color: var(--down-color); }
        
        .hc-balance { font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 12px;}
        .hc-inputs { display: flex; gap: 10px; margin-bottom: 12px;}
        .hc-input-group { flex: 1; background: var(--surface-light); border-radius: 8px; border: 1px solid var(--border-color); padding: 8px 10px; position: relative;}
        .hc-input-group label { display: block; font-size: 0.7rem; color: var(--text-secondary); margin-bottom: 4px;}
        .hc-input { width: 100%; background: none; border: none; color: var(--text-primary); font-size: 1rem; font-weight: 600; font-family: inherit;}
        .hc-input:focus { outline: none; }
        
        .max-btn { position: absolute; right: 8px; bottom: 8px; font-size: 0.65rem; background: rgba(59,130,246,0.15); color: var(--accent-color); padding: 4px 8px; border-radius: 4px; cursor: pointer; border: 1px solid rgba(59,130,246,0.3); font-weight: 700; transition: 0.2s;}
        .max-btn:hover { background: rgba(59,130,246,0.25); }

        .grid-inputs-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;}
        
        .stats-box { background: rgba(59, 130, 246, 0.05); border: 1px dashed var(--accent-color); padding: 15px; border-radius: 10px; margin-top: 20px;}
        .stat-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-secondary);}
        .stat-val { font-weight: 700; color: var(--text-primary); }
        
        .custom-checkbox { display: flex; align-items: center; gap: 10px; margin-top: 15px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color); cursor: pointer;}
        .custom-checkbox input { width: 18px; height: 18px; accent-color: var(--accent-color); cursor: pointer;}
        
        .preview-btn { width: 100%; background: transparent; border: 1px solid var(--info-color); color: var(--info-color); padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 15px; transition: 0.2s;}
        .preview-btn:hover { background: rgba(61,121,242,0.1); }
        
        .preview-container { display: none; margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; background: #13151a;}
        .preview-table { width: 100%; border-collapse: collapse; font-size: 0.85rem;}
        .preview-table th, .preview-table td { padding: 10px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .preview-table th { background: var(--surface-light); color: var(--text-secondary); position: sticky; top: 0; font-weight: 600;}
        .text-buy { color: var(--up-color); }

        .warning-text { font-size: 0.8rem; color: var(--down-color); margin-top: 10px; display: none; font-weight: 500;}

        .footer-action { position: fixed; bottom: 0; left: 0; width: 100%; background: var(--surface-color); border-top: 1px solid var(--border-color); display: flex; justify-content: center; padding: 15px 0; z-index: 100;}
        .btn-create { width: 100%; max-width: 650px; background: var(--accent-color); color: #fff; border: none; padding: 16px; border-radius: 8px; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: 0.2s;}
        .btn-create:disabled { background: #2b3139; color: var(--text-secondary); cursor: not-allowed; }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: none; justify-content: center; align-items: flex-end; z-index: 1000;}
        .modal-content { background: var(--surface-color); width: 100%; max-width: 700px; height: 80vh; border-radius: 20px 20px 0 0; display: flex; flex-direction: column; animation: slideUp 0.3s ease-out;}
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        
        .modal-header { padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;}
        .modal-header h3 { margin: 0; color: var(--text-primary); font-size: 1.1rem;}
        .close-modal { background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;}
        
        .coin-search { padding: 15px 20px; border-bottom: 1px solid var(--border-color); }
        .search-input { width: 100%; box-sizing: border-box; background: var(--surface-light); border: 1px solid var(--border-color); padding: 10px 15px; border-radius: 8px; color: var(--text-primary);}
        
        .modal-body { flex: 1; overflow-y: auto; padding: 0 20px;}
        .modal-coin-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer;}
        .modal-coin-item:hover { background: rgba(255,255,255,0.02); }
        .modal-coin-sym { font-weight: 700; font-size: 1.05rem; color: var(--text-primary);}
        
        #loader-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; display: none; flex-direction: column; justify-content: center; align-items: center; color: var(--accent-color); font-weight: 600; font-size: 1.2rem;}
    </style>
</head>
<body>

    <div id="loader-overlay">Configuring Infinity Grid...</div>

    <header>
        <h1>Infinity Grid Pro <span class="badge-igp">IGP</span></h1>
    </header>

    <div class="container">

        <div class="form-group" style="margin-top: 1rem;">
            <label class="form-label">Strategy Name</label>
            <input type="text" class="form-input" id="strategy-name" placeholder="e.g. WLD Infinity Run">
        </div>

        <div class="form-group">
            
            <label class="form-label">Total Investment (USDT)</label>
            <input type="number" class="form-input" id="total-investment" placeholder="Target strategy value e.g. 1000" step="any" oninput="calculateGridEngine()" style="margin-bottom: 20px; font-weight: 600; font-size: 1.1rem; color: var(--up-color);">
            
            <button class="btn-add-coin" id="btn-open-modal" onclick="openCoinModal()">+ Select Coin Pair</button>
            
            <div class="hybrid-card" id="selected-coin-card">
                <div class="hc-header">
                    <span class="hc-name" id="card-coin-name">BTC</span>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <span class="hc-price" id="card-coin-price">$0.00</span>
                        <button class="delete-btn" onclick="removeCoin()">&#215;</button>
                    </div>
                </div>
                <div class="hc-balance">
                    Spot Balance: <strong id="card-spot-bal" style="color:var(--text-primary);">0.00</strong> <span id="card-coin-sym">BTC</span>
                </div>
                
                <div class="hc-inputs">
                    <div class="hc-input-group">
                        <label>Use Holding (Qty)</label>
                        <input type="number" class="hc-input" id="use-qty" placeholder="0" step="any" min="0" oninput="calculateGridEngine()" style="padding-right: 50px;">
                        <button type="button" class="max-btn" onclick="setMaxHolding()">MAX</button>
                    </div>
                    
                    <div class="hc-input-group" style="background: rgba(255,255,255,0.02);">
                        <label>Required New (USDT)</label>
                        <input type="number" class="hc-input" id="req-usdt" placeholder="0.00" readonly style="color: var(--info-color);">
                    </div>
                </div>
                
                <div class="warning-text" id="err-holding-exceeds">Holding value exceeds total target investment! Increase Total Investment.</div>
                <div class="warning-text" id="err-balance">Not enough spot coin balance!</div>
            </div>

            <div style="font-size: 0.85rem; color: var(--text-primary); margin-top: 10px; font-weight: 600; text-align: right;">
                Available Wallet: <span id="avail-usdt-balance" style="color: var(--accent-color);">0.00</span> USDT
            </div>
            <div class="warning-text" id="err-usdt" style="text-align: right;">Insufficient USDT Balance to cover requirement!</div>
        </div>

        <div class="form-group" style="position: relative;">
            <div id="grid-overlay" style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(24,26,32,0.8); z-index: 10; display: flex; justify-content: center; align-items: center; border-radius: 12px; font-weight: 600; color: var(--text-secondary);">
                Select a coin & investment first
            </div>

            <div class="grid-inputs-row">
                <div>
                    <label class="form-label">Lower Range ($)</label>
                    <input type="number" class="form-input" id="lower-price" placeholder="Bottom limit" step="any" oninput="calculateGridEngine()">
                </div>
                <div>
                    <label class="form-label">Geometric Step (%)</label>
                    <input type="number" class="form-input" id="step-pct" value="1.5" step="0.1" min="0.5" oninput="calculateGridEngine()">
                </div>
            </div>

            <label class="custom-checkbox">
                <input type="checkbox" id="auto-compound" checked>
                <span style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">Enable Auto-Compounding</span>
            </label>

            <div class="stats-box">
                <div class="stat-row">
                    <span>Minimum Inv. Required:</span>
                    <span class="stat-val" style="color: var(--info-color);">$<span id="stat-min-req">0.00</span></span>
                </div>
                <div style="height: 1px; background: rgba(255,255,255,0.05); margin: 10px 0;"></div>
                <div class="stat-row">
                    <span>Total Grids (Down):</span>
                    <span class="stat-val" id="stat-grids">0</span>
                </div>
                <div class="stat-row">
                    <span>Per Grid Order Size:</span>
                    <span class="stat-val">$<span id="stat-order-size">0.00</span></span>
                </div>
                <div class="stat-row">
                    <span>Expected Profit / Match:</span>
                    <span class="stat-val" style="color: var(--up-color);">$<span id="stat-profit">0.00</span></span>
                </div>
            </div>

            <div class="warning-text" id="err-min-inv">Total investment is below the minimum requirement!</div>
            <div class="warning-text" id="err-lower-price">Lower range must be below the current market price!</div>

            <button type="button" class="preview-btn" onclick="togglePreview()">📊 View Grid Book Preview</button>
            
            <div class="preview-container" id="preview-box">
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Level / SN</th>
                            <th>Grid Price</th>
                            <th>Size (USD)</th>
                            <th>Coin Amount</th>
                        </tr>
                    </thead>
                    <tbody id="preview-body"></tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="footer-action">
        <button class="btn-create" id="btn-submit" onclick="createIGP()" disabled>Launch IGP Strategy</button>
    </div>

    <div class="modal-overlay" id="coin-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Coin Pair</h3>
                <button class="close-modal" onclick="closeCoinModal()">&#10005;</button>
            </div>
            <div class="coin-search">
                <input type="text" class="search-input" id="search-coin" placeholder="Search coin..." onkeyup="filterCoins()">
            </div>
            <div class="modal-body" id="modal-coin-list">
                <div style="text-align: center; color: var(--text-secondary); margin-top: 20px;">Fetching live data...</div>
            </div>
        </div>
    </div>

    <script>
        let liveTickersData = [];
        let globalWalletBalances = {}; 
        let usdtBalance = 0; 
        
        let selectedCoin = null;
        let currentLivePrice = 0;
        let minTradeSizeExchange = 1.05; 
        let feeRate = 0.001; 
        
        const SPOT_API_URL = '../../assets/spot-api.php';
        const PRICE_API_URL = '../../price/price-api.php';

        function getMinTradeSizeUSDT(coinSymbol) {
            const limits = { 'BTC': 1.05, 'ETH': 1.15, 'PAXG': 1.45, 'LTC': 1.01 };
            return limits[coinSymbol] || 1.05; 
        }

        async function fetchBalance() {
            try {
                const res = await fetch(SPOT_API_URL + '?t=' + Date.now());
                const data = await res.json();
                if (data.status === 'success' && data.data) {
                    data.data.forEach(a => { globalWalletBalances[a.coin] = { available: parseFloat(a.available) }; });
                    usdtBalance = globalWalletBalances['USDT'] ? globalWalletBalances['USDT'].available : 0;
                    document.getElementById('avail-usdt-balance').innerText = usdtBalance.toFixed(2);
                    if(selectedCoin) updateCardBalance();
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
                    
                    if (document.getElementById('coin-modal').style.display === 'flex') {
                        updatePricesInModalUI();
                    } else if (!selectedCoin) {
                        renderModalCoins(liveTickersData);
                    }
                    
                    if (selectedCoin) {
                        const ticker = liveTickersData.find(t => t.symbol === selectedCoin + 'USDT');
                        if (ticker) {
                            currentLivePrice = parseFloat(ticker.lastPr);
                            document.getElementById('card-coin-price').innerText = '$' + currentLivePrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6});
                            calculateGridEngine();
                        }
                    }
                }
            } catch (e) { console.error(e); }
        }

        function openCoinModal() {
            document.getElementById('coin-modal').style.display = 'flex';
            document.getElementById('search-coin').value = '';
            renderModalCoins(liveTickersData);
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
            
            if (!tickersArray || tickersArray.length === 0) {
                listObj.innerHTML = '<div style="text-align: center; color: var(--text-secondary); margin-top: 20px;">Fetching live data or no coins found...</div>';
                return;
            }

            const sortedTickers = [...tickersArray].sort((a,b) => a.symbol.localeCompare(b.symbol));

            sortedTickers.forEach(ticker => {
                const coin = ticker.symbol.replace('USDT', '');
                const price = parseFloat(ticker.lastPr);
                const change = parseFloat(ticker.change24h || ticker.chgUtc || 0) * 100;
                const changeClass = change >= 0 ? 'color: var(--up-color);' : 'color: var(--down-color);';
                
                const div = document.createElement('div');
                div.className = 'modal-coin-item';
                div.onclick = () => selectCoin(coin, price);
                div.innerHTML = `
                    <span class="modal-coin-sym">${coin}</span>
                    <div style="text-align: right;">
                        <div id="price-${coin}" style="font-weight: 600; color: var(--text-primary);">$${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6})}</div>
                        <div id="change-${coin}" style="font-size: 0.75rem; font-weight: 500; ${changeClass}">${change >= 0 ? '+' : ''}${change.toFixed(2)}%</div>
                    </div>
                `;
                listObj.appendChild(div);
            });
        }

        function updatePricesInModalUI() {
            if (liveTickersData.length === 0) return;
            
            if (document.getElementById('modal-coin-list').innerHTML.includes("Fetching live data")) {
                renderModalCoins(liveTickersData);
                return;
            }

            liveTickersData.forEach(ticker => {
                const coinName = ticker.symbol.replace('USDT', '');
                const priceEl = document.getElementById(`price-${coinName}`);
                const changeEl = document.getElementById(`change-${coinName}`);
                if (priceEl && changeEl) {
                    const change = parseFloat(ticker.change24h || ticker.chgUtc || 0) * 100;
                    priceEl.innerText = `$${parseFloat(ticker.lastPr).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6})}`;
                    changeEl.innerText = `${change >= 0 ? '+' : ''}${change.toFixed(2)}%`;
                    changeEl.style.color = change >= 0 ? 'var(--up-color)' : 'var(--down-color)';
                }
            });
        }

        function selectCoin(coin, price) {
            selectedCoin = coin;
            currentLivePrice = price;
            minTradeSizeExchange = getMinTradeSizeUSDT(coin) + 0.05; 
            
            document.getElementById('card-coin-name').innerText = coin;
            document.getElementById('card-coin-sym').innerText = coin;
            document.getElementById('card-coin-price').innerText = '$' + price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6});
            
            const suggestedLower = price * 0.7;
            let precision = price < 1 ? 4 : 2;
            document.getElementById('lower-price').value = suggestedLower.toFixed(precision);
            
            document.getElementById('use-qty').value = '';
            document.getElementById('req-usdt').value = '';
            
            updateCardBalance();
            
            document.getElementById('btn-open-modal').style.display = 'none';
            document.getElementById('selected-coin-card').style.display = 'block';
            
            closeCoinModal();
            calculateGridEngine();
        }

        function removeCoin() {
            selectedCoin = null;
            document.getElementById('btn-open-modal').style.display = 'block';
            document.getElementById('selected-coin-card').style.display = 'none';
            document.getElementById('grid-overlay').style.display = 'flex';
            document.getElementById('preview-box').style.display = 'none';
            
            document.getElementById('lower-price').value = '';
            
            if (document.getElementById('modal-coin-list').children.length <= 1) {
                renderModalCoins(liveTickersData);
            }
            
            validateForm();
        }

        function updateCardBalance() {
            const avail = globalWalletBalances[selectedCoin] ? globalWalletBalances[selectedCoin].available : 0;
            document.getElementById('card-spot-bal').innerText = avail.toLocaleString(undefined, {maximumFractionDigits:6});
        }

        // Logic for MAX button inside Holding Qty
        function setMaxHolding() {
            if (!selectedCoin || currentLivePrice <= 0) return;
            const avail = globalWalletBalances[selectedCoin] ? globalWalletBalances[selectedCoin].available : 0;
            const totalInv = parseFloat(document.getElementById('total-investment').value) || 0;
            
            let maxQty = avail;
            
            // Limit holding to not exceed total target investment
            if(totalInv > 0) {
                const maxAllowed = totalInv / currentLivePrice;
                if(avail > maxAllowed) {
                    maxQty = maxAllowed;
                }
            }
            
            // Format to 6 decimals to prevent floating point extreme numbers
            maxQty = Math.floor(maxQty * 1000000) / 1000000;
            document.getElementById('use-qty').value = maxQty > 0 ? maxQty : '';
            
            calculateGridEngine();
        }

        // --- Core Mathematical Engine ---
        let globalGridData = {};

        function validateForm() {
            const strategyName = document.getElementById('strategy-name').value.trim();
            const isValid = strategyName !== "" && selectedCoin && globalGridData.netProfit > 0 && globalGridData.gridsCount > 0 && !globalGridData.hasError;
            document.getElementById('btn-submit').disabled = !isValid;
        }

        function calculateGridEngine() {
            if (!selectedCoin || currentLivePrice <= 0) return;

            const totalInvStr = document.getElementById('total-investment').value;
            const useQtyStr = document.getElementById('use-qty').value;
            const lowerPriceStr = document.getElementById('lower-price').value;
            const stepPctStr = document.getElementById('step-pct').value;

            const totalInv = parseFloat(totalInvStr) || 0;
            const useQty = parseFloat(useQtyStr) || 0;
            const lowerPrice = parseFloat(lowerPriceStr) || 0;
            const stepPct = parseFloat(stepPctStr) || 0;

            let hasError = false;
            
            // Remove overlay only if investment is set
            if(totalInv > 0) {
                document.getElementById('grid-overlay').style.display = 'none';
            } else {
                document.getElementById('grid-overlay').style.display = 'flex';
                hasError = true;
            }

            // 1. Calculate Required USDT
            const holdingUsd = useQty * currentLivePrice;
            let requiredUsdt = totalInv - holdingUsd;

            if (requiredUsdt < 0) {
                document.getElementById('err-holding-exceeds').style.display = 'block';
                requiredUsdt = 0;
                hasError = true;
            } else {
                document.getElementById('err-holding-exceeds').style.display = 'none';
            }

            document.getElementById('req-usdt').value = requiredUsdt > 0 ? requiredUsdt.toFixed(2) : '';

            // 2. Balance Validation
            const availCoin = globalWalletBalances[selectedCoin] ? globalWalletBalances[selectedCoin].available : 0;
            if (useQty > availCoin) {
                document.getElementById('err-balance').style.display = 'block';
                hasError = true;
            } else {
                document.getElementById('err-balance').style.display = 'none';
            }

            if (requiredUsdt > usdtBalance) {
                document.getElementById('err-usdt').style.display = 'block';
                hasError = true;
            } else {
                document.getElementById('err-usdt').style.display = 'none';
            }

            // 3. Reset Warnings
            document.getElementById('err-lower-price').style.display = 'none';
            document.getElementById('err-min-inv').style.display = 'none';

            let gridsCount = 0;
            let orderSize = 0;
            let netProfit = 0;
            let minInvReq = 0;

            if (lowerPrice > 0 && lowerPrice < currentLivePrice && stepPct >= 0.5) {
                let percentRate = stepPct / 100;
                let ratio = 1 + percentRate;
                
                gridsCount = Math.floor(Math.log(currentLivePrice / lowerPrice) / Math.log(ratio));
                if (gridsCount < 1) gridsCount = 1;

                let baseUpsideFactor = ratio / percentRate; 
                let divider = gridsCount + (baseUpsideFactor * (1 + feeRate));
                
                minInvReq = minTradeSizeExchange * divider;
                document.getElementById('stat-min-req').innerText = minInvReq.toFixed(2);

                let baseUpsideValue = 0;
                let initialReserveRequired = 0;
                let upsideCoins = 0;

                if (totalInv > 0) {
                    orderSize = totalInv / divider;
                    let grossProfit = orderSize * percentRate;
                    let buyFee = orderSize * feeRate;
                    let sellFee = (orderSize * ratio) * feeRate;
                    netProfit = grossProfit - (buyFee + sellFee);

                    baseUpsideValue = orderSize * baseUpsideFactor;
                    initialReserveRequired = orderSize * gridsCount;
                    upsideCoins = baseUpsideValue / currentLivePrice;
                }

                if (totalInv > 0 && totalInv < minInvReq) {
                    document.getElementById('err-min-inv').style.display = 'block';
                    hasError = true;
                }

                globalGridData = { ratio, gridsCount, orderSize, startPrice: currentLivePrice, baseUpsideValue, initialReserveRequired, upsideCoins, netProfit, hasError };
                renderPreview();
            } else if (lowerPrice >= currentLivePrice) {
                document.getElementById('err-lower-price').style.display = 'block';
                hasError = true;
                globalGridData.hasError = true;
            }

            document.getElementById('stat-grids').innerText = gridsCount;
            document.getElementById('stat-order-size').innerText = orderSize.toFixed(2);
            document.getElementById('stat-profit').innerText = netProfit.toFixed(4);

            validateForm();
        }

        document.getElementById('strategy-name').addEventListener('input', validateForm);
        document.getElementById('total-investment').addEventListener('input', calculateGridEngine);

        function togglePreview() {
            const box = document.getElementById('preview-box');
            box.style.display = box.style.display === 'block' ? 'none' : 'block';
        }

        function renderPreview() {
            const tbody = document.getElementById('preview-body');
            tbody.innerHTML = '';
            
            if (!globalGridData.gridsCount || globalGridData.orderSize <= 0) return;

            const getPrice = (level) => globalGridData.startPrice * Math.pow(globalGridData.ratio, level);
            const sizeFormatted = globalGridData.orderSize.toFixed(2);
            let html = '';

            html += `
                <tr style="background: rgba(14, 203, 129, 0.05);">
                    <td colspan="4" style="padding: 15px;">
                        <div style="display:flex; justify-content: space-between; font-weight:600; font-size: 0.85rem; margin-bottom: 8px;">
                            <span style="color: var(--text-secondary);">Total Upside Coins Needed:</span>
                            <span style="color: var(--up-color);">${globalGridData.upsideCoins.toFixed(6)} Coins</span>
                        </div>
                        <div style="display:flex; justify-content: space-between; font-weight:600; font-size: 0.85rem; margin-bottom: 8px;">
                            <span style="color: var(--text-secondary);">Current Upside Value:</span>
                            <span style="color: var(--text-primary);">$${globalGridData.baseUpsideValue.toFixed(2)}</span>
                        </div>
                        <div style="display:flex; justify-content: space-between; font-weight:600; font-size: 0.85rem;">
                            <span style="color: var(--text-secondary);">Downside USDT Reserved:</span>
                            <span style="color: var(--down-color);">$${globalGridData.initialReserveRequired.toFixed(2)}</span>
                        </div>
                    </td>
                </tr>
            `;

            html += `
                <tr style="background: rgba(255,255,255,0.05); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                    <td style="color: var(--text-primary); font-weight: 700;">START PRICE</td>
                    <td style="color: var(--text-primary); font-weight: 700;">$${globalGridData.startPrice.toFixed(4)}</td>
                    <td class="text-secondary">-</td>
                    <td class="text-secondary">-</td>
                </tr>
            `;
            
            let sn = 1;
            for (let i = -1; i >= -globalGridData.gridsCount; i--) {
                let price = getPrice(i);
                let coinAmt = globalGridData.orderSize / price;
                html += `
                    <tr>
                        <td class="text-buy" style="font-weight: 600;">#SN ${sn}</td>
                        <td style="font-weight: 600;">$${price.toFixed(4)}</td>
                        <td style="color: var(--text-secondary);">$${sizeFormatted}</td>
                        <td style="color: var(--text-primary);">${coinAmt.toFixed(6)}</td>
                    </tr>
                `;
                sn++;
            }

            tbody.innerHTML = html;
        }

        async function createIGP() {
            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            document.getElementById('loader-overlay').style.display = 'flex';

            const totalInv = parseFloat(document.getElementById('total-investment').value) || 0;
            const useQty = parseFloat(document.getElementById('use-qty').value) || 0;
            const requiredUsdt = parseFloat(document.getElementById('req-usdt').value) || 0;

            const payload = {
                strategy_name: document.getElementById('strategy-name').value.trim(),
                coin_pair: selectedCoin + 'USDT',
                geometric_step_pct: parseFloat(document.getElementById('step-pct').value),
                lower_range_price: parseFloat(document.getElementById('lower-price').value),
                auto_compound: document.getElementById('auto-compound').checked,
                investment: {
                    target_total_investment: totalInv,
                    use_holding_qty: useQty,
                    required_new_usdt: requiredUsdt
                }
            };

            console.log("Sending payload:", payload);
            
            setTimeout(() => {
                document.getElementById('loader-overlay').style.display = 'none';
                alert("IGP Configuration Ready. Backend script 'igp-create-process.php' needs to be implemented next.");
                btn.disabled = false;
            }, 1000);
        }

        fetchBalance();
        fetchLivePrices();
        setInterval(fetchLivePrices, 5000); 
    </script>
</body>
</html>