<?php
declare(strict_types=1);

require_once __DIR__ . "/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

try {
    $raw = file_get_contents("php://input") ?: "";
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        $body = [];
    }

    $conn = db();
    $status = getStatusRow($conn);

    $durationMs = (float) ($body["durationMs"] ?? 0);
    $speed = $durationMs > 0
        ? round(SENSOR_DISTANCE_METERS / ($durationMs / 1000), 2)
        : $status["speed"];

    $nextCount = $status["count"] + 1;
    $closeAt = time() + 3;
    $gate = "OPEN";
    $serial = (string) ($status["serial"] ?? "");

    $stmt = $conn->prepare(
        "INSERT INTO status (`count`, gate, speed, serial, gate_auto_close_at)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isdsi", $nextCount, $gate, $speed, $serial, $closeAt);
    $stmt->execute();
    $stmt->close();

    $logStmt = $conn->prepare(
        "INSERT INTO vehicle_logs (count_value, gate, speed, serial, duration_ms)
         VALUES (?, ?, ?, ?, ?)"
    );
    $logStmt->bind_param("isdsd", $nextCount, $gate, $speed, $serial, $durationMs);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode(["message" => "Vehicle counted", "speed" => $speed, "count" => $nextCount]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update status"]);
}
