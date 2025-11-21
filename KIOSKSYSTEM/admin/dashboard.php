<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: /KIOSKSYSTEM/admin/login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Queue Dashboard - Registrar</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body class="admin">
    <div class="container">
        <header style="display:flex;justify-content:space-between;align-items:center;">
            <div class="brand" style="justify-content:flex-start;">
                <img src="../assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
                <div class="title">
                    <h2 style="margin:0;">Registrar Queue Dashboard</h2>
                    <div id="stats" class="muted"></div>
                </div>
            </div>
            <div>
                <a class="btn" href="../display.php" target="_blank">Open Display</a>
                <a class="btn" href="logout.php">Logout</a>
            </div>
        </header>

        <div class="row">
            <div class="card">
                <h3>Controls</h3>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button id="nextBtn" class="btn primary">Next</button>
                </div>
                <div class="card" style="margin-top:12px;">
                    <div>Now Serving</div>
                    <div id="nowServing" class="now">—</div>
                </div>
            </div>
            <div class="card list">
                <h3>Queue</h3>
                <div id="queueList"></div>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
    const listEl = document.getElementById('queueList');
    const nowEl = document.getElementById('nowServing');
    const statsEl = document.getElementById('stats');

    async function refresh(){
        const [list, display, stats] = await Promise.all([
            KioskApp.getJSON('../includes/queue.php?action=list'),
            KioskApp.getJSON('../includes/queue.php?action=display'),
            KioskApp.getJSON('../includes/queue.php?action=stats'),
        ]);
        if (display.ok){ nowEl.textContent = display.serving ? display.serving.queue_number : '—'; }
        if (list.ok){
            listEl.innerHTML = list.items.map(item => `
                <div class="queue-item">
                    <div>
                        <div style="font-weight:700">${item.queue_number}</div>
                        <div style="font-size:12px;color:#64748b">${item.name} — ${item.purpose}</div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        ${KioskApp.formatBadge(item.status)}
                        <button class="btn" onclick="hold(${item.id})">Hold</button>
                        <button class="btn" onclick="complete(${item.id})">Complete</button>
                    </div>
                </div>
            `).join('');
        }
        if (stats.ok){ statsEl.textContent = `Served today: ${stats.servedToday} • Avg time: ${stats.avgWaitMinutes} min`; }
    }

    async function post(action, body){
        const res = await fetch(`../includes/queue.php?action=${action}`, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams(body) });
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