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
    $serialText = (string) ($body["message"] ?? "");

    $conn = db();

    $stmt = $conn->prepare(
        "UPDATE status
         SET serial = ?
         ORDER BY updated_at DESC
         LIMIT 1"
    );
    $stmt->bind_param("s", $serialText);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["message" => "Serial output saved"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to store serial output"]);
}
