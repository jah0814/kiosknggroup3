<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
auth_require();
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Queue Dashboard - Registrar (<?php echo htmlspecialchars($user['window_label'] ?: $user['username'], ENT_QUOTES, 'UTF-8'); ?>)</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        body.admin {
            background: url('assets/images/university-entrance.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
        }
    </style>
</head>
<body class="admin">
    <div class="container">
        <header style="display:flex;justify-content:space-between;align-items:center;">
            <div class="brand" style="justify-content:flex-start;">
                <img src="assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
                <div class="title">
                    <h2 style="margin:0;">Registrar Queue Dashboard</h2>
                    <div class="muted">Signed in as <?php echo htmlspecialchars($user['window_label'] ?: $user['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div id="stats" class="muted"></div>
                </div>
            </div>
            <div>
                <a class="btn" href="display.php" target="_blank">Open Display</a>
                <a class="btn" href="logout.php">Logout</a>
            </div>
        </header>

        <div class="row">
            <div class="card" style="background:#f3e6c9;">
                <h3>Controls</h3>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button id="nextBtn" class="btn primary">Next</button>
                </div>
                <div class="card" style="margin-top:12px; background:#80928B;">
                    <div>Now Serving</div>
                    <div id="nowServing" class="now">—</div>
                </div>
            </div>
            <div class="card list" style="background:#f3e6c9;">
                <h3>Queue</h3>
                <div id="queueList"></div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
    const listEl = document.getElementById('queueList');
    const nowEl = document.getElementById('nowServing');
    const statsEl = document.getElementById('stats');

    // Extract window number from username (window1, window2, etc.)
    const windowMatch = '<?php echo $user["username"]; ?>'.match(/window(\d+)/i);
    const windowNumber = windowMatch ? parseInt(windowMatch[1]) : 1;

    async function refresh(){
        const [list, display, stats] = await Promise.all([
            KioskApp.getJSON(`includes/queue.php?action=list&window=${windowNumber}`),
            KioskApp.getJSON(`includes/queue.php?action=display&window=${windowNumber}`),
            KioskApp.getJSON('includes/queue.php?action=stats'),
        ]);
        if (display.ok){ nowEl.textContent = display.serving ? display.serving.queue_number : '—'; }
        if (list.ok){
            listEl.innerHTML = list.items.map(item => `
                <div class="queue-item">
                    <div>
                        <div style="font-weight:700">${item.queue_number}</div>
                        <div style="font-size:12px;color:#64748b">${item.name} — ${item.department || ''} — ${item.purpose}</div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        ${KioskApp.formatBadge(item.status)}
                        <button class="btn hold-btn" onclick="hold(${item.id})">Hold</button>
                        <button class="btn complete-btn" onclick="complete(${item.id})">Complete</button>
                    </div>
                </div>
            `).join('');
        }
        if (stats.ok){ statsEl.textContent = `Served today: ${stats.servedToday} • Avg time: ${stats.avgWaitMinutes} min`; }
    }

    async function post(action, body){
        body.window = windowNumber;
        const res = await fetch(`includes/queue.php?action=${action}`, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams(body) });
        return await res.json();
    }

    async function hold(id){ await post('hold', { id }); refresh(); }
    async function complete(id){ await post('complete', { id }); refresh(); }

    document.getElementById('nextBtn').addEventListener('click', async () => { await post('next', {}); refresh(); });
    refresh();
    setInterval(refresh, 5000);
    </script>
</body>
</html>
