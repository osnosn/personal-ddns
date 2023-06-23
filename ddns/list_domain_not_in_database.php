<?php

if(!isset($argv) || !isset($argc)) {
   echo "Please run at command line mode.\n";
   exit;
}


$longopt=array('help','list');
$opts=getopt('hl',$longopt);

//echo '$opts=';print_r($opts); echo '$argv=';print_r($argv);
//var_dump($opts,$argc,$argv);

if( $argc<2 || count($opts)<1
      || isset($opts['h']) || isset($opts['help']) ) {
   usage($argv[0]);
   exit;
}

require('config.php');
require('rectype.php');

$doList=false;
if( isset($opts['l']) || isset($opts['list']) ) $doList=true;

if( $doList ) {
   echo "\n";
   $axfr=run_axfr();
   //var_dump($axfr[1]);
   $domains=array();
   if($axfr[0]==0) {
      foreach($axfr[1] as $v) {
         if(strlen($v)<2) continue; // 跳过空行
         if($v[0]==';') continue;   // 跳过注释
         if(strpos($v,$config_zone.".\t")===0) continue;
         if(strpos($v,$config_zone.'. ')===0) continue;
         $domains[]=$v;
      }
      //var_dump($domains);
      $nsupdate_txt='; to delete domain not in database.'."\n";   // comment
      $nsupdate_txt.='server 127.0.0.1'."\n";
      $nsupdate_txt.='zone '.$config_zone."\n";
      echo '  ------------- domain list --------------'."\n";
      require('pdo_new.php');
      foreach($domains as $v) {
         $v=stripslashes($v);
         preg_match("/(.+?)[ \t]+(.+?)[ \t]+(.+?)[ \t]+(.+?)[ \t]+(.+)/",$v,$dd); //分割
         //var_dump($dd);
         if( strpos($dd[1],$config_dot_zone.'.')!==false ) {
            $buf=substr($dd[1],0,strlen($config_dot_zone)*-1-1);
         } else $buf=$dd[1];
         //echo str_pad($buf,30,' ',STR_PAD_LEFT).': ';
         $rectype=rectype_num($dd[4]);
         if($rectype>0) {
            $stmt=$db->prepare('select id from ddns where domain=:domain and rectype=:rectype');
            $stmt->bindParam(':domain',$buf,PDO::PARAM_STR);
            $stmt->bindParam(':rectype',$rectype,PDO::PARAM_INT);
            $stmt->execute();
            $data=$stmt->fetchAll();
            $stmt->closeCursor();
            if(count($data)>0) { // 有记录
               echo str_pad('Found.('.$buf.') ',20).$v."\n";
            } else {
               echo str_pad('Notfound.('.$buf.') ',20).$v."\n";
               $nsupdate_txt.='update delete '.$dd[1].' '.$dd[4]."\n";
            }
         } else {
               echo str_pad('Unknow.('.$buf.') ',20).$v."\n";
         }
      }
      echo '  ------------- domain list end ----------'."\n";
      $nsupdate_txt.='show'."\n";
      $nsupdate_txt.='send'."\n";
      $nsupdate_txt.='quit'."\n";
      echo '  --- run  /usr/bin/nsupdate  ---'."\n";
      echo $nsupdate_txt;
      echo '  ------------- end -------------'."\n";
   } else {
      echo 'axfr ERROR!'."\n";
      exit($axfr[0]);
   }
   echo "\n";
   exit;

}

echo "\n";
echo 'Nothing to do.'."\n";
echo 'See help:'."\n";
echo '    /usr/bin/php '.$argv[0].' -h'."\n";
echo "\n";

function run_axfr() {
   global $config_zone;
   echo '  --- run dig axfr ----'."\n";
   exec('/usr/bin/dig @localhost '.$config_zone.' axfr', $output, $ret_val);
   echo '  --- end dig  --------  '."\n";
   return array($ret_val,$output);
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
        global $config_zone;
	$tmp_file='/tmp/ddns_nsupdate_txt_'.$config_zone.'.tmp';
        $fp=fopen($tmp_file,'w');
        flock($fp,LOCK_EX);
        fputs($fp,$s);
        flock($fp,LOCK_UN);
        fclose($fp);
   return $tmp_file;
}
function usage($s) {
   echo "\n";
   echo 'Usage: /usr/bin/php   '.$s.'    [-l|--list] [-h|--help]'."\n";
   echo '   or: /usr/bin/php -f '.$s.' -- [-l|--list] [-h|--help]'."\n";
   echo '         -l,--list     list domain not in database'."\n";
   echo "\n";
}
