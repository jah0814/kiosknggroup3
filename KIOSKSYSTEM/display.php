<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Now Serving - DLSP Registrar</title>
    <link rel="icon" type="image/jpeg" href="favicon.php" />
    <link rel="shortcut icon" type="image/jpeg" href="favicon.php" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        .ticker{ position:fixed; bottom:0; left:0; right:0; background:#0f172a; color:#fff; padding:8px 12px; overflow:hidden; }
        .ticker span{ display:inline-block; padding-right:40px; animation:ticker 30s linear infinite; }
        @keyframes ticker{ 0%{ transform:translateX(100%);} 100%{ transform:translateX(-100%);} }
    </style>
    <audio id="notification" src="assets/sounds/new-notification-022-370046.mp3" preload="auto"></audio>
    <script>
        // play notification sound helper
        function playNotification(){ 
            try{ 
                const audio = document.getElementById('notification');
                audio.currentTime = 0; // Reset to start
                audio.play().catch(e => console.log('Audio play failed:', e)); 
            }catch(e){
                console.log('Audio error:', e);
            } 
        }
    </script>
</head>
<body class="display">
    <div class="container">
        <header class="display-header">
            <img class="school-logo" src="assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
            <div class="titles">
                <h1>Now Serving</h1>
                <p>Registrar • DLSP</p>
            </div>
        </header>

        <section class="service-grid">
            <article class="service-card" id="win1" data-window="1">
                <div class="window-pill">Window 1</div>
                <div style="font-size:11px; color:#4b5a56; margin-bottom:8px;">Ms Jonalyn Marie M. Cuenca</div>
                <div class="serving-label">Serving:</div>
                <div class="serving-box"><div class="queue-code" data-slot="current">—</div></div>
                <div class="next-label">Next in line:</div>
                <div class="next-badges" data-slot="next"></div>
            </article>
            <article class="service-card" id="win2" data-window="2">
                <div class="window-pill">Window 2</div>
                <div style="font-size:11px; color:#4b5a56; margin-bottom:8px;">Ms Emelinda H. Cosico</div>
                <div class="serving-label">Serving:</div>
                <div class="serving-box"><div class="queue-code" data-slot="current">—</div></div>
                <div class="next-label">Next in line:</div>
                <div class="next-badges" data-slot="next"></div>
            </article>
            <article class="service-card" id="win3" data-window="3">
                <div class="window-pill">Window 3</div>
                <div style="font-size:11px; color:#4b5a56; margin-bottom:8px;">Ms Ellen E. Dejaresco</div>
                <div class="serving-label">Serving:</div>
                <div class="serving-box"><div class="queue-code" data-slot="current">—</div></div>
                <div class="next-label">Next in line:</div>
                <div class="next-badges" data-slot="next"></div>
            </article>
            <article class="service-card" id="win4" data-window="4">
                <div class="window-pill">Window 4</div>
                <div style="font-size:11px; color:#4b5a56; margin-bottom:8px;">Ms. Amalia M. Bobadilla</div>
                <div class="serving-label">Serving:</div>
                <div class="serving-box"><div class="queue-code" data-slot="current">—</div></div>
                <div class="next-label">Next in line:</div>
                <div class="next-badges" data-slot="next"></div>
            </article>
            <article class="service-card" id="win5" data-window="5">
                <div class="window-pill">Window 5</div>
                <div style="font-size:11px; color:#4b5a56; margin-bottom:8px;">Ms Ronieliza Sansebuche</div>
                <div class="serving-label">Serving:</div>
                <div class="serving-box"><div class="queue-code" data-slot="current">—</div></div>
                <div class="next-label">Next in line:</div>
                <div class="next-badges" data-slot="next"></div>
            </article>
            <article class="service-card" id="win6" data-window="6">
                <div class="window-pill">Window 6</div>
                <div style="font-size:11px; color:#4b5a56; margin-bottom:8px;">Ms Arsenia E. Lumalang & Ms Marife De Castro</div>
                <div class="serving-label">Serving:</div>
                <div class="serving-box"><div class="queue-code" data-slot="current">—</div></div>
                <div class="next-label">Next in line:</div>
                <div class="next-badges" data-slot="next"></div>
            </article>
        </section>
    </div>

    <div class="ticker"><span>Welcome to DLSP Registrar. Please prepare your ID and documents. — Office hours: 8:00 AM - 5:00 PM — Thank you.</span></div>

    <script src="assets/js/app.js"></script>
    <script>
    let lastQueueNumbers = {};

    function setWindowCurrent(windowNum, value){
        const card = document.querySelector(`[data-window="${windowNum}"]`);
        if (card) {
            const currentEl = card.querySelector('.queue-code[data-slot="current"]');
            if (currentEl) currentEl.textContent = value || '—';
        }
    }
    
    function setWindowNext(windowNum, list){
        const card = document.querySelector(`[data-window="${windowNum}"]`);
        if (card) {
            const nextEl = card.querySelector('.next-badges[data-slot="next"]');
            if (nextEl) {
                const html = (list||[]).slice(0,3).map(n => `<span class="queue-badge">${n.queue_number}</span>`).join('');
                nextEl.innerHTML = html;
            }
        }
    }

    // Poll all windows
    async function refreshAllWindows(){
        try {
            const data = await KioskApp.getJSON('includes/queue.php?action=display');
            if (data.ok && data.windows){
                for (let w = 1; w <= 6; w++) {
                    const windowData = data.windows[w];
                    if (windowData) {
                        const currentQueue = windowData.serving ? windowData.serving.queue_number : null;
                        const lastQueue = lastQueueNumbers[w];
                        
                        setWindowCurrent(w, currentQueue);
                        setWindowNext(w, windowData.next || []);
                        
                        // Play notification if queue changed (even if lastQueue null or empty)
                        if (currentQueue && currentQueue !== lastQueue) {
                            playNotification();
                        }
                        lastQueueNumbers[w] = currentQueue;
                    }
                }
            }
        } catch (error) {
            console.error('Error refreshing windows:', error);
        }
    }

    // Initial
    refreshAllWindows();
    setInterval(refreshAllWindows, 2000);
    </script>
</body>
</html>

