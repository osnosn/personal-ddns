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

$huser='';
if(isset($_GET['user'])) $huser=trim($_GET['user']);
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
</style>
</head>
<body>
<br>
<a href="adm_list_edit.php?user=<?=$huser?>">刷新</a>
<br><br>
user=<?=$user?>
<br>

<?php
require('isweekpwd_func.php');
require('random_bytes_csn.php');
// 查询当前用户域名限制,和已有域名数量
$stmt=$db->prepare('select num from ddns where user=:user limit 1');
$stmt->bindParam(':user',$user,PDO::PARAM_STR);
$stmt->execute();
$data=$stmt->fetchAll();
$stmt->closeCursor();
$bnum=$data[0]->num;
if($bnum<64) { // >=64 就是管理员了
   echo "<script>alert('权限错误!'); window.location='adm.php';</script>";
   echo '</body></html>';
   exit;
}

$stmt=$db->prepare('select pwd,num from ddns where user=:user limit 1');
$stmt->bindParam(':user',$huser,PDO::PARAM_STR);
$stmt->execute();
$data=$stmt->fetchAll();
$stmt->closeCursor();

if(count($data)<1) { // 无记录
   echo '用户没找到!';
   ?>
 <br><br>
 &nbsp; <a href="adm_list.php">BACK</a>
 <br><br>---end---<br><br>
 </body>
 </html>
   <?php
   exit;
}

// ========= 有记录 ======
// 先修改
// =========修改用户=========
$errmsg='';
if(isset($_POST['submit']) && $_POST['submit']=='Modify') {
   $hnum='';
   if(isset($_POST['num'])) $hnum=intval(trim($_POST['num']));
   $hpwd='';
   if(isset($_POST['pwd'])) $hpwd=trim($_POST['pwd']);
   do{
      $sql='update ddns set ';
      if(strlen($hpwd)>2) {
         if(isweekpwd($huser,$hpwd)) {
            $errmsg='密码太简单';
            break;
         }
         $salt=random_bytes_csn(2);
         $sql.='pwd=\''.$salt.sha1($salt.$hpwd)."',";
         $errmsg.='重置密码成功! ';
         mylog('pwd reset OK.np='.$hpwd.',nu='.$huser.',u='.$user);
      }
      if($hnum>0) {
         $stmt=$db->prepare($sql.'num=:num where user=:user');
         $stmt->bindParam(':num',$hnum,PDO::PARAM_INT);
         $stmt->bindParam(':user',$huser,PDO::PARAM_STR);
         $stmt->execute();
         $stmt->closeCursor();
         $errmsg.='num修改成功! ';
         mylog('num set OK.nn='.$hnum.',nu='.$huser.',u='.$user);
      }
   }while(0);
   // 重新查询记录
   $stmt=$db->prepare('select pwd,num from ddns where user=:user limit 1');
   $stmt->bindParam(':user',$huser,PDO::PARAM_STR);
   $stmt->execute();
   $data=$stmt->fetchAll();
   $stmt->closeCursor();
}
// =========修改用户=========
// =========删除用户=========
if(isset($_POST['submit']) && $_POST['submit']=='DEL') {
   $deluser='';
   if(isset($_POST['deluser'])) $deluser=trim($_POST['deluser']);
   if($deluser=='DEL') {
         //删除
         $stmt=$db->prepare('delete from ddns where user=:user');
         $stmt->bindParam(':user',$huser,PDO::PARAM_STR);
         $stmt->execute();
         $stmt->closeCursor();
         $errmsg .='user删除成功!';
         mylog('user deleted OK.du='.$huser.',u='.$user);
   }
   // 重新查询记录
   $stmt=$db->prepare('select pwd,num from ddns where user=:user limit 1');
   $stmt->bindParam(':user',$huser,PDO::PARAM_STR);
   $stmt->execute();
   $data=$stmt->fetchAll();
   $stmt->closeCursor();
}
// =========删除用户=========

if(count($data)>0) {
   $bpwd=$data[0]->pwd;
   $bnum=$data[0]->num;
} else{
   $bpwd='';
   $bnum='';
}
echo '<form method=POST>'."\n";
echo '<table border=1 cellspacing=0 cellpadding=3>'."\n";
echo '<tr><td>用户名</td><td colspan=2>'. $huser    ."</td></tr>\n";
echo '<tr><td>重置密码</td><td colspan=2><input type=text name=pwd value="" size=8>'."</td></tr>\n";
echo '<tr><td>域名个数限制</td><td colspan=2><input type=text name=num value="'.$bnum.'" size=2>'."</td></tr>\n";
echo '<tr><td colspan=3><input type=submit name=submit value="Modify"></td></tr>'."\n";
echo '<tr><td colspan=3 align=right>'."\n";
echo '删除这个用户'."\n";
echo '<input type=checkbox name=deluser value="DEL" style="font-size:12px">'."\n";
echo '<input type=submit name=submit value="DEL" style="font-size:12px">'."\n";
echo '</td></tr>'."\n";
if(strlen($errmsg)>2) {
   echo '<tr><td colspan=3>';
   echo ' (<font color=blue>'.$errmsg.'</font>)';
   echo '</td></tr>'."\n";
}
echo "</table>\n";
echo "</form>\n";

echo '<br>';
?>
<dl>
<dt>
域名限制数
</dt>
<dd>
大于等于64, 则此用户为管理员。可以创建删除其他用户。
<br>
小于等于63, 则此用户为普通用户。可以创建的域名数受此值限制。
</dd>
</dl>

<br><br>
&nbsp; <a href="adm_list.php">BACK</a>
<br><br>---end---<br><br>
</body>
</html>

<?php
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
