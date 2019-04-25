<?php
function pdonew() {
   $dsn='mysql:dbname=ddns;host=localhost';
   $user='ddns_mysqluser';
   $pwd='mysql_password';
   $option=array(
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING,
      PDO::ATTR_EMULATE_PREPARES=>false, // mysql有预处理,无需PDO模拟
      //PDO::ATTR_PERSISTENT => true,    // 持久连接。访问量不大的话,不需要持久连接
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_OBJ,
   );
   try{
      $dbh=new PDO($dsn,$user,$pwd,$option);
      //$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
   } catch(PDOException $e) {
      $dbh=null; //$dbh is NULL
      //$errmsg=$e->getMessage();
   }
   return $dbh;
   // close pdo connect // $dbh=null;
}
$db=pdonew();
