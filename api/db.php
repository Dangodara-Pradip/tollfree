<?php
declare(strict_types=1);

function db(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli("localhost", "root", "", "toll");
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
            exit;
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}
