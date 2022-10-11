<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
此为说明文挡，请用FTP下载后查看
</body>
</html>
<?php
exit;
__halt_compiler();
 ?>

配置安装说明:

创建数据库,设置用户权限:
   create database ddns;
   grant all on ddns.* to mysqluser@localhost;

创建表:
   use ddns;
   create table ddns (
      id int unsigned not null auto_increment,
      user char(8) not null,
      pwd char(44) not null,
      updatekey char(40) default '-',  -- 用于更新记录的认证字符串
      num tinyint default 5,           -- 此用户可以添加的记录数
      time timestamp default 0,        -- 更新时间
      domain char(16) not null,        -- 子域名
      rectype tinyint default 1,       -- 记录类型 1=A,2=TXT,3=AAAA,4=CNAME,
      ip varchar(255) not null,        -- 记录内容
      changed tinyint default 0,       -- 记录是否改变过
      PRIMARY KEY (id),
      key domain(domain),
      key changed(changed)
   )default charset=utf8;

插入测试数据(用户名密码都为test):
可以跳过这步,直接插入管理帐号.
   insert into ddns values(10,'test','12341303df0377b5c5c72aeb39f9334a94a7ad78d615','a94a8fe5ccb19ba61c4c0873d391e987982fbbd3',5,now(),'test',1,'127.0.0.1',0);
   insert into ddns values(11,'test','12341303df0377b5c5c72aeb39f9334a94a7ad78d615','a94a8fe5ccb19ba61c4c0873d391e987982fbbd3',5,now(),'tst1',1,'127.0.0.1',0);

修改mysql账户信息:
   pdo_new.php

修改域名zone信息:
   config.php
   如动态域名为 xxx.ddns.abc.com , 则填 'ddns.abc.com'
      $config_zone='your.zone.xxx';
   ddns.php 脚本的位置
      $config_link='www.yourweb.xxx/ddns';
   log 文件的位置
      $log_file='/var/www/html/ddns/log.ddns.php';

设置log文件的权限为 666:
   log.ddns.php
   chmod 666 log.ddns.php;

读出数据库中的记录,更新到dns服务器中:
   设置cron任务,每1-5分钟执行一次,
   命令行php脚本:  php ddns_update_cmd.php 执行.
     ddns_update_cmd.php
   user crontab:
   */2 * * * * /usr/bin/php /var/www/html/ddns/ddns_update_cmd.php -u -w

删除测试数据,然后,插入管理员帐号(用户名密码都为test):
   insert into ddns values(10,'test','12341303df0377b5c5c72aeb39f9334a94a7ad78d615','a94a8fe5ccb19ba61c4c0873d391e987982fbbd3',65,now(),'test',1,'127.0.0.1',0);
   用test管理帐号登录, 创建其他帐号,比如adm, 修改普通帐号adm为管理帐号, 用adm登录,改test为普通帐号.(test为普通帐号,即为测试帐号,禁止改密码)

列出dns服务器中所有的记录,对比数据库记录,显示清理建议 (不实际删除dns记录):
   命令行php脚本:  php list_domain_not_in_database.php 执行.
   list_domain_not_in_database.php


 ---------- end ----------
