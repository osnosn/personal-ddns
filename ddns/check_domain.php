<?php
function check_domain($domain,$type) {  // 1=A,2=TXT,3=AAAA,4=CNAME
   // 检查域名前后不能有小数点,不能是连字符
   $domainLen=strlen($domain);
   if($domain{0}=='.' || $domain{$domainLen-1}=='.'
         || strpos($domain,'..')!==false
         || $domainLen>16 ) {
      // domain 长度不超过63,因数据库字段限制,所以限制16
      return 'domain不合法,或太长,修改失败. ';
   }
   if($type==2) { // TXT记录,字符限制宽松些
      if(preg_match('/^[0-9a-z_`~!@#$%^&*()\[\]{}|+=<>,?\/.-]+$/',$domain)<1) {
         return 'domain中字符不合法,添加失败. ';
      }
   } else { // A或AAAA或CNAME记录
      // 域名不能有非法字符
      if(preg_match('/^[0-9a-z.-]+$/',$domain)<1) {
         // 只能由'a-z0-9' 和 '-' 组成,不区分大小写
         return 'domain中字符不合法,修改失败. ';
      }
   }
   if(strpos($domain,'.-')!==false || strpos($domain,'-.')!==false
         || $domain{0}=='-' || $domain{$domainLen-1}=='-' ) {
      // 减号 不能在任意一段的头或者尾
      return 'domain不合法,连字符位置错误,修改失败. ';
   }
   return 'OK';
}
