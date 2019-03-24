<?php
$user='';
$pwd='';
$domain='';
$key='';
$ip='';
if(isset($_GET['user'])) $user=trim($_GET['user']);
if(isset($_GET['pwd'])) $pwd=trim($_GET['pwd']);
if(isset($_GET['domain'])) $domain=trim($_GET['domain']);
$domain=strtolower($domain);
if(isset($_GET['key'])) $key=trim($_GET['key']);
if(isset($_GET['ip'])) $ip=trim($_GET['ip']);

$authok=false;
$uid=0;
$odomain='';
$oip='';
require('pdo_new.php');
require('config.php');

$zonelen=strlen($config_dot_zone);
if(strlen($domain)>$zonelen && strcmp(substr($domain,$zonelen *-1),$config_dot_zone)==0) {
   // 去掉后缀
   $domain=substr($domain,0,$zonelen *-1);
}
//echo $domain.'<br>';

if(0) {
   // 用户名密码 domain 认证
   if( strlen($user)>2 && strlen($pwd)>2 && strlen($domain)>0 ) {
      $stmt=$db->prepare('select id,domain,ip from ddns where domain=:domain and user=:user and pwd=:pwd');
      $stmt->bindParam(':domain',$domain,PDO::PARAM_STR);
      $stmt->bindParam(':user',$user,PDO::PARAM_STR);
      $stmt->bindParam(':pwd',sha1($pwd),PDO::PARAM_STR);
   }
}

// key 和 domain 认证
if( strlen($domain)>0 && strlen($key)>30) {
   $stmt=$db->prepare('select id,domain,rectype,ip from ddns where domain=:domain and updatekey=:key');
   $stmt->bindParam(':domain',$domain,PDO::PARAM_STR);
   $stmt->bindParam(':key',$key,PDO::PARAM_STR);
}
if(! isset($stmt) || ! is_a($stmt,'PDOStatement')) {
   echo "EMPTY\n";
   mylog('empty.');
   exit;
}

$stmt->execute();
$data=$stmt->fetchAll();
$stmt->closeCursor();

if(count($data)>0) { // 有记录
   $bid=$data[0]->id;
   $bdomain=$data[0]->domain;
   $brectype=$data[0]->rectype;
   $bip=$data[0]->ip;

   $uid=$bid;
   $odomain=$bdomain;
   $oip=$bip;
   $authok=true;
}
if($authok == false) {
   echo "ERROR\n";
   $msg='';
   if(strlen($user)>0) $msg.=',u='.$user;
   if(strlen($pwd)>0) $msg.=',p='.$pwd;
   if(strlen($key)>0) $msg.=',k='.$key;
   mylog('ERR.d='.$domain.$msg);
   exit;
}

if($brectype==1) { // A记录
   if(preg_match('/^((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$/',$ip)<1) {
      // 检查ip格式
      $ip=$_SERVER['REMOTE_ADDR'];
   }
   //echo $ip.'<br>';
} else if($brectype==2) {  // TXT记录
   if(strlen($ip)>250) $ip=substr($ip,0,250);
} else if($brectype==3) {  // AAAA记录
   if(preg_match('/^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/',$ip)<1) {
      // 检查ip格式
      $ip='::1';
   }
}

$chged=0;
if( strcmp($ip,$oip)!=0 ) { // ip有变化
   // set chged=2, nsupdate 时,chged=chged-1, 防止没更新
   $chged=2;
   $stmt=$db->prepare('update ddns set time=now(),ip=:ip,changed=:chged where id=:id');
   $stmt->bindParam(':ip',$ip,PDO::PARAM_STR);
   $stmt->bindParam(':chged',$chged,PDO::PARAM_INT);
   $stmt->bindParam(':id',$uid,PDO::PARAM_INT);
} else {
   $stmt=$db->prepare('update ddns set time=now() where id=:id');
   $stmt->bindParam(':id',$uid,PDO::PARAM_INT);
}
$stmt->execute();
$stmt->closeCursor();

echo "UPDATE OK\n";
$msg='';
if(strlen($user)>0) $msg.=',u='.$user;
if(strlen($pwd)>0) $msg.=',p='.$pwd;
mylog('OK.d='.$domain.',i='.$ip.',c='.$chged.$msg);
exit;

function mylog($s){
        global $log_file;
        $fp=fopen($log_file,'a');
        flock($fp,LOCK_EX);
        fputs($fp,date('Y-m-d.H:i:s ').$_SERVER['REMOTE_ADDR'].' ddns:');
        fputs($fp,$s);
        fputs($fp,"\n");
        flock($fp,LOCK_UN);
        fclose($fp);
}



/*
create database ddns;
grant all on ddns.* to mysqluser@localhost;
#------------------------------------
create table ddns ( id int unsigned not null auto_increment, user char(8) not null, pwd char(40) not null,updatekey char(40) default '-', num tinyint default 5, time timestamp default 0, domain char(16) not null,ip char(15) not null,changed tinyint default 0, PRIMARY KEY (id),unique key domain(domain))default charset=utf8;

insert into ddns values(10,'test','a94a8fe5ccb19ba61c4c0873d391e987982fbbd3','a94a8fe5ccb19ba61c4c0873d391e987982fbbd3',5,now(),'test','127.0.0.1',0);
insert into ddns values(11,'test','a94a8fe5ccb19ba61c4c0873d391e987982fbbd3','a94a8fe5ccb19ba61c4c0873d391e987982fbbd3',5,now(),'tst1','127.0.0.1',0);
*/
