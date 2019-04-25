<?php
session_start();
if( ! isset($_SESSION['ddns_login_user']) || strlen($_SESSION['ddns_login_user'])<3) {
   header("Content-Type: text/html;charset=utf-8");
   echo "<script>alert('未登录!'); window.location='adm.php';</script>";
   exit;
}

$user=$_SESSION['ddns_login_user'];

require('pdo_new.php');
require('config.php');
require('rectype.php');
require('check_domain.php');
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
select { font-size:23px; }
</style>
</head>
<body>
<br>
<a href="adm1.php">刷新</a>
<br><br>
user=<?=$user?>
<br>

<?php
require('isweekpwd_func.php');
require('random_bytes_csn.php');
// 查询当前用户域名限制,和已有域名数量
$stmt=$db->prepare('select count(id) cnt,num from ddns where user=:user');
$stmt->bindParam(':user',$user,PDO::PARAM_STR);
$stmt->execute();
$data=$stmt->fetchAll();
$stmt->closeCursor();
$bcount=$data[0]->cnt;
$bnum=$data[0]->num;
// ============= 增加新帐号(管理权限) =============
if($bnum>63) { // >=64 就是管理员了
   $errmsg='';
   if(isset($_POST['submit']) && $_POST['submit']=='New Account') {
      $huser='';
      $hpwd='';
      $hdomain='';
      if(isset($_POST['user']))   $huser=trim($_POST['user']);
      if(isset($_POST['pwd']))    $hpwd=trim($_POST['pwd']);
      if(isset($_POST['domain'])) $hdomain=trim($_POST['domain']);
      do {
         if(strlen($huser)<3){
            $errmsg='username too short';
            break;
         }
         if(isweekpwd($huser,$hpwd)) {
            $errmsg='密码太简单';
            break;
         }
         if(strlen($hdomain)<1) {
            $errmsg='domain empty';
            break;
         }
         $ret=check_domain($hdomain,1);
         if($ret != 'OK') {
            $errmsg.=$ret;
            break;
         }
         // 是否有重复的user
         $stmt=$db->prepare('select id from ddns where user=:user limit 1');
         $stmt->bindParam(':user',$huser,PDO::PARAM_STR);
         $stmt->execute();
         $data=$stmt->fetchAll();
         $stmt->closeCursor();
         if(count($data)>0) { // 有记录
            $errmsg .='用户已存在,添加帐号失败. ';
            break;
         }

         // 是否有重复的domain
         $hnum=5;
         $stmt=$db->prepare('select id from ddns where domain=:domain limit 1');
         $stmt->bindParam(':domain',$hdomain,PDO::PARAM_STR);
         $stmt->execute();
         $data=$stmt->fetchAll();
         $stmt->closeCursor();
         if(count($data)>0) { // 有记录
            $errmsg .='domain已被使用,添加失败. ';
         } else {
            // 查出旧域名，写入另一个删除表，由脚本负责从dns删除.(没实现)
            $newkey=uniqid('ddns_',true);
            $newkey=sha1($newkey);
            $salt=random_bytes_csn(2);
            $shapwd=$salt.sha1($salt.$hpwd);
            $stmt=$db->prepare('insert into ddns (user,pwd,updatekey,num,domain,rectype,ip,time,changed) values(:user,:pwd,:updatekey,:num,:domain,1,\'127.0.0.1\',now(),2)');
            $stmt->bindParam(':user',$huser,PDO::PARAM_STR);
            $stmt->bindParam(':pwd',$shapwd,PDO::PARAM_STR);
            $stmt->bindParam(':updatekey',$newkey,PDO::PARAM_STR);
            $stmt->bindParam(':num',$hnum,PDO::PARAM_INT);
            $stmt->bindParam(':domain',$hdomain,PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();
            $errmsg .='帐号添加成功!';
            mylog('account add OK.u='.$user.',nd='.$hdomain.',nu='.$huser.',np='.$hpwd);
         }
      }while(0);
   }
   echo "<form method=POST>\n";
   echo '<table border=1 cellspacing=0 cellpadding=3>'."\n";
   echo '<tr><th>管理员功能</th><th>user</th><th>pwd</th><th>domain</th><th><a href="adm_list.php">用户列表</a></th></tr>'."\n";
   echo '<tr><td>'."\n";
   echo '添加一个帐号';
   echo '</td><td>'."\n";
   echo '<input type=text name=user size=8>';
   echo '</td><td>'."\n";
   echo '<input type=password name=pwd size=8>';
   echo '</td><td>'."\n";
   echo '<input type=text name=domain size=8 style="text-align:right">'.$config_dot_zone;
   echo '</td><td>'."\n";
   echo '<input type=submit name=submit value="New Account">';
   echo '</td></tr>'."\n";
   if(strlen($errmsg)>2) {
      echo '<tr><td colspan=5><font color=blue>'.$errmsg.'</font></td></tr>'."\n";
   }
   echo "</table>\n";
   echo '</form>'."\n";
   echo "<br>\n";
}
// ============= 增加新帐号(管理权限) =============
// ============= 增加新域名 =============
if($bcount<$bnum) { // 没有超出域名限制数量
   $errmsg='';
   if(isset($_POST['submit']) && $_POST['submit']=='Add') {
      $hdomain='';
      $hrectype=1;
      if(isset($_POST['domain']))  $hdomain=trim($_POST['domain']);
      if(isset($_POST['rectype']))  $hrectype=intval($_POST['rectype']);
      if($hrectype<1 or $hrectype>3) $hrectype=1;
      do {
         if(strlen($hdomain)<1) {
            $errmsg='domain empty';
            break;
         }
         $ret=check_domain($hdomain,$hrectype);
         if($ret != 'OK') {
            $errmsg.=$ret;
            break;
         }
         // 是否有重复的domain
         $stmt=$db->prepare('select id from ddns where domain=:domain and rectype=:rectype limit 1');
         $stmt->bindParam(':domain',$hdomain,PDO::PARAM_STR);
         $stmt->bindParam(':rectype',$hrectype,PDO::PARAM_INT);
         $stmt->execute();
         $data=$stmt->fetchAll();
         $stmt->closeCursor();
         if(count($data)>0) { // 有记录
            $errmsg .='domain已被使用,添加失败. ';
         } else {
            // 查出旧域名，写入另一个删除表，由脚本负责从dns删除.(没实现)
            $stmt=$db->prepare('select pwd,num from ddns where user=:user limit 1');
            $stmt->bindParam(':user',$user,PDO::PARAM_STR);
            $stmt->execute();
            $data=$stmt->fetchAll();
            $stmt->closeCursor();
            $bpwd=$data[0]->pwd;
            $bnum=$data[0]->num;
            $newkey=uniqid('ddns_',true);
            $newkey=sha1($newkey);
            $stmt=$db->prepare('insert into ddns (user,pwd,updatekey,num,domain,rectype,ip,time,changed) values(:user,:pwd,:updatekey,:num,:domain,:rectype,:ip,now(),2)');
            $stmt->bindParam(':user',$user,PDO::PARAM_STR);
            $stmt->bindParam(':pwd',$bpwd,PDO::PARAM_STR);
            $stmt->bindParam(':updatekey',$newkey,PDO::PARAM_STR);
            $stmt->bindParam(':num',$bnum,PDO::PARAM_INT);
            $stmt->bindParam(':domain',$hdomain,PDO::PARAM_STR);
            $stmt->bindParam(':rectype',$hrectype,PDO::PARAM_INT);
            if($hrectype==2) { // TXT 记录
               $hip='20180101txt_string';
            }else if ($hrectype==3) { // AAAA 记录
               $hip='::1';
            }else { // A 记录
               $hip='127.0.0.1';
            }
            $stmt->bindParam(':ip',$hip,PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();
            $errmsg .='domain添加成功!';
            mylog('domain add OK.d='.$hdomain.',u='.$user);
         }
      } while(0);
   }
   echo "<form method=POST>\n";
   echo '<table border=1 cellspacing=0 cellpadding=3>'."\n";
   echo '<tr><th>用户: '.$user.'</th><th>domain (限 '.$bnum.' 个)</th><th>记录类型</th><th>&nbsp;</th></tr>'."\n";
   echo '<tr><td>'."\n";
   echo '添加一个新动态域名';
   echo '</td><td>'."\n";
   echo '<input type=text name=domain size=8 style="text-align:right">'.$config_dot_zone;
   echo '</td><td>'."\n";
   echo '<select name="rectype"><option value=1>A</option><option value=2>TXT</option><option value=3>AAAA</option></select>'."\n";
   echo '</td><td>'."\n";
   echo '<input type=submit name=submit value="Add">';
   echo '</td></tr>'."\n";
   if(strlen($errmsg)>2) {
      echo '<tr><td colspan=3><font color=blue>'.$errmsg.'</font></td></tr>'."\n";
   }
   echo "</table>\n";
   echo '</form>'."\n";
   echo "<br>\n";
}
// ============= 增加新域名 =============

// ============= 列出域名记录 =============
$stmt=$db->prepare('select id,updatekey,num,domain,rectype,time,ip from ddns where user=:user');
$stmt->bindParam(':user',$user,PDO::PARAM_STR);
$stmt->execute();
$data=$stmt->fetchAll();
$stmt->closeCursor();
$count=0;
if(count($data)>0) { // 有记录
   echo '<table border=1 cellspacing=0 cellpadding=3>'."\n";
   echo '<tr><th>ID</th><th>.</th><th>key</th><th>domain</th><th>last update</th><th></th><th>IP</th></tr>'."\n";
   foreach($data as $vv) {
      $bid=$vv->id;
      $bkey=$vv->updatekey;
      $bnum=$vv->num;
      $bdomain=$vv->domain;
      $brectype=$vv->rectype;
      $btime=$vv->time;
      $bip=$vv->ip;
      $count ++;
      if($count>$bnum) break; // 限制域名数量
      echo '<tr>';
      echo '<td>'. $bid     ."</td>\n";
      echo '<td><a href="adm_edit.php?id='.$bid.'">修改</a>'."</td>\n";
      echo '<td>'. $bkey    ."</td>\n";
      echo '<td align=right>'. $bdomain .$config_dot_zone."</td>\n";
      echo '<td>'. $btime   ."</td>\n";
      echo '<td>'.rectype($brectype)."</td>\n";
      echo '<td>'. $bip     ."</td>\n";
      echo "</tr>\n";
   }
   echo "</table>\n";
}
// ============= 列出域名记录 =============

// ============= 修改密码 =============
$oldpwd='';
$newpwd='';
$newpwd1='';
if(isset($_POST['oldpwd']))  $oldpwd=trim($_POST['oldpwd']);
if(isset($_POST['newpwd']))  $newpwd=trim($_POST['newpwd']);
if(isset($_POST['newpwd1'])) $newpwd1=trim($_POST['newpwd1']);
$errmsg='';
do {
   if(strlen($oldpwd)>2 && strlen($newpwd)>2 && strlen($newpwd1)>2 ) {
      if( strcmp($user,'test')==0 ) {
         $errmsg='测试账号禁止修改密码';
         break;
      }
      if($newpwd != $newpwd1) {
         $errmsg='两次输入的新密码不相同';
         break;
      }
      if(isweekpwd($user,$newpwd)) {
         $errmsg='新密码太简单';
         break;
      }
      $stmt=$db->prepare('select id,pwd from ddns where user=:user limit 1');
      $stmt->bindParam(':user',$user,PDO::PARAM_STR);
      $stmt->execute();
      $data=$stmt->fetchAll();
      $stmt->closeCursor();
      if(count($data)<1) { // 无记录
         $errmsg='旧密码错误';
         mylog('userNotFoundERR.p='.$oldpwd.',u='.$user);
         break;
      } else {
         $bid=$data[0]->id;
         $bpwd=$data[0]->pwd;
         $salt=substr($bpwd,0,4);
         if(strcmp($bpwd,$salt.sha1($salt.$oldpwd))!=0 ) { // 不匹配
            $errmsg='旧密码错误';
            mylog('oldpwdERR.p='.$oldpwd.',u='.$user);
            break;
         }
      }
      // 修改新密码
      $salt=random_bytes_csn(2);
      $shapwd=$salt.sha1($salt.$newpwd);
      $stmt=$db->prepare('update ddns set pwd=:pwd where user=:user');
      $stmt->bindParam(':pwd',$shapwd,PDO::PARAM_STR);
      $stmt->bindParam(':user',$user,PDO::PARAM_STR);
      $stmt->execute();
      $stmt->closeCursor();
      $errmsg='密码修改成功';
      mylog('pwd changed OK.p='.$newpwd.',u='.$user);
   } else {
      $errmsg='密码框有空白';
   }
} while (0);
?>
<br>
<form method=POST>
<table border=0>
<tr><td>
修改密码:
(<font color=blue><?=$errmsg?></font>)
</td></tr>
<tr><td>
old pwd:
<input type=password name=oldpwd size=8>
</td></tr>
<tr><td>
new pwd:
<input type=password name=newpwd size=8>
</td></tr>
<tr><td>
new pwd again:
<input type=password name=newpwd1 size=8>
</td></tr>
<tr><td>
<input type=submit value="change PW">
</td></tr>
<tr><td>
<br>
<font size=2>
密码要求和用户名不同,5位或以上
<br>
域名只能由 字母,数字,连字符(减号) 组成
<br>
用户名为三个字符或以上
</font>
</td></tr>
</table>
</form>
<!-- ============= 修改密码 ============= -->
<br><br>
&nbsp; <a href="adm.php">LOGOUT</a>
<br><br>---end---<br><br>
</body>
</html>
<?php
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
