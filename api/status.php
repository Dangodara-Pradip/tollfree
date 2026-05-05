<?php
declare(strict_types=1);

require_once __DIR__ . "/config.php";
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

try {
    $row = getStatusRow(db());
    $updatedAtRaw = (string) ($row["updated_at"] ?? "");
    $updatedAtIso = $updatedAtRaw !== ""
        ? gmdate('Y-m-d\TH:i:s\Z', strtotime($updatedAtRaw))
        : gmdate('Y-m-d\TH:i:s\Z');
    echo json_encode([
        "count" => (int) ($row["count"] ?? 0),
        "gate" => (string) ($row["gate"] ?? "CLOSED"),
        "speed" => (float) ($row["speed"] ?? 0),
        "serial" => (string) ($row["serial"] ?? ""),
        "date" => $updatedAtIso,
        "updated_at" => $updatedAtIso,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Failed to read status",
        "count" => 0,
        "gate" => "OFFLINE",
        "speed" => 0,
        "serial" => "",
        "date" => gmdate('Y-m-d\TH:i:s') . 'Z',
    ]);
}
