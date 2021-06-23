<?php
class Info {
  const title     = 'うｐﾏﾀﾞｧ?(・∀・ )っ/凵⌒☆ﾁﾝﾁﾝ';
  const uploadDir = './files';
  private $memoryLimit;
  private $postMaxSize;
  private $uploadMaxFilesize;

  static private $instance;
  static public function getInstance() {
    if (is_null($instance))
      self::$instance = new Info();
    return self::$instance;
  }

  private function __construct() {
    $this->memoryLimit       = $this->createProp('メモリ使用量上限',       ini_get('memory_limit'));
    $this->postMaxSize       = $this->createProp('POSTデータの最大サイズ', ini_get('post_max_size'));
    $this->uploadMaxFilesize = $this->createProp('最大アップロードサイズ', ini_get('upload_max_filesize'));
  }

  private function createProp(string $label, string $value) {
    return ['label' => $label, 'value' => $value];
  }

  public function __get(string $name) {
    return $this->$name;
  }
}

class Disk {
  const TO_MiB = 1048576;    // 1024 ** 2
  const TO_GiB = 1073741824; // 1024 ** 3

  const diskPath  = '/';
  static private $diskTotal;
  static private $diskFree;
  static private $diskUsed;
  static private $diskUsedPer;

  static public function info() {
    return sprintf("Disk: %s%% (%sGiB / %sGiB)",
      round(self::diskUsedPer(), 2),
      self::toGiB(self::diskUsed()),
      self::toGiB(self::diskTotal())
    );
  }

  static public function toMiB(int $size) { return round($size / self::TO_MiB, 2); }
  static public function toGiB(int $size) { return round($size / self::TO_GiB, 2); }

  static public function diskTotal() {
    return self::$diskTotal ??= disk_total_space(self::diskPath);
  }

  static public function diskFree() {
    return self::$diskFree ??= disk_free_space(self::diskPath);
  }

  static public function diskUsed() {
    return self::$diskUsed ??= self::diskTotal() - self::diskFree();
  }
  static public function diskUsedPer() {
    return self::$diskUsedPer ??= self::diskUsed() / self::diskTotal() * 100;
  }
}


function upload() {
  $uploadedFile = $_FILES['upload_file'];
  if (!is_uploaded_file($uploadedFile['tmp_name']) || $uploadedFile['error'] !== 0) {
    return '<span class="error">失敗: 有効なファイルではないか、サイズが大きすぎます。</span>';
  }

  $uploadFileName = sprintf('%s_%s', date('YmdHis'), basename($uploadedFile['name']));
  $uploadPath     = sprintf('%s/%s', Info::uploadDir , $uploadFileName);
  if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
    return '<span class="error">失敗: ファイルの作成に失敗しました。</span>';
  }

  return sprintf('<span class="notice">成功: アップロードが正常に完了しました。(%s)</span>', $uploadFileName);
}

function deleteFile($filePath=null) {
  if (is_null($_GET["delete"])) return;
  $deleteFileName = basename($_GET["delete"]);
  $deletePath =  sprintf('%s/%s', Info::uploadDir , $deleteFileName);
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
  foreach (glob(Info::uploadDir."/${search}") as $file) {
    $info = array(
      'name' => str_replace(Info::uploadDir . '/', '', $file),
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
<title><?php echo Info::title ?></title>
</head>
<body>
<div class='header'>
  <h1><a href="./"><?php echo Info::title ?></a></h1>
</div>
<div class='upload'>
  <form action='index.php' method='post' enctype='multipart/form-data'>
    <input type='file' name='upload_file' />
    <input type='submit' value='アップロード' />
  </form>
</div>
<div class='system-info'>
  <div class='disk-total'>
    <div class='disk-used' style="width: <?php echo Disk::diskUsedPer() ?>%">
      &nbsp;<?php echo Disk::info() ?>
    </div>
  </div>
</div>
<div class='description'>
  <span><?php echo implode(':', Info::getInstance()->memoryLimit)       ?></span>
  <span><?php echo implode(':', Info::getInstance()->postMaxSize)       ?></span>
  <span><?php echo implode(':', Info::getInstance()->uploadMaxFilesize) ?></span>
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
      <td><?php echo Disk::toMiB($info['size']) ?> MiB</td>
      <td class="tac"><a href="<?php echo  htmlspecialchars($path) ?>" download="<?php echo $path ?>">[↓]</a></td>
      <td class="tac"><a href="<?php echo  "./?", http_build_query(array('delete' => htmlspecialchars($path))) ?>">[x]</a></td>
    </tr>
    <?php endforeach; unset($path, $info) ?>
  </table>
</div>

</body>
</html>
