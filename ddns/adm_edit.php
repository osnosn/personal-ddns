<?php
session_start();
if( ! isset($_SESSION['ddns_login_user']) || strlen($_SESSION['ddns_login_user'])<3) {
   header("Content-Type: text/html;charset=utf-8");
   echo "<script>alert('未登录!'); window.location='adm.php';</script>";
   exit;
}

$user=$_SESSION['ddns_login_user'];

$uid=0;
if(isset($_GET['id'])) $uid=intval($_GET['id']);

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
</style>
</head>
<body>
<br>
<a href="adm_edit.php?id=<?=$uid?>">刷新</a>
<br><br>

<?php
$stmt=$db->prepare('select updatekey,num,domain,time,rectype,ip from ddns where user=:user and id=:id');
$stmt->bindParam(':user',$user,PDO::PARAM_STR);
$stmt->bindParam(':id',$uid,PDO::PARAM_INT);
$stmt->execute();
$data=$stmt->fetchAll();
$stmt->closeCursor();

if(count($data)<1) { // 无记录
   echo '记录没找到!';
   ?>
 <br><br>
 &nbsp; <a href="adm1.php">BACK</a>
 <br><br>---end---<br><br>
 </body>
 </html>
   <?php
   exit;
}

// ========= 有记录 ======
// 先修改
// =========修改域名=========
$errmsg=' ';
if(isset($_POST['submit']) && $_POST['submit']=='Modify') {
   $resetkey='';
   if(isset($_POST['resetkey'])) $resetkey=trim($_POST['resetkey']);
   if($resetkey=='resetkey') {
      $newkey=uniqid('ddns_',true);
      $newkey=sha1($newkey);
      $stmt=$db->prepare('update ddns set updatekey=:key where id=:id');
      $stmt->bindParam(':key',$newkey,PDO::PARAM_STR);
      $stmt->bindParam(':id',$uid,PDO::PARAM_INT);
      $stmt->execute();
      $stmt->closeCursor();
      $errmsg.='key修改成功! ';
      mylog('updatekey reset OK.id='.$uid.',u='.$user);
   }
   $rectype=1;
   if(isset($_POST['rectype'])) $rectype=intval($_POST['rectype']);
   if($rectype<1 || $rectype>2) $rectype=1;
   $domain='';
   if(isset($_POST['domain'])) $domain=trim($_POST['domain']);
   $domain=strtolower($domain);
   if(strlen($domain)>0) {
      do {
         $ret=check_domain($domain,$rectype);
         if($ret != 'OK') {
            $errmsg .= $ret;
            mylog('domainERR.d='.$domain.',u='.$user);
            break;
         }

         // 是否有重复的domain
         $stmt=$db->prepare('select id,domain from ddns where domain=:domain and rectype=:rectype limit 1');
         $stmt->bindParam(':domain',$domain,PDO::PARAM_STR);
         $stmt->bindParam(':rectype',$hrectype,PDO::PARAM_INT);
         $stmt->execute();
         $data=$stmt->fetchAll();
         $stmt->closeCursor();
         if(count($data)>0) { // 有记录
            $bid=$data[0]->id;
            $bdomain=$data[0]->domain;
            if($bid!=$uid) $errmsg .='domain已被使用,修改失败. ';
         } else {
            // 查出旧域名，写入另一个删除表，由脚本负责从dns删除.(没实现)
            $stmt=$db->prepare('update ddns set changed=2,time=now(),domain=:domain where id=:id');
            $stmt->bindParam(':domain',$domain,PDO::PARAM_STR);
            $stmt->bindParam(':id',$uid,PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            $errmsg .='domain修改成功!';
            mylog('domain changed OK.d='.$domain.',u='.$user);
         }
      } while(0);
   }

   // 重新查询记录
   $stmt=$db->prepare('select updatekey,num,domain,time,rectype,ip from ddns where user=:user and id=:id');
   $stmt->bindParam(':user',$user,PDO::PARAM_STR);
   $stmt->bindParam(':id',$uid,PDO::PARAM_INT);
   $stmt->execute();
   $data=$stmt->fetchAll();
   $stmt->closeCursor();
}
// =========修改域名=========
// =========删除域名=========
if(isset($_POST['submit']) && $_POST['submit']=='DEL') {
   $deldomain='';
   if(isset($_POST['deldomain'])) $deldomain=trim($_POST['deldomain']);
   if($deldomain=='DEL') {
      // 查询当前用户剩余域名数量
      $stmt=$db->prepare('select count(id) cnt from ddns where user=:user');
      $stmt->bindParam(':user',$user,PDO::PARAM_STR);
      $stmt->execute();
      $data=$stmt->fetchAll();
      $stmt->closeCursor();
      $bcount=$data[0]->cnt;
      if ($bcount>1) { // 是否最后一条记录
         // 查出旧域名，写入另一个删除表，由脚本负责从dns删除.(没实现)
         $stmt=$db->prepare('select domain from ddns where user=:user and id=:id');
         $stmt->bindParam(':user',$user,PDO::PARAM_STR);
         $stmt->bindParam(':id',$uid,PDO::PARAM_INT);
         $stmt->execute();
         $data=$stmt->fetchAll();
         $stmt->closeCursor();
         $bdomain=$data[0]->domain;
         //删除
         $stmt=$db->prepare('delete from ddns where user=:user and id=:id');
         $stmt->bindParam(':user',$user,PDO::PARAM_STR);
         $stmt->bindParam(':id',$uid,PDO::PARAM_INT);
         $stmt->execute();
         $stmt->closeCursor();
         $errmsg .='domain删除成功!';
         mylog('domain deleted OK.d='.$bdomain.',u='.$user);
      } else {
         $errmsg .='最后一条域名,拒绝删除!';
      }
   }

   // 重新查询记录
   $stmt=$db->prepare('select updatekey,num,domain,time,rectype,ip from ddns where user=:user and id=:id');
   $stmt->bindParam(':user',$user,PDO::PARAM_STR);
   $stmt->bindParam(':id',$uid,PDO::PARAM_INT);
   $stmt->execute();
   $data=$stmt->fetchAll();
   $stmt->closeCursor();
}
// =========删除域名=========

if(count($data)>0) {
   $bkey=$data[0]->updatekey;
   $bnum=$data[0]->num;
   $bdomain=$data[0]->domain;
   $btime=$data[0]->time;
   $brectype=$data[0]->rectype;
   $bip=$data[0]->ip;
} else {
   $bkey='';
   $bnum='';
   $bdomain='';
   $btime='';
   $brectype=1;
   $bip='';
}
echo '<form method=POST>'."\n";
echo '<table border=1 cellspacing=0 cellpadding=3>'."\n";
echo '<tr><td>domain</td><td colspan=2><input type=text name=domain value="'. $bdomain .'" size=8 style="text-align:right">'.$config_dot_zone."</td></tr>\n";
echo '<tr><td>type</td><td colspan=2><input type=hidden name=rectype value="'.$brectype.'">'. rectype($brectype)."</td></tr>\n";
echo '<tr><td>user</td><td colspan=2>'. $user    ."</td></tr>\n";
echo '<tr><td>key</td><td colspan=2>'. $bkey    ."</td></tr>\n";
echo '<tr><td>last update</td><td colspan=2>'. $btime   ."</td></tr>\n";
echo '<tr><td>IP '.($brectype==2?'(TXT)':'').'</td><td>'. $bip     .'</td><td width="180px">'."\n";
echo '删除这个域名';
echo '<input type=checkbox name=deldomain value="DEL" style="font-size:12px">';
echo '<input type=submit name=submit value="DEL" style="font-size:12px">';
echo "</td></tr>\n";
echo '<tr><td colspan=3>重置key <input type=checkbox name=resetkey value="resetkey"> &nbsp; <input type=submit name=submit value="Modify">';
echo ' (<font color=blue>'.$errmsg.'</font>)';
echo '</td></tr>'."\n";
echo "</table>\n";
echo "</form>\n";

echo '<br>';
if($brectype==1) {
   echo '自动ip更新: <br>';
   echo '&nbsp; &nbsp; ';
   echo 'http://'.$config_link.'/ddns.php?key='.$bkey.'&domain='.urlencode($bdomain);
   echo "<br>\n";
   echo '指定ip更新: <br>';
   echo '&nbsp; &nbsp; ';
   echo 'http://'.$config_link.'/ddns.php?key='.$bkey.'&domain='.urlencode($bdomain).'&ip=127.0.0.4';
   echo '<br>';
   echo '更新间隔: <br>';
   echo '&nbsp; &nbsp; ';
   echo '建议每6分钟-20分钟更新一次(访问一次更新链接)。超过60分钟未更新,对应的域名会被重置为127.0.0.1';
} else if($brectype==2) {
   echo '指定内容更新: <br>';
   echo '&nbsp; &nbsp; &nbsp; &nbsp; ';
   echo 'TXT的字符串内容写在ip参数中 , 如TXT指定为"20181015abcdefg"<br>';
   echo '&nbsp; &nbsp; ';
   echo 'http://'.$config_link.'/ddns.php?key='.$bkey.'&domain='.urlencode($bdomain).'&ip=20181015abcdefg';
} else {
   echo '指定内容更新: <br>';
   echo '&nbsp; &nbsp; ';
   echo 'http://'.$config_link.'/ddns.php?key='.$bkey.'&domain='.urlencode($bdomain).'&ip=othermsgxxxxxxxxx';
}
?>
<br><br>
&nbsp; <a href="adm1.php">BACK</a>
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
