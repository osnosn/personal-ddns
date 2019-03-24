<?php
function random_bytes_csn($length,$raw=false) {
   if(function_exists('openssl_random_pseudo_bytes')) {
      $rand=openssl_random_pseudo_bytes($length);
   } else if(function_exists('mcrypt_create_iv')) {
      $rand=mcrypt_create_iv ($length, MCRYPT_DEV_URANDOM);
   } else {
      $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $rand=substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
   }
   if($raw) return $rand;
   else return bin2hex($rand);
}
/*
$time=ut();
for($i=0;$i<10000;$i++) {
   $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $rand=substr(str_shuffle(str_repeat($pool, 5)), 0, 16);
   //echo $rand;
   //echo "\n";
}
echo '----- time: '.(ut()-$time)."\n";

function ut() {
   list($usec,$sec)=explode(' ',microtime());
   return ((float)$usec +(float)$sec);
}
*/
