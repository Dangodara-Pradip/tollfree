<?php
$conn = new mysqli("localhost", "root", "", "toll");

$count = $_GET['count'];
$speed = $_GET['speed'];
$gate = $_GET['gate'];

$sql = "INSERT INTO data (count, speed, gate)
        VALUES ('$count', '$speed', '$gate')";

if ($conn->query($sql)) {
    echo "OK";
} else {
    echo "ERROR";
}

$conn->close();
?>