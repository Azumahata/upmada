# Upmada

## Description
* 簡易アップロードツールです。
  * This is easy upload tool.
* 単一ファイルで作成しており、データベースを必要としません。
  * It is created in a single file and does not require a database.

## Demo
![Sample](https://github.com/Azumahata/upmada/blob/master/src/files/demo.png "Sample")

## Requirement
```
PHP 5.4.16
```

## Usage

* ファイルを選択または、アップロードしたいファイルを青い箇所へドラッグアンドドロップ
  * Select file or drag and drop to blue area.
* 当然ですが、不特定多数がアクセスできる環境に設置することは推奨しません。
  * Of course, I do not recommend installing in an environment accessible to everyone.

## Install and getting started

* CentOS7
  * ルート権限で実行してください
    * Do root permission
```console

$ yum install -y apache php php-devel git
$ systemctl start httpd
$ systemctl enable httpd

$ firewall-cmd --add-service http
$ firewall-cmd --add-service http --permanent

$ setenforce 0
$ sed -i 's/SELINUX=enforcing/SELINUX=permissive/' /etc/selinux/config

$ cd /var/www/html
$ git clone https://github.com/Azumahata/upmada.git
$ chown apache:apache upmada/files
```
access to http://localhost/upmada/index.php

## Licence
* MIT

## Author
[Azumahata](https://github.com/Azumahata)

