<?php
declare(strict_types=1);

require_once __DIR__ . "/api/db.php";

$conn = db();
$result = $conn->query("SELECT `count`, speed, gate FROM status WHERE id = 1 LIMIT 1");
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    $row = [
        "count" => 0,
        "speed" => 0,
        "gate" => "CLOSED",
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Toll Dashboard</title>
</head>
<body>

<h1>🚗 Toll System</h1>

<p>Vehicle Count: <?php echo htmlspecialchars((string) $row['count'], ENT_QUOTES, 'UTF-8'); ?></p>
<p>Speed: <?php echo htmlspecialchars((string) $row['speed'], ENT_QUOTES, 'UTF-8'); ?></p>
<p>Gate: <?php echo htmlspecialchars((string) $row['gate'], ENT_QUOTES, 'UTF-8'); ?></p>

<script>
setTimeout(() => {
  location.reload();
}, 2000);
</script>

</body>
</html>
