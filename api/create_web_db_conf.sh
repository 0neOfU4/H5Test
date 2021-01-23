#!/bin/bash
set -e
cd $(dirname $0)
if [ ! -z "$1" ];then
   echo "$1"|base64 -d >`pwd`/conf.sh
fi
. conf.sh && rm -f conf.sh


web_db_conf_template()
{
cat > db.php<<-EOF
<?php
\$dbhost="$gxserver_db_host";
\$dbuser="$dbacc_user";
\$dbpwd="$dbacc_passwd";
\$dbport="$dbacc_port";

\$dbacc="$gxserver_dbacc_name";
\$dblog="$gxserver_dblog_name";
\$dbserverlog="$dbserverlog";

\$mailurl="http://sendsms.funova.com/mail/mail.php?title=%s&txt=%s";

\$game_host = "$gxserver_host_nei";
\$game_port = $game_port;
\$game_key = "$game_password";

\$conn = @mysql_connect(\$dbhost.":".\$dbport,\$dbuser,\$dbpwd);
if (!\$conn) die ("对不起，发生错误! 请检查conn.php中数据库的配置是否正确!");
mysql_query("set names GB2312");
mysql_select_db(\$dblog,\$conn);

?>
EOF
}


web_db_conf_template


