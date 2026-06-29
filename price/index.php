<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Crypto Market List</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0b0e11;
            --surface-color: #181a20;
            --border-color: #2b3139;
            --text-primary: #eaecef;
            --text-secondary: #848e9c;
            --up-color: #0ecb81;
            --down-color: #f6465d;
        }

        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); min-height: 100vh; }
        .container { width: 100%; max-width: 1200px; padding: 1rem; box-sizing: border-box; margin: 0 auto; padding-bottom: 80px;} 
        
        header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        header h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
        .status-bar { font-size: 0.8rem; color: var(--up-color); display: flex; align-items: center; gap: 6px; background: rgba(14, 203, 129, 0.1); padding: 6px 12px; border-radius: 20px; font-weight: 500;}
        .live-dot { height: 8px; width: 8px; background-color: var(--up-color); border-radius: 50%; display: inline-block; animation: blink 1.5s infinite; }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }

        .market-list { width: 100%; }
        .list-header { display: flex; justify-content: space-between; padding: 10px 5px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-size: 0.75rem; font-weight: 500; text-transform: uppercase;}
        .col-name { flex: 1.2; text-align: left; }
        .col-price { flex: 1; text-align: right; padding-right: 15px; }
        .col-change { width: 80px; text-align: right; cursor: pointer; user-select: none; transition: 0.2s;}
        .col-change:hover { color: var(--text-primary); }
        .sort-icon { font-size: 0.8rem; margin-left: 2px; }

        .asset-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 5px; border-bottom: 1px solid rgba(255,255,255,0.03); cursor: pointer; transition: background 0.2s; }
        .asset-row:hover { background-color: rgba(255,255,255,0.02); }
        
        .row-col-name { flex: 1.2; display: flex; align-items: center; gap: 12px; }
        .coin-icon { width: 30px; height: 30px; border-radius: 50%; background: #2b3139; padding: 2px; }
        .coin-title { display: flex; align-items: baseline; gap: 3px; }
        .coin-symbol { font-weight: 700; color: var(--text-primary); font-size: 1rem; }
        .quote-symbol { color: var(--text-secondary); font-size: 0.75rem; font-weight: 500; }
        .coin-high-low { font-size: 0.7rem; color: var(--text-secondary); margin-top: 4px; font-weight: 500;}

        .row-col-price { flex: 1; text-align: right; padding-right: 15px; }
        .price-val { font-size: 1.05rem; font-weight: 600; color: var(--text-primary); }
        .price-transition { transition: color 0.3s ease, text-shadow 0.3s ease; }
        
        .row-col-change { width: 80px; display: flex; justify-content: flex-end; }
        .change-badge { width: 100%; padding: 8px 0; text-align: center; border-radius: 6px; font-weight: 600; font-size: 0.85rem; color: #fff; transition: background-color 0.3s;}
        
        .bg-up { background-color: var(--up-color); }
        .bg-down { background-color: var(--down-color); }
        .bg-neutral { background-color: var(--border-color); color: var(--text-primary); }

        .flash-up { color: var(--up-color) !important; text-shadow: 0 0 8px rgba(14,203,129,0.4); }
        .flash-down { color: var(--down-color) !important; text-shadow: 0 0 8px rgba(246,70,93,0.4); }

        @media (max-width: 600px) {
            .container { padding: 10px; }
            .list-header { font-size: 0.7rem; }
            .price-val { font-size: 0.95rem; }
            .change-badge { font-size: 0.8rem; padding: 7px 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Markets</h1>
            <div class="status-bar" id="ws-status">
                <span class="live-dot" id="ws-dot"></span> <span id="ws-text">Connecting...</span>
            </div>
        </header>

        <div class="market-list">
            <div class="list-header">
                <div class="col-name">Trading Pair</div>
                <div class="col-price">Last Price</div>
                <div class="col-change" onclick="toggleSort()" title="Click to sort">
                    24h Chg <span id="sort-icon" class="sort-icon">↕</span>
                </div>
            </div>

            <div id="crypto-list"></div>
        </div>
    </div>

    <script>
        const targetCoins = ['BTC', 'ETH', 'LTC', 'XRP', 'SOL', 'ADA', 'LINK', 'AVAX', 'DOGE', 'BNB', 'SUI', 'PAXG', 'SLVON'];
        const previousPrices = {};
        let sortDirection = 'none'; 
        const coinPercentages = {}; 
        
        function getIconUrl(coin) {
            return `https://assets.coincap.io/assets/icons/${coin.toLowerCase()}@2x.png`;
        }

        function initList() {
            const listBody = document.getElementById('crypto-list');
            targetCoins.forEach(coin => {
                const div = document.createElement('div');
                div.className = 'asset-row';
                div.id = `row-${coin}`;
                div.onclick = function() { window.location.href = `chart.php?symbol=${coin}USDT`; };

                div.innerHTML = `
                    <div class="row-col-name">
                        <img src="${getIconUrl(coin)}" id="icon-${coin}" onerror="this.src='https://cdn-icons-png.flaticon.com/512/1213/1213322.png'" class="coin-icon" alt="${coin}">
                        <div>
                            <div class="coin-title"><span class="coin-symbol">${coin}</span><span class="quote-symbol">/USDT</span></div>
                            <div class="coin-high-low">H: <span id="high-${coin}">--</span> &nbsp; L: <span id="low-${coin}">--</span></div>
                        </div>
                    </div>
                    <div class="row-col-price"><div id="price-${coin}" class="price-val price-transition">Loading...</div></div>
                    <div class="row-col-change"><div id="change-${coin}" class="change-badge bg-neutral">--%</div></div>
                `;
                listBody.appendChild(div);
            });
        }

        function formatPrice(price) {
            let val = parseFloat(price);
            if (val >= 1000) return val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (val >= 1) return val.toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4});
            return val.toFixed(6);
        }

        function toggleSort() {
            const icon = document.getElementById('sort-icon');
            if (sortDirection === 'none') { sortDirection = 'desc'; icon.innerText = '↓'; icon.style.color = 'var(--up-color)'; } 
            else if (sortDirection === 'desc') { sortDirection = 'asc'; icon.innerText = '↑'; icon.style.color = 'var(--down-color)'; } 
            else { sortDirection = 'none'; icon.innerText = '↕'; icon.style.color = 'inherit'; }
            sortTableDOM();
        }

        function sortTableDOM() {
            const listBody = document.getElementById('crypto-list');
            let sortedArray = [...targetCoins];
            if (sortDirection === 'desc') { sortedArray.sort((a, b) => (coinPercentages[b] || -999) - (coinPercentages[a] || -999)); } 
            else if (sortDirection === 'asc') { sortedArray.sort((a, b) => (coinPercentages[a] || 999) - (coinPercentages[b] || 999)); }
            sortedArray.forEach(coin => {
                const row = document.getElementById(`row-${coin}`);
                if (row) listBody.appendChild(row);
            });
        }

        // --- Bitget Web Socket Connection ---
        let ws;
        let pingInterval;

        function connectWebSocket() {
            ws = new WebSocket('wss://ws.bitget.com/v2/ws/public');

            ws.onopen = () => {
                document.getElementById('ws-text').innerText = 'Live WebSocket';
                document.getElementById('ws-dot').style.backgroundColor = 'var(--up-color)';
                
                // Subscribe to Ticker Channel for all target coins
                const args = targetCoins.map(coin => ({
                    instType: "SPOT",
                    channel: "ticker",
                    instId: coin + "USDT"
                }));
                ws.send(JSON.stringify({ op: "subscribe", args: args }));

                // Ping every 20s to keep connection alive
                pingInterval = setInterval(() => {
                    if (ws.readyState === 1) ws.send("ping");
                }, 20000);
            };

            ws.onmessage = (event) => {
                if (event.data === 'pong') return;
                
                try {
                    const msg = JSON.parse(event.data);
                    if (msg.data && (msg.action === 'snapshot' || msg.action === 'update')) {
                        msg.data.forEach(tickerData => {
                            const symbol = tickerData.instId;
                            const coin = symbol.replace('USDT', '');
                            
                            if(targetCoins.includes(coin)) {
                                updateDOMWithTicker(coin, tickerData);
                            }
                        });
                        if(sortDirection !== 'none') sortTableDOM();
                    }
                } catch(e) {}
            };

            ws.onclose = () => {
                document.getElementById('ws-text').innerText = 'Reconnecting...';
                document.getElementById('ws-dot').style.backgroundColor = 'var(--down-color)';
                clearInterval(pingInterval);
                setTimeout(connectWebSocket, 3000); // Reconnect on close
            };
        }

        function updateDOMWithTicker(coin, tickerData) {
            const priceElement = document.getElementById(`price-${coin}`);
            const changeElement = document.getElementById(`change-${coin}`);
            const highElement = document.getElementById(`high-${coin}`);
            const lowElement = document.getElementById(`low-${coin}`);

            const newPrice = parseFloat(tickerData.lastPr);
            const openPrice = parseFloat(tickerData.open24h);
            
            // Flash Effect
            if (previousPrices[coin]) {
                if (newPrice > previousPrices[coin]) {
                    priceElement.classList.add('flash-up');
                    setTimeout(() => priceElement.classList.remove('flash-up'), 500);
                } else if (newPrice < previousPrices[coin]) {
                    priceElement.classList.add('flash-down');
                    setTimeout(() => priceElement.classList.remove('flash-down'), 500);
                }
            }
            priceElement.innerText = '$' + formatPrice(newPrice);
            priceElement.style.color = 'var(--text-primary)';
            previousPrices[coin] = newPrice;

            // Update Percentage
            if (openPrice > 0) {
                const percent = ((newPrice - openPrice) / openPrice) * 100;
                coinPercentages[coin] = percent; 
                let changeText = percent.toFixed(2) + "%";
                
                if (percent > 0) {
                    changeElement.innerText = "+" + changeText;
                    changeElement.className = "change-badge bg-up";
                } else if (percent < 0) {
                    changeElement.innerText = changeText;
                    changeElement.className = "change-badge bg-down";
                } else {
                    changeElement.innerText = "0.00%";
                    changeElement.className = "change-badge bg-neutral";
                }
            }

            if(tickerData.high24h) highElement.innerText = formatPrice(tickerData.high24h);
            if(tickerData.low24h) lowElement.innerText = formatPrice(tickerData.low24h);
        }

        initList();
        
        // Initial Fetch for quick load, then connect WebSocket for real-time
        fetch('price-api.php').then(r => r.json()).then(res => {
            if(res.status === 'success' && res.data) {
                res.data.forEach(t => updateDOMWithTicker(t.symbol.replace('USDT', ''), t));
            }
            connectWebSocket(); // Start Live Connection
        }).catch(() => connectWebSocket());

    </script>
</body>
</html>
