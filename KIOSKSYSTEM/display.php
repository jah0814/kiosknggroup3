<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Now Serving - DLSP Registrar</title>
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
        <div class="brand" style="margin-bottom:8px;">
            <img src="assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
            <div class="title">
                <h1>Now Serving</h1>
                <p>Registrar • DLSP</p>
            </div>
        </div>
        <div id="now" class="now">—</div>
        <h3>Next in Line</h3>
        <div id="next" class="next"></div>
    </div>

    <div class="ticker"><span>Welcome to DLSP Registrar. Please prepare your ID and documents. — Office hours: 8:00 AM - 5:00 PM — Thank you.</span></div>

    <script src="assets/js/app.js"></script>
    <script>
    let lastQueueNumber = '';
    
    // Function to check current queue and play notification if changed
    // This polls get_current_queue.php every 2 seconds as required
    async function checkCurrentQueue(){
        try {
            const data = await KioskApp.getJSON('get_current_queue.php');
            if (data.ok){
                const currentQueueNumber = data.queue_number || '—';
                
                // If queue number changed, update display and play sound
                if (currentQueueNumber !== lastQueueNumber && lastQueueNumber !== ''){
                    document.getElementById('now').textContent = currentQueueNumber;
                    playNotification();
                } else if (lastQueueNumber === ''){
                    // First load, just update display without sound
                    document.getElementById('now').textContent = currentQueueNumber;
                }
                
                lastQueueNumber = currentQueueNumber;
            }
        } catch (error) {
            console.error('Error checking queue:', error);
        }
    }
    
    // Refresh the "next in line" list separately (less frequent)
    async function refreshNextInLine(){
        try {
            const data = await KioskApp.getJSON('includes/queue.php?action=display');
            if (data.ok){
                document.getElementById('next').innerHTML = (data.next||[]).map(n => `<div class="tile">${n.queue_number}</div>`).join('');
            }
        } catch (error) {
            console.error('Error refreshing next in line:', error);
        }
    }
    
    // Initial load
    checkCurrentQueue();
    refreshNextInLine();
    
    // Poll for queue number changes every 2 seconds (as required)
    setInterval(checkCurrentQueue, 2000);
    
    // Refresh "next in line" list every 4 seconds (less frequent)
    setInterval(refreshNextInLine, 4000);
    </script>
</body>
</html>

