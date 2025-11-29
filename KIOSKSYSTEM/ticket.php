<?php
declare(strict_types=1);
$queue = isset($_GET['queue']) ? htmlspecialchars((string)$_GET['queue'], ENT_QUOTES, 'UTF-8') : '';
$eta   = isset($_GET['eta'])   ? htmlspecialchars((string)$_GET['eta'], ENT_QUOTES, 'UTF-8')   : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ticket - DLSP Registrar Kiosk</title>
  <link rel="icon" type="image/jpeg" href="favicon.php" />
  <link rel="shortcut icon" type="image/jpeg" href="favicon.php" />
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body class="kiosk">
  <div class="container">
    <header class="ticket-header">
      <img class="school-logo" src="assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
      <div class="title-center">
        <h1>Registrar Kiosk</h1>
        <p>Dalubhasaan ng Lungsod ng San Pablo</p>
      </div>
    </header>

    <main class="ticket-card" role="region" aria-labelledby="ticketTitle">
      <div class="ticket-heading" id="ticketTitle">Your Queue Number:</div>
      <div class="queue-box">
        <div class="queue-code" id="queueNumber"><?php echo $queue !== '' ? $queue : 'REG-000'; ?></div>
      </div>
      <div class="eta-line">Estimated time: <span id="eta"><?php echo $eta !== '' ? $eta : '0'; ?></span> minutes</div>
      <div class="ticket-actions">
        <button id="printBtn" class="btn ticket-btn">Print Ticket</button>
        <a href="display.php" target="_blank" class="btn ticket-btn ghost">View Display</a>
      </div>
    </main>
  </div>

  <script>
    document.getElementById('printBtn').addEventListener('click', function(){
      window.print();
    });
  </script>
</body>
</html>
