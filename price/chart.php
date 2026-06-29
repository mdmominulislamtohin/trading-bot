<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Exchange Terminal (Live WS)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/klinecharts/dist/klinecharts.min.js"></script>
    
    <style>
        :root {
            --bg-color: #0b0e11;
            --surface-color: #181a20;
            --border-color: #2b3139;
            --text-primary: #eaecef;
            --text-secondary: #848e9c;
            --up-color: #0ecb81;
            --down-color: #f6465d;
            --accent-color: #fcd535;
        }
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        
        .header { padding: 10px 20px; background-color: var(--surface-color); display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); z-index: 20; }
        .back-btn { color: var(--text-secondary); text-decoration: none; font-size: 1.5rem; transition: 0.2s; font-weight: bold; }
        .back-btn:hover { color: var(--text-primary); }
        .coin-info { display: flex; align-items: center; gap: 12px; }
        .coin-symbol { font-size: 1.2rem; font-weight: 700; }
        .live-price { font-size: 1.15rem; font-weight: 600; transition: color 0.3s ease; }
        .change-percent { font-size: 0.85rem; font-weight: 600; padding: 3px 6px; border-radius: 4px; background: rgba(255,255,255,0.05); }
        .ws-indicator { font-size: 0.75rem; color: var(--up-color); font-weight: 600; display: flex; align-items: center; gap: 4px;}
        .ws-dot { width: 6px; height: 6px; background-color: var(--up-color); border-radius: 50%; animation: blink 1.5s infinite; }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }

        .toolbar { background: var(--bg-color); padding: 8px 20px; display: flex; gap: 15px; align-items: center; border-bottom: 1px solid #1e2329; flex-wrap: wrap; }
        .toolbar-group { display: flex; gap: 4px; border-right: 1px solid #2b3139; padding-right: 15px; }
        .toolbar-group:last-child { border-right: none; }
        .tool-btn { background: transparent; border: 1px solid transparent; color: var(--text-secondary); padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.2s; font-family: 'Inter', sans-serif; }
        .tool-btn:hover { color: var(--text-primary); }
        .tool-btn.active { background: #2b3139; color: var(--text-primary); }
        .indicator-btn { border: 1px solid #2b3139; }
        .indicator-btn.active { border-color: var(--accent-color); color: var(--accent-color); background: rgba(252, 213, 53, 0.05); }

        .chart-wrapper { flex: 1; width: 100%; position: relative; display: flex; flex-direction: column; background: var(--bg-color); }
        #kline-chart { width: 100%; height: 100%; transition: height 0.3s ease; }
        
        .loader-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: var(--bg-color); z-index: 10; transition: opacity 0.3s ease; }
        .spinner { width: 35px; height: 35px; border: 3px solid var(--border-color); border-top: 3px solid var(--accent-color); border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <a href="javascript:history.back()" class="back-btn">&#8592;</a>
        <div class="coin-info">
            <span class="coin-symbol" id="symbol-name">Loading...</span>
            <span class="live-price" id="live-price">--</span>
            <span class="change-percent" id="change-percent">--%</span>
        </div>
        <div class="ws-indicator" id="ws-indicator"><div class="ws-dot"></div> Live WS</div>
    </div>

    <!-- Timeframe & Toolbar -->
    <div class="toolbar">
        <div class="toolbar-group" id="tf-group">
            <button class="tool-btn" onclick="changeTimeframe('5m', this)">5m</button>
            <button class="tool-btn" onclick="changeTimeframe('15m', this)">15m</button>
            <button class="tool-btn" onclick="changeTimeframe('30m', this)">30m</button>
            <button class="tool-btn active" onclick="changeTimeframe('1H', this)">1H</button>
            <button class="tool-btn" onclick="changeTimeframe('4H', this)">4H</button>
            <button class="tool-btn" onclick="changeTimeframe('1D', this)">1D</button>
            <button class="tool-btn" onclick="changeTimeframe('1W', this)">1W</button>
            <button class="tool-btn" onclick="changeTimeframe('1M', this)">1M</button>
        </div>
        <div class="toolbar-group">
            <button class="tool-btn indicator-btn" id="btn-ma" onclick="toggleMainIndicator('MA')">MA</button>
            <button class="tool-btn indicator-btn" id="btn-ema" onclick="toggleMainIndicator('EMA')">EMA</button>
            <button class="tool-btn indicator-btn active" id="btn-vol" onclick="toggleSubIndicator('VOL')">VOL</button>
            <button class="tool-btn indicator-btn" id="btn-macd" onclick="toggleSubIndicator('MACD')">MACD</button>
        </div>
    </div>

    <div class="chart-wrapper">
        <div id="kline-chart"></div>
        <div id="loader" class="loader-container">
            <div class="spinner"></div>
            <div style="color: var(--text-secondary); font-size: 0.85rem;" id="loader-msg">Syncing History Data...</div>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const symbol = urlParams.get('symbol') || 'BTCUSDT';
        let currentTimeframe = '1H';
        document.getElementById('symbol-name').innerText = symbol;

        const loader = document.getElementById('loader');
        
        // --- Initialize KLineChart ---
        const chart = klinecharts.init('kline-chart');
        
        chart.setStyles({
            grid: { horizontal: { color: '#1e2329', size: 1, style: 'dashed' }, vertical: { show: false } },
            candle: {
                bar: { upColor: '#0ecb81', downColor: '#f6465d', noChangeColor: '#848e9c', upBorderColor: '#0ecb81', downBorderColor: '#f6465d', upWickColor: '#0ecb81', downWickColor: '#f6465d' },
                priceMark: { show: true, high: { show: true, color: '#848e9c' }, low: { show: true, color: '#848e9c' }, last: { show: true, upColor: '#0ecb81', downColor: '#f6465d', noChangeColor: '#848e9c', line: { show: true, style: 'dashed' }, text: { size: 12, paddingLeft: 4, paddingTop: 4, paddingRight: 4, paddingBottom: 4, color: '#0b0e11', weight: 'bold', borderRadius: 2 } } },
                tooltip: { showRule: 'always', showType: 'standard', labels: ['O: ', 'H: ', 'L: ', 'C: '], text: { color: '#848e9c', marginTop: 8 } }
            },
            xAxis: { axisLine: { color: '#2b3139' }, tickText: { color: '#848e9c' } },
            yAxis: { axisLine: { color: '#2b3139' }, tickText: { color: '#848e9c' } },
            crosshair: {
                horizontal: { line: { color: '#848e9c', style: 'dashed' }, text: { backgroundColor: '#fcd535', color: '#000', weight: 'bold', paddingLeft: 4, paddingRight: 4, paddingTop: 4, paddingBottom: 4 } },
                vertical: { line: { color: '#848e9c', style: 'dashed' }, text: { backgroundColor: '#2b3139', color: '#fff', paddingLeft: 4, paddingRight: 4, paddingTop: 4, paddingBottom: 4 } }
            }
        });

        let mainPaneId = 'candle_pane'; 
        let volPaneId = chart.createIndicator('VOL', false, { height: 100 });
        let macdPaneId = null;
        let currentMainIndicator = '';

        function toggleMainIndicator(type) {
            const btn = document.getElementById(`btn-${type.toLowerCase()}`);
            if (currentMainIndicator === type) { chart.removeIndicator(mainPaneId, type); currentMainIndicator = ''; btn.classList.remove('active'); } 
            else { if (currentMainIndicator) { chart.removeIndicator(mainPaneId, currentMainIndicator); document.getElementById(`btn-${currentMainIndicator.toLowerCase()}`).classList.remove('active'); } chart.createIndicator(type, false, { id: mainPaneId }); currentMainIndicator = type; btn.classList.add('active'); }
        }

        function toggleSubIndicator(type) {
            const btn = document.getElementById(`btn-${type.toLowerCase()}`);
            if (type === 'VOL') { if (volPaneId) { chart.removeIndicator(volPaneId); volPaneId = null; btn.classList.remove('active'); } else { volPaneId = chart.createIndicator('VOL', false, { height: 100 }); btn.classList.add('active'); } } 
            else if (type === 'MACD') { if (macdPaneId) { chart.removeIndicator(macdPaneId); macdPaneId = null; btn.classList.remove('active'); } else { macdPaneId = chart.createIndicator('MACD', false, { height: 100 }); btn.classList.add('active'); } }
        }

        // --- Live WebSocket Logic ---
        let ws;
        let pingInterval;
        let previousLastPrice = 0;

        function connectWebSocket() {
            if(ws) ws.close();
            ws = new WebSocket('wss://ws.bitget.com/v2/ws/public');

            // Map UI Timeframe to Bitget WS Channel format
            const tfMap = { '5m':'candle5m', '15m':'candle15m', '30m':'candle30m', '1H':'candle1H', '4H':'candle4H', '1D':'candle1D', '1W':'candle1W', '1M':'candle1M' };
            const candleChannel = tfMap[currentTimeframe] || 'candle1H';

            ws.onopen = () => {
                document.getElementById('ws-indicator').style.color = 'var(--up-color)';
                document.querySelector('.ws-dot').style.backgroundColor = 'var(--up-color)';
                
                // Subscribe to both Candle and Ticker channels
                ws.send(JSON.stringify({
                    op: "subscribe",
                    args: [
                        { instType: "SPOT", channel: "ticker", instId: symbol },
                        { instType: "SPOT", channel: candleChannel, instId: symbol }
                    ]
                }));

                pingInterval = setInterval(() => { if (ws.readyState === 1) ws.send("ping"); }, 20000);
            };

            ws.onmessage = (event) => {
                if (event.data === 'pong') return;
                try {
                    const msg = JSON.parse(event.data);
                    if (msg.data && msg.arg) {
                        
                        // 1. Update Header from Ticker Data
                        if(msg.arg.channel === 'ticker') {
                            const ticker = msg.data[0];
                            const lastPr = parseFloat(ticker.lastPr);
                            const open24 = parseFloat(ticker.open24h);
                            
                            const priceEl = document.getElementById('live-price');
                            priceEl.innerText = '$' + (lastPr >= 1000 ? lastPr.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : lastPr.toFixed(6));
                            
                            // Flash color
                            if(previousLastPrice !== 0 && lastPr !== previousLastPrice) {
                                priceEl.style.color = lastPr > previousLastPrice ? 'var(--up-color)' : 'var(--down-color)';
                                setTimeout(() => { priceEl.style.color = 'var(--text-primary)'; }, 300);
                            }
                            previousLastPrice = lastPr;

                            if(open24 > 0) {
                                const percentChange = ((lastPr - open24) / open24) * 100;
                                const pctEl = document.getElementById('change-percent');
                                if (percentChange >= 0) {
                                    pctEl.innerText = '+' + percentChange.toFixed(2) + '%';
                                    pctEl.style.color = '#0ecb81';
                                    pctEl.style.backgroundColor = 'rgba(14, 203, 129, 0.1)';
                                } else {
                                    pctEl.innerText = percentChange.toFixed(2) + '%';
                                    pctEl.style.color = '#f6465d';
                                    pctEl.style.backgroundColor = 'rgba(246, 70, 93, 0.1)';
                                }
                            }
                        }
                        
                        // 2. Update KLineChart Live Candle
                        else if(msg.arg.channel.startsWith('candle')) {
                            const c = msg.data[0];
                            chart.updateData({
                                timestamp: parseInt(c[0]), 
                                open: parseFloat(c[1]),
                                high: parseFloat(c[2]),
                                low: parseFloat(c[3]),
                                close: parseFloat(c[4]),
                                volume: parseFloat(c[5] || 0)
                            });
                        }
                    }
                } catch(e) {}
            };

            ws.onclose = () => {
                document.getElementById('ws-indicator').style.color = 'var(--down-color)';
                document.querySelector('.ws-dot').style.backgroundColor = 'var(--down-color)';
                clearInterval(pingInterval);
                setTimeout(connectWebSocket, 3000); 
            };
        }

        function changeTimeframe(tf, element) {
            if(currentTimeframe === tf) return;
            currentTimeframe = tf;
            
            const buttons = document.getElementById('tf-group').getElementsByTagName('button');
            for(let btn of buttons) { btn.classList.remove('active'); }
            element.classList.add('active');
            
            loader.style.display = 'flex';
            loader.style.opacity = '1';
            
            // Re-fetch historical data, then re-connect WS for new timeframe
            loadChartData();
        }

        async function loadChartData() {
            try {
                // Fetch History via REST
                const response = await fetch(`get-chart-data.php?symbol=${symbol}&tf=${currentTimeframe}&t=${Date.now()}`);
                const result = await response.json();

                if (result.status === 'success' && result.data && result.data.length > 0) {
                    const klineData = result.data.map(item => ({
                        timestamp: parseInt(item[0]), 
                        open: parseFloat(item[1]),
                        high: parseFloat(item[2]),
                        low: parseFloat(item[3]),
                        close: parseFloat(item[4]),
                        volume: parseFloat(item[5] || 0) 
                    }));

                    chart.applyNewData(klineData);
                    
                    // Init Header values
                    if(result.ticker) {
                        const lastPr = parseFloat(result.ticker.lastPr);
                        previousLastPrice = lastPr;
                        document.getElementById('live-price').innerText = '$' + (lastPr >= 1000 ? lastPr.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : lastPr.toFixed(6));
                    }

                    loader.style.opacity = '0';
                    setTimeout(() => { loader.style.display = 'none'; }, 300);
                    
                    // Start Live Connection for the selected timeframe
                    connectWebSocket();

                } else {
                    document.getElementById('loader-msg').innerText = 'No chart data available.';
                }
            } catch (err) {
                console.error(err);
                document.getElementById('loader-msg').innerText = 'Data fetch error.';
            }
        }

        window.addEventListener('resize', () => chart.resize());
        loadChartData();
    </script>
</body>
</html>
