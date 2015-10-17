#!/bin/bash
set -eu

# httpd.conf 追加
cd /etc/httpd/sites-enabled
if [[ -f httpd-vhosts.react-php.conf ]]; then
    sudo rm httpd-vhosts.react-php.conf
fi
sudo cp /var/www/html/react-php/setup/httpd-vhosts.conf httpd-vhosts.react-php.conf
sudo /etc/init.d/httpd restart

# テストコード
echo "C:\Windows\System32\drivers\etc\hosts に下記を追記してください:"
echo 192.168.33.10  react-php.local
echo
echo "次のページにアクセスしてみてください: http://react-php.local/"
echo "監視ログ: tailf /etc/httpd/logs/error.log"
