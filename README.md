## 个人动态域名系统。Personal-DDNS.
使用 bind9 &amp; php &amp; mysql , 创建个人的动态域名系统。  
Use bind &amp; php &amp; mysql , create a personal-ddns , update by an URL. Only support "A","AAAA","TXT" recorder.  

#### 条件
- 首先，需要一个有管理权的域名。
- 其次，需要一个有固定IP的服务器。

#### 设置域名 (需域名管理权)
- 在域名商系统中增加一个二级域名，和对应的IP。IP指向你的服务器。
   -  比如你的IP是"1.1.1.1"   
   -  你拥有的域名是 "mydomain.net"
   -  在域名商的解析系统中增加这两条记录。
   > ```
   >  ddns.mydomain.net  A  1.1.1.1 
   >  ddns.mydomain.net  NS ddns.mydomain.net.  
   > ```

#### 设置bind9 (需服务器root权限)
   - 我是用的 bind-chroot-9.4.4  `yum install bind-chroot bind-utils`
   - 修改/etc/named.rfc1912.zones , 在最后加上:
   > ```
   > zone "ddns.mydomain.net" IN {
   >    type master;
   >    file "dynamic/named.ddns.mydomain.net";
   >    allow-update { localhost; };
   > };
   > ```

   - 创建文件 /var/named/dynamic/named.ddns.mydomain.net 
   > ```
   > $ORIGIN .
   > $TTL 600        ; 10 minutes
   > ddns.mydomain.net      IN SOA  www.mydomain.net. rname.invalid. (
   >       1096    ; serial
   >       86400   ; refresh (1 day)
   >       3600    ; retry (1 hour)
   >       604800  ; expire (1 week)
   >       10800   ; minimum (3 hours)
   >       )
   >      NS      www.mydomain.net.
   >      A       127.0.0.1
   >      AAAA    ::1
   > $ORIGIN ddns.mydomain.net.
   > ```

#### 其他设置 (无需root权限。但需要用户权限，设置crontab)
- 看文件 [ddns/readme.php](ddns/readme.php) 

------
### 安装好之后，创建了对应的动态域名后，客户端的使用
- 如果是 A 记录 或 AAAA 记录   
   - 自动获取客户端IP(根据来源IP. A需通过ipv4，AAAA需通过ipv6访问)  
  `wget http://www.mydomain.net/ddns/ddns.php?key=xxxxxxxxx&domain=dddd`   
   - 强制指定IP(无所谓ipv4,ipv6网络)  
  `wget http://www.mydomain.net/ddns/ddns.php?key=xxxxxxxxx&domain=dddd&ip=1.1.1.1`  
  `wget http://www.mydomain.net/ddns/ddns.php?key=xxxxxxxxx&domain=dddd&ip=FC00:0:130F::9C0:876A:130B`  
   建议每10-15分钟访问一次更新链接。超过60分钟未更新,对应域名重置为"127.0.0.1" 或 "::1"   
    > 比如动态域名为 `abc.ddns.mydomain.net` 其中 `domain=dddd` 可以写为:  
    >>  `domain=abc`  
    >>  `domain=abc.ddns.mydomain.net`  

    >  其中 `xxxxxxxxx` 是创建动态域名时，生成的对应key。

- 如果是 TXT 记录   
  - 强制指定TXT内容  
  `wget http://www.mydomain.net/ddns/ddns.php?key=xxxxxxxxx&domain=dddd&ip=20181015abcdefg`  
  
