<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Wallet Portfolio | Bitget</title>
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
            --locked-color: #f6465d;
        }

        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); min-height: 100vh; display: flex; flex-direction: column; align-items: center; }
        
        /* Added bottom padding so the bottom nav doesn't hide the last item */
        .container { width: 100%; max-width: 1000px; padding: 1.5rem; box-sizing: border-box; padding-bottom: 90px; }
        
        .total-balance-card {
            background: linear-gradient(135deg, var(--surface-light) 0%, var(--surface-color) 100%);
            border: 1px solid var(--border-color);
            padding: 2rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        .total-label { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 10px; font-weight: 500;}
        .total-amount { font-size: 2.2rem; font-weight: 700; color: var(--accent-color); }
        .total-currency { font-size: 1.1rem; color: var(--text-secondary); margin-left: 5px; font-weight: 600;}

        header { margin-bottom: 1.2rem; display: flex; justify-content: space-between; align-items: center; }
        header h2 { font-size: 1.3rem; margin: 0; }

        .status-dot { height: 8px; width: 8px; background-color: var(--up-color); border-radius: 50%; display: inline-block; animation: blink 1.5s infinite; margin-right: 5px;}
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }

        /* Mobile App Style List Layout */
        .wallet-list { width: 100%; }
        
        .list-header { display: flex; justify-content: space-between; padding: 10px 5px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;}
        .col-name { flex: 1.2; text-align: left; }
        .col-bal { flex: 1; text-align: right; }
        .col-val { flex: 1; text-align: right; }

        .asset-item-container { border-bottom: 1px solid rgba(255,255,255,0.03); }
        .asset-item-container:last-child { border-bottom: none; }

        .asset-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 5px; cursor: pointer; transition: background 0.2s; }
        .asset-row:hover { background-color: rgba(255,255,255,0.02); }
        
        .row-col-name { flex: 1.2; display: flex; align-items: center; gap: 10px; }
        .coin-icon { width: 30px; height: 30px; border-radius: 50%; background: #2b3139; padding: 2px; }
        .coin-sym { font-weight: 700; color: var(--text-primary); font-size: 1rem; }
        .toggle-icon { color: var(--text-secondary); font-size: 0.7rem; margin-left: 5px; transition: transform 0.3s; display: inline-block;}

        .row-col-bal { flex: 1; text-align: right; font-weight: 500; font-size: 0.95rem; }
        .row-col-val { flex: 1; text-align: right; font-weight: 600; font-size: 1rem; color: var(--text-primary); }

        /* Expandable Details Box */
        .details-box {
            background-color: #13151a;
            padding: 15px;
            display: none; /* Flex when active */
            justify-content: space-between;
            align-items: center;
            border-left: 3px solid var(--accent-color);
            border-radius: 0 8px 8px 0;
            margin-bottom: 10px;
        }
        .detail-item { flex: 1; }
        .detail-item:nth-child(2) { text-align: center; }
        .detail-item:last-child { text-align: right; }
        
        .detail-label { display: block; font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px; font-weight: 600;}
        .detail-val { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); }
        .val-locked { color: var(--locked-color); }

        /* FLASH EFFECT CSS */
        .val-transition { transition: color 0.5s ease, text-shadow 0.5s ease; }
        .val-flash-up { color: var(--up-color) !important; text-shadow: 0 0 12px rgba(14,203,129,0.7); }
        .val-flash-down { color: var(--locked-color) !important; text-shadow: 0 0 12px rgba(246,70,93,0.7); }

        @media (max-width: 600px) {
            .container { padding: 1rem; padding-bottom: 90px;}
            .total-amount { font-size: 1.8rem; }
            .list-header { font-size: 0.7rem; }
            .row-col-bal, .row-col-val { font-size: 0.9rem; }
            .details-box { padding: 12px; }
            .detail-val { font-size: 0.85rem; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="total-balance-card">
            <div class="total-label">Estimated Portfolio Value</div>
            <div>
                <span class="total-amount val-transition" id="grand-total">0.00</span>
                <span class="total-currency">USDT</span>
            </div>
        </div>

        <header>
            <h2>Assets List</h2>
            <div style="font-size: 0.8rem; color: var(--up-color); font-weight: 500;">
                <span class="status-dot"></span> Live Sync
            </div>
        </header>

        <div class="wallet-list">
            <div class="list-header">
                <div class="col-name">Asset</div>
                <div class="col-bal">Balance</div>
                <div class="col-val">Value (USDT)</div>
            </div>
            <div id="wallet-list-body">
                </div>
        </div>
    </div>

    <script>
        const defaultIcon = 'https://cdn-icons-png.flaticon.com/512/1213/1213322.png';
        const expandedCoins = new Set();

        const previousValues = {}; 
        const previousPrices = {}; 
        let previousGrandTotal = 0; 

        window.toggleDetails = function(coin) {
            const detailsBox = document.getElementById(`details-${coin}`);
            const toggleIcon = document.getElementById(`icon-${coin}`);
            
            if (expandedCoins.has(coin)) {
                expandedCoins.delete(coin);
                detailsBox.style.display = 'none';
                toggleIcon.style.transform = 'rotate(0deg)';
            } else {
                expandedCoins.add(coin);
                detailsBox.style.display = 'flex';
                toggleIcon.style.transform = 'rotate(180deg)';
            }
        };

        async function updatePortfolio() {
            try {
                // Cache buster যোগ করা হয়েছে
                const [walletRes, priceRes] = await Promise.all([
                    fetch('spot-api.php?t=' + Date.now()),
                    fetch('../price/price-api.php?t=' + Date.now())
                ]);

                const walletData = await walletRes.json();
                const marketData = await priceRes.json();

                if (walletData.status === 'success' && marketData.status === 'success') {
                    const rawAssets = walletData.data;
                    const tickers = marketData.data;
                    const listBody = document.getElementById('wallet-list-body');
                    
                    let totalPortfolioValue = 0;

                    let processedAssets = rawAssets.map(asset => {
                        const coin = asset.coin;
                        const available = parseFloat(asset.available);
                        const frozen = parseFloat(asset.frozen);
                        const totalAmount = available + frozen;
                        
                        let currentPrice = 0;
                        if (coin === 'USDT') {
                            currentPrice = 1;
                        } else {
                            const ticker = tickers.find(t => t.symbol === coin + 'USDT');
                            currentPrice = ticker ? parseFloat(ticker.lastPr) : 0;
                        }

                        const usdValue = totalAmount * currentPrice;

                        return {
                            ...asset,
                            available: available,
                            frozen: frozen,
                            totalAmount: totalAmount,
                            currentPrice: currentPrice,
                            usdValue: usdValue
                        };
                    });

                    processedAssets.sort((a, b) => {
                        if (a.coin === 'USDT') return -1;
                        if (b.coin === 'USDT') return 1;
                        return b.usdValue - a.usdValue;
                    });

                    let htmlContent = '';
                    processedAssets.forEach(asset => {
                        totalPortfolioValue += asset.usdValue;
                        
                        let valueFlashClass = '';
                        if (previousValues[asset.coin] !== undefined) {
                            if (asset.usdValue > previousValues[asset.coin]) valueFlashClass = 'val-flash-up';
                            else if (asset.usdValue < previousValues[asset.coin]) valueFlashClass = 'val-flash-down';
                        }
                        previousValues[asset.coin] = asset.usdValue; 

                        let priceFlashClass = '';
                        if (previousPrices[asset.coin] !== undefined) {
                            if (asset.currentPrice > previousPrices[asset.coin]) priceFlashClass = 'val-flash-up';
                            else if (asset.currentPrice < previousPrices[asset.coin]) priceFlashClass = 'val-flash-down';
                        }
                        previousPrices[asset.coin] = asset.currentPrice;

                        const isExpanded = expandedCoins.has(asset.coin);
                        const displayStyle = isExpanded ? 'flex' : 'none';
                        const rotationStyle = isExpanded ? 'rotate(180deg)' : 'rotate(0deg)';

                        htmlContent += `
                            <div class="asset-item-container">
                                <div class="asset-row" onclick="toggleDetails('${asset.coin}')">
                                    <div class="row-col-name">
                                        <img src="${asset.icon}" onerror="this.src='${defaultIcon}'" class="coin-icon">
                                        <span class="coin-sym">${asset.coin} <span class="toggle-icon" id="icon-${asset.coin}" style="transform: ${rotationStyle};">&#9662;</span></span>
                                    </div>
                                    <div class="row-col-bal">
                                        ${asset.totalAmount.toLocaleString(undefined, {maximumFractionDigits: 6})}
                                    </div>
                                    <div class="row-col-val val-transition ${valueFlashClass}">
                                        $${asset.usdValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                    </div>
                                </div>
                                
                                <div id="details-${asset.coin}" class="details-box" style="display: ${displayStyle};">
                                    <div class="detail-item">
                                        <span class="detail-label">Price</span>
                                        <span class="detail-val val-transition ${priceFlashClass}" style="color: var(--up-color);">$${asset.currentPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 6})}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Available</span>
                                        <span class="detail-val">${asset.available.toLocaleString(undefined, {maximumFractionDigits: 6})}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Locked</span>
                                        <span class="detail-val val-locked">${asset.frozen.toLocaleString(undefined, {maximumFractionDigits: 6})}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    listBody.innerHTML = htmlContent;

                    const grandTotalEl = document.getElementById('grand-total');
                    if (previousGrandTotal > 0) {
                        if (totalPortfolioValue > previousGrandTotal) {
                            grandTotalEl.classList.add('val-flash-up');
                        } else if (totalPortfolioValue < previousGrandTotal) {
                            grandTotalEl.classList.add('val-flash-down');
                        }
                    }
                    grandTotalEl.innerText = totalPortfolioValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    previousGrandTotal = totalPortfolioValue;

                    setTimeout(() => {
                        document.querySelectorAll('.val-flash-up, .val-flash-down').forEach(el => {
                            el.classList.remove('val-flash-up', 'val-flash-down');
                        });
                    }, 1000);

                }
            } catch (err) {
                console.error("Update error:", err);
            }
        }

        updatePortfolio();
        setInterval(updatePortfolio, 5000);
    </script>
</body>
</html>
