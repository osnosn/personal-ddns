## 个人动态域名系统。Personal-DDNS.
简单又实用的动态域名管理系统  
使用 bind9 + php + mysql , 创建个人的动态域名系统。  
Use bind + php + mysql , create a personal ddns server, update by an URL. Only support "A","AAAA","TXT" recorder.  

#### 条件
- 首先，需要一个有管理权的域名。
- 其次，需要一个有固定IP的服务器。

#### 设置域名 (需域名管理权)
- 在域名商系统中增加一个二级域名，和对应的IP。IP指向你的服务器。
   -  比如你拥有的域名是 "mydomain.net"
      - 设二级域名 `dns.mydomain.net`
      - 你可以用类似 `abc.ddns.mydoman.net` , `xxx.ddns.mydoman.net` 作为你的动态域名。
   -  比如你的IP是"1.1.1.1"   
   -  在域名商的解析系统中增加这两条记录。
   > ```
   >  ddns.mydomain.net  A  1.1.1.1 
   >  ddns.mydomain.net  NS ddns.mydomain.net.  
   > ```

#### 设置bind9 (需服务器root权限)
   - 我用的是centos7   
     装 bind-chroot-9.4.4  
     `yum install bind-chroot bind-utils`   
     bind老是出漏洞，装个chroot版感觉安全点。
   - 修改配置文件 /etc/named.conf   
   > ```
   > options  {
   > listen-on port 53 { 127.0.0.1; any; };
   > listen-on-v6 port 53 { ::1; any; };
   > allow-query { localhost; any; };
   > recursion no; /* 关闭了所有的axfr,如要allow-recursion生效,必须为yes */
   > allow-recursion { localhost; };
   > allow-transfer { localhost; };
   > };
   > ```
   >  以上条目，原来有的保留。不相同的就修改。原来没有的就添加。
   - 修改/etc/named.rfc1912.zones , 在最后加上:
   > ```
   > zone "ddns.mydomain.net" IN {
   >    type master;
   >    file "dynamic/named.ddns.mydomain.net";
   >    allow-update { localhost; };
   > };
   > ```

   - 创建文件 /var/named/dynamic/named.ddns.mydomain.net 
   > 要求 named 对 dynamic/ 目录有写权限   
   > nsupdate 时会生成 named.ddns.mydomain.net.jnl   
   > named.service stop 时会改写 named.ddns.mydomain.net 文件   
   > 如果 /var/named/dynamic目录不存在，就先启动一下 service  named-chroot  start   
   > ```
   > $TTL 600        ; 10 minutes
   > @   IN  SOA  ddns.mydomain.net.  email.invalid. (
   >       1096    ; serial
   >       86400   ; refresh (1 day)
   >       3600    ; retry (1 hour)
   >       604800  ; expire (1 week)
   >       10800   ; minimum (3 hours)
   >       )
   > @     NS      ddns.mydomain.net.
   > ```
   - `service   named-chroot   stop;`  
     `service   named-chroot   start;`  
     `systemctl   enable   named-chroot;`  
   - 检查防火墙开放了 udp/53 的访问。   
     `iptables   -A   INPUT   -p  udp   –dport  53   -j  ACCEPT   #一般查询用 `   
     `iptables   -A   INPUT   -p  tcp   –dport  53   -j  ACCEPT    # axfr 用`   
   
#### 其他设置 (无需root权限。但需要用户权限，设置crontab)
- 看文件 [ddns/readme.php](ddns/readme.php)  
- 大致步骤是，
   * 把 `ddns/` 目录中的东西放到你的网站中。
   * 在MySQL中创建数据库，创建一张表，
   * 修改配置文件 pdo_new.php,  config.php
   * 创建一条 crontab 定时任务。2分钟执行一次。
   * 测试一下，就能用了。

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
  
write at 2019-02-24.   
--- end ---
