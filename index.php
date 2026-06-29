<?php
// bitget/index.php
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bitget Pro | Dashboard</title>
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
            --sbs-color: #8a2be2; /* New SBS Theme Color */
            --up-color: #0ecb81;
            --nav-height: 65px;
        }

        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-primary); overflow: hidden; }
        
        /* Main Container */
        .app-container { 
            height: calc(100dvh - var(--nav-height) - env(safe-area-inset-bottom)); 
            width: 100%; 
            position: relative; 
        }
        
        /* Tab Contents */
        .tab-pane { display: none; width: 100%; height: 100%; overflow-y: auto; -webkit-overflow-scrolling: touch; padding-bottom: 20px;}
        .tab-pane.active { display: block; animation: fadeIn 0.2s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* iFrames */
        iframe { width: 100%; height: 100%; border: none; display: block; }

        /* Strategies Tab Native CSS */
        .strategy-header { padding: 20px; background: var(--surface-color); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 10; }
        .strategy-header h1 { margin: 0; font-size: 1.5rem; color: var(--text-primary); }
        .strategy-header p { margin: 5px 0 0 0; font-size: 0.85rem; color: var(--text-secondary); }
        
        .strategy-content { padding: 20px; }
        .strat-card { background: var(--surface-light); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; position: relative; cursor: pointer; transition: 0.2s; overflow: hidden; margin-bottom: 15px;}
        .strat-card:active { transform: scale(0.98); }
        .strat-icon { width: 45px; height: 45px; background: rgba(252, 213, 53, 0.1); border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 1.5rem; margin-bottom: 15px; color: var(--accent-color); border: 1px solid rgba(252, 213, 53, 0.2);}
        .strat-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;}
        .strat-desc { font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4; margin-bottom: 15px; }
        .strat-btn { display: inline-block; background: var(--accent-color); color: #000; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        
        /* SBS Specific Card Styles */
        .sbs-card .strat-icon { background: rgba(138, 43, 226, 0.1); color: var(--sbs-color); border-color: rgba(138, 43, 226, 0.2); }
        .sbs-card .strat-btn { background: var(--sbs-color); color: #fff; }
        .badge-sbs { background: rgba(138, 43, 226, 0.2); color: var(--sbs-color); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; border: 1px solid var(--sbs-color);}

        /* Profile Tab CSS */
        .profile-wrap { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 20px;}
        .avatar { width: 80px; height: 80px; background: var(--surface-light); border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 2rem; border: 2px solid var(--border-color); margin-bottom: 15px; color: var(--text-secondary);}
        .profile-name { font-size: 1.3rem; font-weight: 700; margin-bottom: 5px; }
        .profile-uid { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 20px; }

        /* Bottom Navigation Menu */
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; height: calc(var(--nav-height) + env(safe-area-inset-bottom)); padding-bottom: env(safe-area-inset-bottom); background: var(--surface-color); border-top: 1px solid var(--border-color); display: flex; justify-content: space-around; align-items: center; z-index: 9999; box-sizing: border-box; }
        .nav-item { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 25%; height: var(--nav-height); cursor: pointer; color: var(--text-secondary); transition: 0.2s; }
        .nav-item.active { color: var(--accent-color); }
        .nav-icon { font-size: 1.3rem; margin-bottom: 4px; }
        .nav-text { font-size: 0.7rem; font-weight: 600; }
        
        .nav-item svg { width: 22px; height: 22px; fill: currentColor; margin-bottom: 4px; }
    </style>
</head>
<body>

    <div class="app-container">
        
        <div id="tab-market" class="tab-pane active">
            <iframe src="price/index.php"></iframe>
        </div>

        <div id="tab-strategy" class="tab-pane">
            <div class="strategy-header">
                <h1>Trading Bots</h1>
                <p>Automate your trades with advanced algorithms</p>
            </div>
            <div class="strategy-content">
                
                <div class="strat-card" onclick="window.location.href='trading/smart-portfolio-strategy/sps-bot-list.php'">
                    <div class="strat-icon">📊</div>
                    <div class="strat-title">Smart Portfolio Strategy</div>
                    <div class="strat-desc">Automated rebalancing index fund. Distributes risks and dynamically manages allocations using smart deviation thresholding.</div>
                    <div class="strat-btn">Open Dashboard</div>
                </div>

                <div class="strat-card sbs-card" onclick="window.location.href='trading/smart-buy-sell/sbs-bot-list.php'">
                    <div class="strat-icon">⚖️</div>
                    <div class="strat-title">Smart Buy Sell <span class="badge-sbs">SBS</span></div>
                    <div class="strat-desc">Perfect mean reversion algorithm. Automatically tracks value gaps to sell the highest performing asset and buy the lowest.</div>
                    <div class="strat-btn">Open Dashboard</div>
                </div>
                
                <div class="strat-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="strat-icon" style="color: var(--text-secondary); border-color: var(--border-color); background: none;">🤖</div>
                    <div class="strat-title">Grid Trading Bot</div>
                    <div class="strat-desc">Buy low and sell high automatically within a set price range. Coming soon.</div>
                    <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);">Coming Soon</div>
                </div>

            </div>
        </div>

        <div id="tab-assets" class="tab-pane">
            <iframe src="assets/spot.php"></iframe>
        </div>

        <div id="tab-profile" class="tab-pane">
            <div class="profile-wrap">
                <div class="avatar">👤</div>
                <div class="profile-name">Bitget Pro User</div>
                <div class="profile-uid">UID: 84729103</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; max-width: 80%;">Profile management and security settings will be available here.</p>
                <button style="margin-top: 20px; background: var(--surface-light); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px 20px; border-radius: 8px; cursor:pointer;">Settings</button>
            </div>
        </div>

    </div>

    <div class="bottom-nav">
        <div class="nav-item active" onclick="switchTab('market', this)">
            <svg viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>
            <span class="nav-text">Market</span>
        </div>
        
        <div class="nav-item" onclick="switchTab('strategy', this)">
            <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            <span class="nav-text">Strategies</span>
        </div>
        
        <div class="nav-item" onclick="switchTab('assets', this)">
            <svg viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
            <span class="nav-text">Assets</span>
        </div>
        
        <div class="nav-item" onclick="switchTab('profile', this)">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span class="nav-text">Profile</span>
        </div>
    </div>

    <script>
        function switchTab(tabName, element) {
            document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
            element.classList.add('active');

            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
        }
    </script>
</body>
</html>
