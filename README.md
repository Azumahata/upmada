# Upmada

## Description
* 簡易アップロードツールです。
  * This is easy upload tool.
* 単一ファイルで作成しており、データベースを必要としません。
  * It is created in a single file and does not require a database.

## Demo
![Sample](https://github.com/Azumahata/upmada/blob/master/files/demo.png "Sample")

## Requirement
```
PHP 5.4.16
```

## Usage

* ファイルを選択または、アップロードしたいファイルを青い箇所へドラッグアンドドロップ
  * Select file or drag and drop to blue area.
* 当然ですが、不特定多数がアクセスできる環境に設置することは推奨しません。
  * Of course, I do not recommend installing in an environment accessible to everyone.

## Install

* CentOS7
```console
$ yum install -y apache php php-devel git
$ systemctl start httpd
$ systemctl enable httpd

$ cd /var/www/html
$ git clone [this repo]
$ chown apache:apache upmada/files
```
access to http://localhost/upmada/index.php

## Licence
* MIT

## Author
[Azumahata](https://github.com/Azumahata)

