<?php

if(!isset($argv) || !isset($argc)) {
   echo "Please run at command line mode.\n";
   exit;
}


$longopt=array('help','update');
$opts=getopt('huwt:',$longopt);

//echo '$opts=';print_r($opts); echo '$argv=';print_r($argv);
//var_dump($opts,$argc,$argv);

if( $argc<2 || count($opts)<1
      || isset($opts['h']) || isset($opts['help']) ) {
   usage($argv[0]);
   exit;
}

$doUpdate=false;
if( isset($opts['w'])) $doUpdate=true;
$updateTime=0;
if( isset($opts['t'])) {
   if(count($opts['t'])>1) {
      // 如果有多个值,只取最后一个值
      $updateTime=$opts['t'][count($opts['t'])-1];
   } else {
      $updateTime=$opts['t'];
   }
   $updateTime=intval($updateTime);
}
if($updateTime<10) $updateTime=600;

require('config.php');
require('rectype.php');

if( isset($opts['u']) || isset($opts['update']) ) {
   require('pdo_new.php');
   // 超过60分钟没更新的,IP不是127.0.0.1或不是::1 , 全部改为127.0.0.1和::1
   if(! $doUpdate) {
      // 列出超过60分钟没更新的 A 和 AAAA 记录
      $stmt=$db->prepare('select id,domain,time,rectype,ip,changed from ddns where (rectype=1 or rectype=3) and ip!=\'127.0.0.1\' and timestampdiff(SECOND,time,now())>3600');
      $stmt->execute();
      $data=$stmt->fetchAll();
      $stmt->closeCursor();
      if(count($data)>0) { // 有记录
         echo '-------expired recorders----------------------------------'."\n";
         echo '  ID ,    last active time, type,              IP, chged, Domain '."\n";
         foreach($data as $vv) {
            $bid=$vv->id;
            $bdomain=$vv->domain;
            $btime=$vv->time;
            $brectype=$vv->rectype;
            $brectype=rectype($brectype);
            $bip=$vv->ip;
            $bchged=$vv->changed;
            echo str_pad($bid,5,      ' ',STR_PAD_LEFT) .',';
            echo str_pad($btime,20,   ' ',STR_PAD_LEFT) .',';
            echo str_pad($brectype,5, ' ',STR_PAD_LEFT) .',';
            echo str_pad($bip,16,     ' ',STR_PAD_LEFT) .',';
            echo str_pad($bchged,5,   ' ',STR_PAD_LEFT) .',';
            echo ' '.$bdomain;
            echo "\n";
         }
         echo '-------expired recorders----------------------------------'."\n";
         echo ' --- set this expired recorders ip=127.0.0.1 ---'."\n";
      }
   } else {
      // A 记录
      $stmt=$db->prepare('update ddns set ip=\'127.0.0.1\',changed=2,time=now() where rectype=1 and ip!=\'127.0.0.1\' and timestampdiff(SECOND,time,now())>3600');
      $stmt->execute();
      $stmt->closeCursor();
      // AAAA 记录
      $stmt=$db->prepare('update ddns set ip=\'::1\',changed=2,time=now() where rectype=3 and ip!=\'::1\' and timestampdiff(SECOND,time,now())>3600');
      $stmt->execute();
      $stmt->closeCursor();
   }

   // 更新指定范围内的动态域名到 DNS 服务器
   $stmt=$db->prepare('select id,domain,time,rectype,ip,changed from ddns where changed>0 and timestampdiff(SECOND,time,now())< :time');
   // select timestampdiff(second,time,now()),now()-time,now()-0,time-0,now(),time,(3600*2+60*25) from ddns
   $stmt->bindParam(':time',$updateTime,PDO::PARAM_INT);
   $stmt->execute();
   $data=$stmt->fetchAll();
   $stmt->closeCursor();
   if(count($data)>0) { // 有记录
      $nsupdate_txt='; update ip to DNS server'."\n";  // comment
      $nsupdate_txt.='server 127.0.0.1'."\n";
      $nsupdate_txt.='zone '.$config_zone."\n";
      $logmsg='';
      if(! $doUpdate) {
         echo "\n";
         echo '-------active recorders-----------------------------------'."\n";
         echo '  ID ,    last active time, type,              IP, chged, Domain '."\n";
      }
      foreach($data as $vv) {
         $bid=$vv->id;
         $bdomain=$vv->domain;
         $btime=$vv->time;
         $brectype=$vv->rectype;
         $brectype=rectype($brectype);
         $bip=$vv->ip;
         $bchged=$vv->changed;
         $nsupdate_txt.='update delete '.$bdomain.$config_dot_zone.' '.$brectype."\n";
         if(strcmp($brectype,'TXT')==0) {
            $nsupdate_txt.='update add '.$bdomain.$config_dot_zone.' 60 '.$brectype.' '.$bip."\n";
         } else { // A 和 AAA 记录
            $nsupdate_txt.='update add '.$bdomain.$config_dot_zone.' 600 '.$brectype.' '.$bip."\n";
         }
         $logmsg.=$bdomain.'='.$bip.',';
         if(! $doUpdate) {
            echo str_pad($bid,5,      ' ',STR_PAD_LEFT) .',';
            echo str_pad($btime,20,   ' ',STR_PAD_LEFT) .',';
            echo str_pad($brectype,5, ' ',STR_PAD_LEFT) .',';
            echo str_pad($bip,16,     ' ',STR_PAD_LEFT) .',';
            echo str_pad($bchged,5,   ' ',STR_PAD_LEFT) .',';
            echo ' '.$bdomain;
            echo "\n";
         }
      }
      if(! $doUpdate) {
         echo '-------active recorders-----------------------------------'."\n";
      }
      //$nsupdate_txt.='update abc '.$bdomain.$config_dot_zone.' 600 A '.$bip."\n";
      $nsupdate_txt.='send'."\n";
      $nsupdate_txt.='quit'."\n";
      if(! $doUpdate) {
         echo $nsupdate_txt;
         echo '--- you can run /usr/bin/nsupdate ---'."\n";
         echo '--- update chged=chged-1 from ddns where chged>0 ---'."\n";
         echo "\n";
      } else {
         $tmpfile=writeToTmpFile($nsupdate_txt);
         $ret_val=run_nsupdate($tmpfile);
         if($ret_val>0) {
            echo 'nsupdate ERR'."\n";
            mylog('ERR.'.$logmsg);
         } else {
            $stmt=$db->prepare('update ddns set changed=changed-1 where changed>0');
            $stmt->execute();
            $stmt->closeCursor();
            mylog('OK.'.$logmsg);
         }
         delTmpFile($tmpfile);
      }
   } else {
      if(! $doUpdate) {
         echo 'No records found!'."\n";
      }
      // Nothing to do
   }
   if(isset($ret_val) && $ret_val>0) exit(10);
   exit;
}

echo "\n";
echo 'Nothing to do.'."\n";
echo 'See help:'."\n";
echo '    /usr/bin/php '.$argv[0].' -h'."\n";
echo "\n";


function delTmpFile($tmpfile) {
   unlink($tmpfile);
   clearstatcache();
   if(file_exists($tmpfile)) {
      echo 'Delete '.$tmpfile." Fail.\n";
   } else {
      //echo 'Delete '.$tmpfile." OK.\n";
   }
}
function run_nsupdate($tmpfile) {
   exec('/usr/bin/nsupdate '.$tmpfile.' 2>&1', $output, $ret_val);
   if(count($output)>0) {
      //echo '/usr/bin/nsupdate '.$tmpfile."\n";
      echo '--- run nsupdate  -------'."\n";
      echo implode("\n",$output);
      echo "\n";
      echo '--- end nsupdate  -------  '."\n";
   }
   return $ret_val;
}
function writeToTmpFile($s){
        $tmp_file='/tmp/ddns_nsupdate_txt.tmp';
        $fp=fopen($tmp_file,'w');
        flock($fp,LOCK_EX);
        fputs($fp,$s);
        flock($fp,LOCK_UN);
        fclose($fp);
   return $tmp_file;
}
function usage($s) {
   echo "\n";
   echo 'Usage: /usr/bin/php   '.$s.'    [-u|--update] [-w] [-t SEC] [-h|--help]'."\n";
   echo '   or: /usr/bin/php -f '.$s.' -- [-u|--update] [-w] [-t SEC] [-h|--help]'."\n";
   echo '         -u,--update     update ddns for test,no realy update. (without -w)'."\n";
   echo '         -w              update ddns (with -u)'."\n";
   echo '         -t SEC          only update records within SEC seconds.(default 600)'."\n";
   echo "\n";
}
function mylog($s){
        global $log_file;
        $fp=fopen($log_file,'a');
        flock($fp,LOCK_EX);
        fputs($fp,date('Y-m-d.H:i:s ').'cmd_line ddns_update:');
        fputs($fp,$s);
        fputs($fp,"\n");
        flock($fp,LOCK_UN);
        fclose($fp);
}
