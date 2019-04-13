<?php
const TITLE = 'うｐﾏﾀﾞｧ?(・∀・ )っ/凵⌒☆ﾁﾝﾁﾝ';
const UPLOAD_DIR = './files';
const DISK_PATH = '/';

$PHP_INI_SETTINGS = array(
  'メモリ使用量上限'       => ini_get('memory_limit'),
  'POSTデータの最大サイズ' => ini_get('post_max_size'),
  '最大アップロードサイズ' => ini_get('upload_max_filesize'),
);


const TO_MiB = 1048576;    // 1024 ** 2
const TO_GiB = 1073741824; // 1024 ** 3

$SYSTEM_INFO = array(
  'disk_total'    => disk_total_space(DISK_PATH),
  'disk_free'     => disk_free_space(DISK_PATH),
  'disk_used'     => disk_total_space(DISK_PATH) - disk_free_space(DISK_PATH),
  'disk_used_per' => (disk_total_space(DISK_PATH) - disk_free_space(DISK_PATH)) / disk_total_space(DISK_PATH) * 100,
);

function upload() {
  $uploadedFile = $_FILES['upload_file'];
  if (!is_uploaded_file($uploadedFile['tmp_name']) || $uploadedFile['error'] !== 0) {
    return '<span class="error">失敗: 有効なファイルではないか、サイズが大きすぎます。</span>';
  }

  $uploadFileName = sprintf('%s_%s', date('YmdHis'), basename($uploadedFile['name']));
  $uploadPath     = sprintf('%s/%s', UPLOAD_DIR , $uploadFileName);
  if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
    return '<span class="error">失敗: ファイルの作成に失敗しました。</span>';
  }

  return sprintf('<span class="notice">成功: アップロードが正常に完了しました。(%s)</span>', $uploadFileName);
}

function deleteFile($filePath) {
  if (is_null($_GET["delete"])) return;
  $deleteFileName = basename($_GET["delete"]);
  $deletePath =  sprintf('%s/%s', UPLOAD_DIR , $deleteFileName);
  if (!file_exists($deletePath)) {
    return '<span class="error">失敗: 削除するファイルが存在しません。</span>';
  }
  if (!unlink($deletePath)) {
    return '<span class="error">失敗: 削除できませんでした。</span>';
  }
  return sprintf('<span class="notice">成功: 削除が正常に完了しました(%s)</span>', $deleteFileName);
}

function getUploadedFiles() {
  $files = array(); // path : { name : basename, size : filesize }
  foreach (glob(UPLOAD_DIR.'/*') as $file) {
    $info = array(
      'name' => str_replace(UPLOAD_DIR . '/', '', $file),
      'size' => filesize($file),
    );
    $files[$file] = $info;
  } unset($file);
  krsort($files);

  return $files;
}

?>
<!DOCTYPE html>
<html lang='ja'>
<head>
<meta charset='UTF-8'>
<link rel='stylesheet' type='text/css' href='css/html5reset.css'  />
<style type='text/css'>  
.tac { text-align: center; }

h1 {
  position: relative;
  padding-left: 12px;
}
h1:before {
  position: absolute;
  content: '';
  bottom: -0px;
  left: 0;
  width: 0;
  height: 0;
  border: none;
  border-left: solid 15px transparent;
  border-bottom: solid 15px rgb(230, 90, 90);
}
h1:after {
  content: "";
  display: block;
  height: 2px;
  background: -webkit-linear-gradient(to right, rgb(230, 90, 90), transparent);
  background: linear-gradient(to right, rgb(230, 90, 90), transparent);
}
h1 a { text-decoration: none;}
h1 a:hover { text-decoration: underline;}
h1 a:link { color: #000; }
h1 a:visited { color: #000; }

input {
  color: #fff;
  padding: 8px;
  border: double 4px #fff;
}
input[type='file'] { background-color: #06d; }
input[type='submit'] { background-color: #060; }

table {
  font-size: 0.8em;
  width: 99%;
  border: solid 1px #bbb;
  border-collapse: collapse;
}
table td, th {
  padding: 4px;
  border: solid 1px #bbb;
}
table tr:nth-child(odd) { background-color: #eee }

.description { font-size: 0.4em; }
span.error  { font-size: 0.8em; color: #e00; }
span.notice { font-size: 0.8em; color: #0a0; }

.system-info { margin: 8px; }
.system-info .disk-total {
  width: 21em;
  background-color: #000;
  border-style: ridge;
}
.system-info .disk-used  {
  width: <?php echo $SYSTEM_INFO['disk_used_per'] ?>%;
  color: #FFF;
  background-color: #e55;
  white-space: nowrap;
}

.uploaded-count { font-size: 0.8em; }

</style>

<script type='text/javascript'> 
</script>
<title><?php echo TITLE ?></title>
</head>
<body>
<div class='header'>
  <h1><a href="./"><?php echo TITLE ?></a></h1>
</div>
<div class='system-info'>
  <div class='disk-total'><div class='disk-used'>
    <?php echo sprintf("&nbsp;Disk: %s%% (%sGiB / %sGiB)", 
      round($SYSTEM_INFO['disk_used_per'], 2),
      round($SYSTEM_INFO['disk_used'] / TO_GiB, 2),
      round($SYSTEM_INFO['disk_total'] / TO_GiB, 2)
    ); ?>
  </div></div>
</div>
<div class='upload'>
  <form action='index.php' method='post' enctype='multipart/form-data'>
    <input type='file' name='upload_file' />
    <input type='submit' value='アップロード' />
  </form>
  <div class='description'>
    <?php echo implode(",&nbsp", array_map(function($k, $v)  {return "$k:$v";}, array_keys($PHP_INI_SETTINGS), $PHP_INI_SETTINGS)) ?>
  </div>
</div>

<div class='result-message'>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  echo upload();
} else {
  echo deleteFile();
}
?>
</div>

<div class='uploaded-files'>
<?php $uploadedFiles = getUploadedFiles(); ?>
  <div class="uploaded-count"><?php echo count($uploadedFiles) ?>件アップロードされています。</div>
  <table>
    <tr><th>ファイル名</th><th>サイズ</th><th>ダウンロード</th><th>削除</th></tr>
<?php foreach ($uploadedFiles as $path => $info) : ?>
    <tr>
      <td><a             href="<?php echo  htmlspecialchars($path) ?>"><?php echo htmlspecialchars($info['name']) ?></a></td>
      <td><?php echo round($info['size'] / TO_MiB, 2) ?> MiB</td>
      <td class="tac"><a href="<?php echo  htmlspecialchars($path) ?>" download="<?php echo $path ?>">[↓]</a></td>
      <td class="tac"><a href="<?php echo  "./?", http_build_query(array('delete' => htmlspecialchars($path))) ?>">[x]</a></td>
    </tr>
<?php endforeach; unset($path, $info) ?>
  </table>
</div>

</body>
</html>
