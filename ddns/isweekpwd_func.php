<?php
function isweekpwd($uid,$key) {
   if($uid==$key) return true;  // 不能是用户名
   if(strlen($key)<5) return true; //长度不能小于5
   $week=array('12345','123456','1234567','12345678','123456789',
            '01234','012345','0123456','01234567','012345678',
            '123123','121212','12341234',
            '11111','22222','33333','44444','55555','66666','77777','88888','99999','00000',
            '111111','222222','333333','444444','555555','666666','777777','888888','999999','000000');
   if(in_array($key,$week,true)) return true;
   if (pwd_score($key)<3) return true;
   return false;
}

function pwd_score($str) {
   $score = 0;
   if(preg_match('/[0-9]+/',$str))
   {
      $score ++;
   }
   if(preg_match('/[0-9]{3,}/',$str))
   {
      $score ++;
   }
   if(preg_match('/[a-z]+/',$str))
   {
      $score ++;
   }
   if(preg_match('/[a-z]{3,}/',$str))
   {
      $score ++;
   }
   if(preg_match('/[A-Z]+/',$str))
   {
      $score ++;
   }
   if(preg_match('/[A-Z]{3,}/',$str))
   {
      $score ++;
   }
   if(preg_match('/[_\-+=*!@#$%^&(),<>?\/:;\'"{}\[\]\\\|\.]+/',$str))
   {
      $score += 2;
   }
   //if(preg_match('/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]{3,}/',$str))
   if(preg_match('/[_\-+=*!@#$%^&(),<>?\/:;\'"{}\[\]\\\|\.]{3,}/',$str))
   {
      $score ++ ;
   }
   if(strlen($str) >= 10)
   {
      $score ++;
   }
   return $score;
}
