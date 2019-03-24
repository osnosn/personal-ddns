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
<a href="adm_list.php">刷新</a>
<br><br>
user=<?=$user?>
<br>

<?php
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

// ============= 列出域名记录 =============
$stmt=$db->prepare('select id,user,updatekey,num,domain,time,rectype,ip from ddns ');
$stmt->execute();
$data=$stmt->fetchAll();
$stmt->closeCursor();
if(count($data)>0) { // 有记录
   echo '<table border=1 cellspacing=0 cellpadding=3>'."\n";
   echo '<tr><th>ID</th><th>user</th><th>key</th><th>domain</th><th>n</th><th>last update</th><th>&nbsp;</th><th>IP</th></tr>'."\n";
   foreach($data as $vv) {
      $bid=$vv->id;
      $buser=$vv->user;
      $bkey=$vv->updatekey;
      $bnum=$vv->num;
      $bdomain=$vv->domain;
      $btime=$vv->time;
      $brectype=$vv->rectype;
      $bip=$vv->ip;
      echo '<tr>';
      echo '<td>'. $bid     ."</td>\n";
      echo '<td><a href="adm_list_edit.php?user='.$buser.'">'.$buser.'</a>'."</td>\n";
      //echo '<td>'. $buser   ."</td>\n";
      echo '<td>'. $bkey    ."</td>\n";
      echo '<td align=right>'. $bdomain .$config_dot_zone."</td>\n";
      echo '<td>'. $bnum   ."</td>\n";
      echo '<td>'. $btime   ."</td>\n";
      echo '<td>'. rectype($brectype)   ."</td>\n";
      echo '<td>'. $bip     ."</td>\n";
      echo "</tr>\n";
   }
   echo "</table>\n";
}
// ============= 列出域名记录 =============
?>

<br><br>
&nbsp; <a href="adm1.php">BACK</a>
<br><br>---end---<br><br>
</body>
</html>
