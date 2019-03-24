<?php
session_start(); // 验证码支持
unset($_SESSION['ddns_login_user']); // 清除session中的用户id
$user='';
$pwd='';
$vercode='';
if(isset($_POST['user'])) $user=trim($_POST['user']);
if(isset($_POST['pwd'])) $pwd=trim($_POST['pwd']);
if(isset($_POST['vercode'])) $vercode=trim($_POST['vercode']);

if(strlen($vercode)>0) {
   if( ! isset($_SESSION['authnum_session']) || strcasecmp($_SESSION['authnum_session'], $vercode) != 0) {
      unset($_SESSION['authnum_session']);
      header("Content-Type: text/html;charset=utf-8");
      echo "<script>alert('验证码(Vercode)不正确!');window.history.back();</script>";
      exit;
   }
}
unset($_SESSION['authnum_session']);

if(!( strlen($user)>2 && strlen($pwd)>2 && strlen($vercode)>1)) {
   ?>
<!doctype html>
<html>
<head>
<title>
admin_DDNS
</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, minimum-scale=0.5, maximum-scale=2.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">
input { font-size:23px; }
body { font-size:21px; }
</style>
</head>
<body>
<form method=POST>
<table border=0 cellspacing=8>
<tr><td>
User:
<input type=text name=user size=8>
</td></tr>
<tr><td>
pwd:
<input type=password name=pwd size=8>
</td></tr>
<tr><td>
Vercode:
<input name="vercode" size=6 value="">
</td></tr>
<tr><td>
 <img border=1 width="130px" height="70px" title="点击刷新" src="captcha3.php?<?=time()?>" align="absbottom" style="vertical-align:bottom;" onclick="this.src='captcha3.php?'+Math.random();" />
</td></tr>
<tr><td>
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
<input type=submit value='Login'>
</td></tr>
</table>
</form>
<br><br>
&nbsp; <a href=./>BACK</a>
<br><br>---end---<br><br>
</body>
</html>
   <?php
   exit;
}

// 登录
$authok=false;
require('pdo_new.php');
require('config.php');
$stmt=$db->prepare('select id,pwd from ddns where user=:user limit 1');
$stmt->bindParam(':user',$user,PDO::PARAM_STR);
$stmt->execute();
$data=$stmt->fetchAll();
$stmt->closeCursor();
if(count($data)>0) { // 有记录
   $bid=$data[0]->id;
   $bpwd=$data[0]->pwd;
   $salt=substr($bpwd,0,4);
   if(strcmp($bpwd,$salt.sha1($salt.$pwd))==0) {
      $authok=true;
   }
}
if($authok == false) {
   mylog('login err.u='.$user.',p='.$pwd.',v='.$vercode);
   header("Content-Type: text/html;charset=utf-8");
   echo "<script>alert('登录失败! 帐号错误!');window.history.back();</script>";
   exit;
}
mylog('login ok.u='.$user.',p='.$pwd);

$_SESSION['ddns_login_user'] = $user; // 用户id保存到session中

header("Content-Type: text/html;charset=utf-8");
echo "<script>window.location='adm1.php';</script>";
exit;

function mylog($s){
        global $log_file;
        $fp=fopen($log_file,'a');
        flock($fp,LOCK_EX);
        fputs($fp,date('Y-m-d.H:i:s ').$_SERVER['REMOTE_ADDR'].' adm:');
        fputs($fp,$s);
        fputs($fp,"\n");
        flock($fp,LOCK_UN);
        fclose($fp);
}
