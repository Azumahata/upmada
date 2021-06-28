<?php
declare(strict_types=1);
// ini_set('display_errors', "On");
// error_reporting(E_ALL);

class Info {
  const title     = 'うｐﾏﾀﾞｧ?(・∀・ )っ/凵⌒☆ﾁﾝﾁﾝ';
  private $memoryLimit;
  private $postMaxSize;
  private $uploadMaxFilesize;

  static private $instance;
  static public function getInstance() {
    if (is_null(self::$instance))
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

  static public function info() : string {
    return sprintf('Disk: %s%% (%sGiB / %sGiB)',
      round(self::diskUsedPer(), 2),
      self::toGiB(self::diskUsed()),
      self::toGiB(self::diskTotal())
    );
  }

  static public function toMiB(float $size) : float { return round($size / self::TO_MiB, 2); }
  static public function toGiB(float $size) : float { return round($size / self::TO_GiB, 2); }

  static public function diskTotal() : float {
    return self::$diskTotal ??= disk_total_space(self::diskPath);
  }

  static public function diskFree() : float {
    return self::$diskFree ??= disk_free_space(self::diskPath);
  }

  static public function diskUsed() : float {
    return self::$diskUsed ??= self::diskTotal() - self::diskFree();
  }
  static public function diskUsedPer() : float {
    return self::$diskUsedPer ??= self::diskUsed() / self::diskTotal() * 100;
  }
}

class Upmada {
  private const uploadDir  = './files';
  private $search  = '';
  private $files   = [];
  private $errors  = [];
  private $notices = [];

  public function searchQuery() : string {
    return $search ??= isset($_GET['search']) ? trim($_GET['search']) : '';
  }

  public function files() : array {
    if ($this->files) return $this->files;

    $pattern = empty($this->searchQuery()) ? '*' : '*'.basename($this->searchQuery()).'*';
    $this->files = array_map(function($f) {
      return [
        'path' => $f,
        'name' => str_replace(self::uploadDir . '/', '', $f),
        'size' => filesize($f),
      ];
    }, glob(self::uploadDir . '/' . $pattern));

    usort($this->files, function($a, $b) {
      return strcmp($b['path'], $a['path']);
    });

    return $this->files;
  }

  public function dispatch() : Closure {
    $requestMethod = strtolower($_SERVER['REQUEST_METHOD']);

    $callMethod = 'get';
    try {
      if ($requestMethod === 'get') {
        $callMethod = 'get';
      } elseif ($requestMethod === 'post') {
        $extraMethod = isset($_REQUEST['_method']) ? $_REQUEST['_method'] : null;
        if (is_null($extraMethod)) {
          $callMethod = 'post';
        } elseif ($extraMethod === 'DELETE') {
          $callMethod = 'delete';
        } else {
          throw new BadMethodCallException();
        }
      } else {
        throw new BadMethodCallException();
      }
    } catch (BadMethodCallException $e) {
      $this->setError('無効なリクエストです。');
    }

    return function() use ($callMethod) {
      $this->{$callMethod}();
    };
  }

  private function get() : bool {
    // do nothing
    return true;
  }

  private function post() : bool {
    $uploadedFile = isset($_FILES['upload_file'])    ? $_FILES['upload_file']    : '';
    $tmpName      = isset($uploadedFile['tmp_name']) ? $uploadedFile['tmp_name'] : '';

    if (!is_uploaded_file($tmpName) || $uploadedFile['error'] !== 0) {
      $this->setError('有効なファイルではないか、サイズが大きすぎます。');
      return false;
    }

    $uploaded = sprintf('%s_%s', date('YmdHis'), basename($uploadedFile['name']));
    $uploadedPath = sprintf('%s/%s', Upmada::uploadDir , $uploaded);
    if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadedPath )) {
      $this->setError('ファイルの作成に失敗しました。');
      return false;
    }

    $this->setNotice(sprintf('アップロードが正常に完了しました。(%s)', $uploaded));
    return true;
  }

  private function delete() : bool {
    $uploadedPath = $_REQUEST['path'];
    if (is_null($uploadedPath)) return false;

    $deleteFile = basename($uploadedPath);
    $deletePath =  sprintf('%s/%s', Upmada::uploadDir , $deleteFile);

    if (!file_exists($deletePath)) {
      $this->setError('削除するファイルが存在しません。');
      return false;
    }
    if (!unlink($deletePath)) {
      $this->setError('削除できませんでした。');
      return false;
    }

    $this->setNotice(sprintf('削除が正常に完了しました(%s)', $deleteFile));
    return true;
  }

  private function setNotice(string $_message) : void {
    $this->notices[] = $_message;
  }

  private function setError(string $_message) : void {
    $this->errors[] = $_message;
  }

  public function getNotices() : array {
    return $this->notices;
  }

  public function getErrors() : array {
    return $this->errors;
  }
}

$upmada = new Upmada();
$upmada->dispatch()();

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
  <h1><a href='./'><?php echo Info::title ?></a></h1>
</div>
<div class='upload'>
  <form action='/' method='post' enctype='multipart/form-data'>
    <input type='file' name='upload_file' />
    <input type='submit' value='アップロード' />
  </form>
</div>
<div class='system-info'>
  <div class='disk-total'>
    <div class='disk-used' style='width: <?php echo Disk::diskUsedPer() ?>%'>
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
<?php foreach ($upmada->getNotices() as $notice) : ?>
  <span class='notice'><?php echo $notice ?></span>
<?php endforeach; unset($notice) ?>
<?php foreach ($upmada->getErrors() as $error) : ?>
  <span class='error'><?php echo $error ?></span>
<?php endforeach; unset($error) ?>
</div>

<div class='uploaded-info'>
  <div class='uploaded-count'><?php echo count($upmada->files()) ?>件のアップロード</div>
  <div class='uploaded-search'>
    <form action='/' method='get' enctype='multipart/form-data'>
    <input type='text' name='search' size='40%' maxlength='128' value='<?php echo htmlspecialchars($upmada->searchQuery()) ?>'>
      <input type='submit' value='検索' />
    </form>
  </div>
</div>

<div class='uploaded-files'>
  <table>
    <tr><th>ファイル名</th><th>サイズ</th><th>ダウンロード</th><th>削除</th></tr>
    <?php foreach ($upmada->files() as $f) : ?>
    <tr>
      <td><a href='<?php echo  htmlspecialchars($f['path']) ?>'><?php echo htmlspecialchars($f['name']) ?></a></td>
      <td><?php echo Disk::toMiB($f['size']) ?> MiB</td>
      <td class='tac'><a href='<?php echo  htmlspecialchars($f['path']) ?>' download='<?php echo $f['path'] ?>'>[↓]</a></td>
      <td class='tac'>
        <form method='post' action='/'>
          <input type='hidden' name='_method' value='DELETE' />
          <input type='hidden' name='search'  value='<?php echo $upmada->searchQuery() ?>' />
          <input type='hidden' name='path'    value='<?php echo htmlspecialchars($f['path']) ?>' />
          <input type='submit' value='x'>
        </form>
      </td>
    </tr>
    <?php endforeach; unset($path, $f) ?>
  </table>
</div>

</body>
</html>
