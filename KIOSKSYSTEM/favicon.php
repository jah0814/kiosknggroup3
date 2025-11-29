<?php
// favicon.php - Serve the school logo as favicon
header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=31536000');
$logoPath = __DIR__ . '/assets/images/441281302_977585947490493_7271137553168216114_n.jpg';
if (file_exists($logoPath)) {
    readfile($logoPath);
} else {
    http_response_code(404);
}
exit;
?>

