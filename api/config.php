<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

const SENSOR_DISTANCE_METERS = 2.0;
require_once __DIR__ . "/db.php";

function ensureGateAutoCloseColumn(mysqli $conn): void
{
    $r = $conn->query("SHOW COLUMNS FROM status LIKE 'gate_auto_close_at'");
    if ($r && $r->num_rows === 0) {
        $conn->query("ALTER TABLE status ADD COLUMN gate_auto_close_at BIGINT NULL");
    }
}

function ensureUpdatedAtColumn(mysqli $conn): void
{
    $r = $conn->query("SHOW COLUMNS FROM status LIKE 'updated_at'");
    if ($r && $r->num_rows === 0) {
        $conn->query(
            "ALTER TABLE status
             ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        );
    }
}

function ensureLightColumnRemoved(mysqli $conn): void
{
    $r = $conn->query("SHOW COLUMNS FROM status LIKE 'light'");
    if ($r && $r->num_rows > 0) {
        $conn->query("ALTER TABLE status DROP COLUMN light");
    }
}

function ensureStatusIdRemoved(mysqli $conn): void
{
    $r = $conn->query("SHOW COLUMNS FROM status LIKE 'id'");
    if ($r && $r->num_rows > 0) {
        $pk = $conn->query("SHOW KEYS FROM status WHERE Key_name = 'PRIMARY'");
        if ($pk && $pk->num_rows > 0) {
            $conn->query("ALTER TABLE status DROP PRIMARY KEY");
        }
        $conn->query("ALTER TABLE status DROP COLUMN id");
    }
}

function ensureVehicleLogsIdColumn(mysqli $conn): void
{
    $r = $conn->query("SHOW COLUMNS FROM vehicle_logs LIKE 'id'");
    if ($r && $r->num_rows === 0) {
        // Add a unique row id so each vehicle event is stored separately.
        $conn->query("ALTER TABLE vehicle_logs ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
    }
}

function initDb(): void
{
    $conn = db();
    $conn->query("
        CREATE TABLE IF NOT EXISTS status (
            `count` INT NOT NULL DEFAULT 0,
            gate VARCHAR(32) NOT NULL,
            speed DOUBLE NOT NULL DEFAULT 0,
            serial TEXT,
            gate_auto_close_at BIGINT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $conn->query("
        CREATE TABLE IF NOT EXISTS vehicle_logs (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            count_value INT NOT NULL,
            gate VARCHAR(32) NOT NULL,
            speed DOUBLE NOT NULL DEFAULT 0,
            serial TEXT,
            duration_ms DOUBLE NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    ensureGateAutoCloseColumn($conn);
    ensureUpdatedAtColumn($conn);
    ensureLightColumnRemoved($conn);
    ensureStatusIdRemoved($conn);
    ensureVehicleLogsIdColumn($conn);

    $r = $conn->query("SELECT COUNT(*) AS c FROM status");
    $row = $r->fetch_assoc();
    if ($row && (int) $row["c"] === 0) {
        $conn->query(
            "INSERT INTO status (`count`, gate, speed, serial) VALUES (0, 'CLOSED', 0, '')"
        );
    }
}

function closeGateIfDue(mysqli $conn): void
{
    $now = time();
    $stmt = $conn->prepare(
        "UPDATE status
         SET gate = 'CLOSED', gate_auto_close_at = NULL
         WHERE gate_auto_close_at IS NOT NULL AND gate_auto_close_at <= ?"
    );
    $stmt->bind_param("i", $now);
    $stmt->execute();
    $stmt->close();
}

/**
 * @return array{count: int, gate: string, speed: float, serial: string|null, updated_at: string|null}
 */
function getStatusRow(mysqli $conn): array
{
    closeGateIfDue($conn);
    $stmt = $conn->prepare(
        "SELECT `count`, gate, speed, serial, updated_at
         FROM status
         ORDER BY updated_at DESC
         LIMIT 1"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return [
            "count" => 0,
            "gate" => "CLOSED",
            "speed" => 0.0,
            "serial" => "",
            "updated_at" => null,
        ];
    }
    $row["count"] = (int) $row["count"];
    $row["speed"] = (float) $row["speed"];
    return $row;
}

initDb();
