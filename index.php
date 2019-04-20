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
  $search = trim($_GET['search']);
  if (empty($search)) {
    $search = '*';
  } else {
    $search = '*'.basename($search).'*';
  }

  $files = array(); // path : { name : basename, size : filesize }
  foreach (glob(UPLOAD_DIR."/${search}") as $file) {
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
<link rel='stylesheet' type='text/css' href='css/app.css'  />
<title><?php echo TITLE ?></title>
</head>
<body>
<div class='header'>
  <h1><a href="./"><?php echo TITLE ?></a></h1>
</div>
<div class='upload'>
  <form action='index.php' method='post' enctype='multipart/form-data'>
    <input type='file' name='upload_file' />
    <input type='submit' value='アップロード' />
  </form>
</div>
<div class='system-info'>
  <div class='disk-total'><div class='disk-used' style="width: <?php echo $SYSTEM_INFO['disk_used_per'] ?>%">
    <?php echo sprintf("&nbsp;Disk: %s%% (%sGiB / %sGiB)", 
      round($SYSTEM_INFO['disk_used_per'], 2),
      round($SYSTEM_INFO['disk_used'] / TO_GiB, 2),
      round($SYSTEM_INFO['disk_total'] / TO_GiB, 2)
    ); ?>
  </div></div>
</div>
<div class='description'>
  <?php echo implode(",&nbsp", array_map(function($k, $v)  {return "$k:$v";}, array_keys($PHP_INI_SETTINGS), $PHP_INI_SETTINGS)) ?>
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

<?php $uploadedFiles = getUploadedFiles(); ?>
<div class="uploaded-info">
  <div class="uploaded-count"><?php echo count($uploadedFiles) ?>件のアップロード</div>
  <div class="uploaded-search">
    <form action='index.php' method='get' enctype='multipart/form-data'>
      <input type="text" name="search" size="40%" maxlength="128">
      <input type='submit' value='検索' />
    </form>
  </div>
</div>

<div class='uploaded-files'>
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
