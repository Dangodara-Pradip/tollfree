<?php
$port = "COM5"; // change to your Arduino port

$fp = fopen($port, "r");

if (!$fp) {
    die("Cannot open serial port");
}

$distance = fgets($fp);
echo $distance;

fclose($fp);
?>