<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create SPS Bot | Bitget</title>
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
            --info-color: #3d79f2;
        }

        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding-bottom: 90px;}
        .container { width: 100%; max-width: 600px; padding: 1rem; box-sizing: border-box; }
        
        header { width: 100%; text-align: center; padding: 15px 0; border-bottom: 1px solid var(--border-color); background-color: var(--bg-color); position: sticky; top: 0; z-index: 50;}
        header h1 { font-size: 1.3rem; margin: 0; color: var(--text-primary); }

        .form-group { margin-top: 1.5rem; background: var(--surface-color); padding: 1.2rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .form-label { display: block; font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 10px; font-weight: 500;}
        
        .form-input { width: 100%; box-sizing: border-box; background: var(--surface-light); border: 1px solid var(--border-color); padding: 14px; border-radius: 8px; color: var(--text-primary); font-size: 1rem; font-family: inherit;}
        .form-input:focus { outline: none; border-color: var(--accent-color); }

        .coins-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .coins-title { font-size: 1.05rem; font-weight: 600; color: var(--text-primary); }
        .allocate-btn { color: var(--info-color); font-size: 0.85rem; cursor: pointer; font-weight: 500; background: none; border: none; padding: 0; }
        
        .asset-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .asset-row { display: flex; justify-content: space-between; align-items: center; background: #13151a; padding: 10px 15px; border-radius: 8px; border: 1px solid var(--border-color); }
        .asset-name { font-weight: 600; font-size: 1rem; display: flex; align-items: center; gap: 10px;}
        .asset-name span.dot { height: 8px; width: 8px; background: var(--accent-color); border-radius: 50%; display: inline-block;}
        
        .input-wrapper { display: flex; align-items: center; gap: 5px; background: var(--surface-light); border-radius: 6px; padding: 6px 10px; border: 1px solid transparent;}
        .input-wrapper:focus-within { border-color: var(--accent-color); }
        .pct-input { background: none; border: none; color: #fff; font-size: 0.95rem; text-align: right; width: 45px; font-weight: 600; padding: 0;}
        .pct-input:focus { outline: none; }
        .pct-symbol { color: var(--text-secondary); font-size: 0.9rem;}
        .delete-btn { color: var(--down-color); font-size: 1.2rem; cursor: pointer; margin-left: 10px; opacity: 0.8;}
        
        .btn-add-coin { width: 100%; background: rgba(255,255,255,0.05); border: 1px dashed var(--text-secondary); color: var(--text-primary); padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: 0.2s;}
        .btn-add-coin:hover { border-color: var(--accent-color); color: var(--accent-color); }

        .warning-text { font-size: 0.8rem; color: var(--down-color); margin-top: 10px; display: none;}
        .info-text { font-size: 0.8rem; color: var(--info-color); margin-top: 10px; display: none;}

        .rebalance-flex { display: flex; gap: 10px; align-items: center; }
        .reb-input-wrap { flex: 1; position: relative; }
        .reb-unit { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
        .quick-pct-btn { background: var(--surface-light); border: 1px solid var(--border-color); color: var(--text-primary); padding: 13px 15px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: 0.2s;}
        .quick-pct-btn:hover, .quick-pct-btn.active { background: var(--accent-color); color: #000; border-color: var(--accent-color);}

        .investment-wrapper { position: relative; }
        .investment-unit { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--text-primary); font-weight: 600;}
        .balance-info { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-secondary); margin-top: 8px;}
        .balance-val { color: var(--text-primary); font-weight: 600;}

        .footer-action { position: fixed; bottom: 0; left: 0; width: 100%; background: var(--surface-color); border-top: 1px solid var(--border-color); display: flex; justify-content: center; padding: 15px 0; z-index: 100;}
        .btn-create { width: 100%; max-width: 570px; background: var(--up-color); color: #000; border: none; padding: 16px; border-radius: 8px; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: 0.2s;}
        .btn-create:disabled { background: #2b3139; color: var(--text-secondary); cursor: not-allowed; }

        /* Modal Layout */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: none; justify-content: center; align-items: flex-end; z-index: 1000;}
        .modal-content { background: var(--surface-color); width: 100%; max-width: 600px; height: 80vh; border-radius: 20px 20px 0 0; display: flex; flex-direction: column; animation: slideUp 0.3s ease-out;}
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
        .btn-confirm { width: 100%; background: var(--accent-color); color: #000; padding: 14px; border: none; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer;}

        @media (max-width: 600px) {
            .btn-create { width: calc(100% - 30px); }
            .quick-pct-btn { padding: 13px 10px; font-size: 0.85rem;}
        }
    </style>
</head>
<body>

    <header>
        <h1>Smart Portfolio Strategy</h1>
    </header>

    <div class="container">
        
        <div class="form-group" style="margin-top: 1rem;">
            <label class="form-label">Strategy Name</label>
            <input type="text" class="form-input" id="strategy-name" placeholder="e.g. Next Bull Run 2026">
        </div>

        <div class="form-group">
            <div class="coins-header">
                <span class="coins-title">Coins</span>
                <button class="allocate-btn" onclick="allocateEqually()">Allocate Equally</button>
            </div>
            
            <div class="asset-list" id="selected-coins-list"></div>
            
            <button class="btn-add-coin" onclick="openCoinModal()">+ Add Coin</button>
            
            <div class="warning-text" id="min-coin-warning" style="display: block;">
                Please select at least 2 coins to create a portfolio.
            </div>

            <div class="warning-text" id="allocation-warning">
                Total allocation must equal 100%. Current: <span id="current-total">0</span>%
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Rebalancing Mode (By Percentage)</label>
            <div class="rebalance-flex">
                <div class="reb-input-wrap">
                    <input type="number" class="form-input" id="rebalance-pct" placeholder="Custom" step="0.1" min="0.1">
                    <span class="reb-unit">%</span>
                </div>
                <button class="quick-pct-btn" onclick="setRebalance(0.5, this)">0.5%</button>
                <button class="quick-pct-btn" onclick="setRebalance(1.0, this)">1%</button>
                <button class="quick-pct-btn" onclick="setRebalance(2.0, this)">2%</button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Investment Amount</label>
            <div class="investment-wrapper">
                <input type="number" class="form-input" id="invest-amount" placeholder="Enter amount">
                <span class="investment-unit">USDT</span>
            </div>
            <div class="info-text" id="min-invest-info" style="display: block; color: var(--accent-color);">
                Required Minimum: <span id="calculated-min">0.00</span> USDT
            </div>
            <div class="warning-text" id="invest-warning">Investment is below minimum requirement!</div>
            
            <div class="balance-info">
                <span>Available Balance</span>
                <span class="balance-val" id="avail-balance">Loading...</span>
            </div>
        </div>

    </div>

    <div class="footer-action">
        <button class="btn-create" id="btn-submit" onclick="createSPS()" disabled>Create Strategy</button>
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
        
        const SPOT_API_URL = '../../assets/spot-api.php';
        const PRICE_API_URL = '../../price/price-api.php';

        async function fetchBalance() {
            try {
                const res = await fetch(SPOT_API_URL + '?t=' + Date.now());
                const data = await res.json();
                
                if (data.status === 'success' && data.data) {
                    const usdt = data.data.find(a => a.coin === 'USDT');
                    const avail = usdt ? parseFloat(usdt.available).toFixed(2) : '0.00';
                    document.getElementById('avail-balance').innerText = `${avail} USDT`;
                } else {
                    document.getElementById('avail-balance').innerText = `API Error`;
                }
            } catch (e) {
                document.getElementById('avail-balance').innerText = `Connection Failed`;
            }
        }

        async function fetchLivePrices() {
            try {
                const res = await fetch(PRICE_API_URL + '?t=' + Date.now());
                const textResponse = await res.text();
                
                let data;
                try {
                    data = JSON.parse(textResponse);
                } catch(err) {
                    document.getElementById('modal-loading-text').innerText = "Data parse error. Check PHP API.";
                    return;
                }

                if (data.status === 'success' && data.data) {
                    liveTickersData = data.data.filter(t => t.symbol.endsWith('USDT'));
                    
                    const loadingText = document.getElementById('modal-loading-text');
                    if (loadingText) {
                        renderModalCoins(liveTickersData);
                    } else if (document.getElementById('coin-modal').style.display === 'flex') {
                        updatePricesInModalUI();
                    }
                } else {
                    document.getElementById('modal-loading-text').innerText = "API Error. Could not fetch coins.";
                }
            } catch (e) {
                const loadingText = document.getElementById('modal-loading-text');
                if(loadingText) loadingText.innerText = "Network Error. Please check API path.";
            }
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

        function openCoinModal() {
            tempModalSelections = new Set(selectedCoins);
            document.getElementById('coin-modal').style.display = 'flex';
            document.getElementById('search-coin').value = '';
            if (liveTickersData.length > 0) {
                renderModalCoins(liveTickersData);
            }
        }

        function closeCoinModal() {
            document.getElementById('coin-modal').style.display = 'none';
        }

        function filterCoins() {
            const query = document.getElementById('search-coin').value.toUpperCase();
            const filtered = liveTickersData.filter(t => t.symbol.replace('USDT','').includes(query));
            renderModalCoins(filtered);
        }

        function renderModalCoins(tickersArray) {
            const listObj = document.getElementById('modal-coin-list');
            listObj.innerHTML = '';
            
            if (tickersArray.length === 0) {
                listObj.innerHTML = `<div style="text-align: center; color: var(--text-secondary); margin-top: 20px;">No coins found</div>`;
                return;
            }

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
            if (checkbox.checked) {
                tempModalSelections.add(checkbox.value);
            } else {
                tempModalSelections.delete(checkbox.value);
            }
        }

        function confirmCoinSelection() {
            selectedCoins = Array.from(tempModalSelections);
            closeCoinModal();
            renderSelectedCoins();
            validateForm();
        }

        function removeCoin(coin) {
            selectedCoins = selectedCoins.filter(c => c !== coin);
            renderSelectedCoins();
            validateForm();
        }

        function renderSelectedCoins() {
            const listObj = document.getElementById('selected-coins-list');
            listObj.innerHTML = '';
            
            selectedCoins.forEach(coin => {
                const div = document.createElement('div');
                div.className = 'asset-row';
                div.innerHTML = `
                    <div class="asset-name"><span class="dot"></span>${coin}</div>
                    <div style="display:flex; align-items:center;">
                        <div class="input-wrapper">
                            <input type="number" class="pct-input coin-alloc" data-coin="${coin}" placeholder="0" min="1" max="99" oninput="validateForm()">
                            <span class="pct-symbol">%</span>
                        </div>
                        <span class="delete-btn" onclick="removeCoin('${coin}')">&#215;</span>
                    </div>
                `;
                listObj.appendChild(div);
            });
        }

        function allocateEqually() {
            if (selectedCoins.length === 0) return;
            const equal = Math.floor(100 / selectedCoins.length);
            const remainder = 100 % selectedCoins.length;
            
            const inputs = document.querySelectorAll('.coin-alloc');
            inputs.forEach((input, index) => {
                input.value = (index < remainder) ? equal + 1 : equal;
            });
            validateForm();
        }

        function setRebalance(val, btnElement) {
            document.getElementById('rebalance-pct').value = val;
            document.querySelectorAll('.quick-pct-btn').forEach(b => b.classList.remove('active'));
            btnElement.classList.add('active');
            validateForm();
        }

        document.getElementById('rebalance-pct').addEventListener('input', function() {
            document.querySelectorAll('.quick-pct-btn').forEach(b => b.classList.remove('active'));
            validateForm();
        });
        
        document.getElementById('strategy-name').addEventListener('input', validateForm);
        document.getElementById('invest-amount').addEventListener('input', validateForm);

        function calculateMinInvestment(allocationsArray, rebPct) {
            const MIN_ORDER_VALUE = 1.00;
            let minInvestReq = 0;

            if (rebPct > 0) {
                const reqByReb = MIN_ORDER_VALUE / (rebPct / 100);
                minInvestReq = Math.max(minInvestReq, reqByReb);
            }

            if (allocationsArray.length > 0) {
                const validAllocs = allocationsArray.filter(a => a > 0);
                if (validAllocs.length > 0) {
                    const minAlloc = Math.min(...validAllocs);
                    const reqByAlloc = MIN_ORDER_VALUE / (minAlloc / 100);
                    minInvestReq = Math.max(minInvestReq, reqByAlloc);
                }
            }
            return minInvestReq;
        }

        function validateForm() {
            const name = document.getElementById('strategy-name').value.trim();
            const rebPct = parseFloat(document.getElementById('rebalance-pct').value) || 0;
            const investInput = parseFloat(document.getElementById('invest-amount').value) || 0;
            
            let allocations = [];
            let totalAlloc = 0;
            document.querySelectorAll('.coin-alloc').forEach(inp => {
                const val = parseInt(inp.value) || 0;
                allocations.push(val);
                totalAlloc += val;
            });

            document.getElementById('current-total').innerText = totalAlloc;
            
            const minCoinWarning = document.getElementById('min-coin-warning');
            const allocWarning = document.getElementById('allocation-warning');

            // Minimum 2 coins logic check
            if (selectedCoins.length < 2) {
                minCoinWarning.style.display = 'block';
                allocWarning.style.display = 'none'; // Hide % warning if coin requirement isn't met
            } else {
                minCoinWarning.style.display = 'none';
                allocWarning.style.display = (totalAlloc !== 100) ? 'block' : 'none';
            }

            const requiredMinInvest = calculateMinInvestment(allocations, rebPct);
            document.getElementById('calculated-min').innerText = requiredMinInvest.toFixed(2);
            document.getElementById('invest-amount').placeholder = `Min. ${requiredMinInvest.toFixed(2)}`;

            const investWarningEl = document.getElementById('invest-warning');
            if (investInput > 0 && investInput < requiredMinInvest) {
                investWarningEl.style.display = 'block';
            } else {
                investWarningEl.style.display = 'none';
            }

            // Create button disabled logic (must be >= 2 coins)
            const isValid = name !== "" && 
                            selectedCoins.length >= 2 && 
                            totalAlloc === 100 && 
                            rebPct > 0 && 
                            investInput >= requiredMinInvest;

            document.getElementById('btn-submit').disabled = !isValid;
        }
        async function createSPS() {
            const btn = document.getElementById('btn-submit');
            const name = document.getElementById('strategy-name').value;
            const rebPct = document.getElementById('rebalance-pct').value;
            const invest = document.getElementById('invest-amount').value;
            
            let composition = [];
            document.querySelectorAll('.coin-alloc').forEach(inp => {
                composition.push({
                    coin: inp.getAttribute('data-coin'),
                    percentage: parseInt(inp.value)
                });
            });

            const payload = {
                bot_name: name,
                initial_investment: parseFloat(invest),
                rebalancing_percentage: parseFloat(rebPct),
                composition: composition
            };

            if (!confirm(`Are you sure you want to invest ${invest} USDT to create "${name}"?\nMarket Buy orders will be executed immediately.`)) {
                return;
            }

            // বাটন ডিজেবল ও লোডিং স্টেট
            btn.disabled = true;
            btn.innerText = "Executing Orders... Please wait";
            btn.style.backgroundColor = "var(--text-secondary)";

            try {
                const response = await fetch('sps-create-process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert(result.message);
                    // সফল হলে বট লিস্ট পেজে রিডাইরেক্ট করা
                    window.location.href = 'sps-bot-list.php';
                } else if (result.status === 'warning') {
                    console.error("Partial errors:", result.errors);
                    alert(result.message + "\nCheck console for details.");
                    window.location.href = 'sps-bot-list.php';
                } else {
                    console.error("Execution errors:", result.errors);
                    alert("Failed to create Strategy:\n" + (result.errors ? result.errors.join('\n') : result.message));
                    
                    // ফেইল করলে বাটন আবার আগের মতো করে দেওয়া
                    btn.disabled = false;
                    btn.innerText = "Create Strategy";
                    btn.style.backgroundColor = "var(--up-color)";
                }
            } catch (error) {
                alert("Network error occurred while connecting to the processor.");
                console.error(error);
                btn.disabled = false;
                btn.innerText = "Create Strategy";
                btn.style.backgroundColor = "var(--up-color)";
            }
        }

        

        // Initialize Call
        fetchBalance();
        fetchLivePrices();
        setInterval(fetchLivePrices, 5000); 
    </script>
</body>
</html>