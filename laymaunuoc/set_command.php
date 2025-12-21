<?php
$cmd = $_GET['cmd'] ?? 'OFF';
file_put_contents("command.txt", $cmd);
echo "Command set to $cmd";
?>
