# Apache 개인 참고자료 정리
## URL replace
* 웹브라우저에서 www.a.com 이라는 도메인을 입력하면 www.b.com 으로 리다이렉팅 되게할때 사용
```
# vi httpd.conf
<VirtualHost  *:80>
  ServerName www.a.com
  Redirect / https://www.b.com
</VirtualHost>
```

## AllowOverride AuthConfig
* 클라이언트가 URL을 통해 접근할때 .htaccess 를 이용해 사용자인증을 받게할 수 있음
```
# ${APACHE_PATH}/bin/htpasswd -c passfilename userid
```
* 최초 설치시에만 c 옵션을 넣고하고 이후부터 c 넣으면 기존에있던 정보 다 지우니 조심
* passfilename 에 위치한 경로에 .htaccess 파일 생성필요
  
### .htaccess
```
# vi {HOME}/.htaccess
AuthType Digest
AuthName "DY.JIN PAGE, Get out now!"
AuthUserFile /devp/app/apache/htdocs/deokpass
order deny,allow
deny from all
allow from ALLOW_IP
<Limit GET POST>
require valid-user
</Limit>
```
* 특별히 생성된 passfile 이나 htaccess 권한수정은 필요없다
* 그냥 안나오거나 로그인화면은 나오는데 정상적인 패스워드를 입력해도 접속이안된다면 .htaccess 의 file path를 살펴보자
* 보안스크립트 떄문에 어쩔수없이 AuthConfig 옵션을 넣고 재구동해도 서비스에는 문제는없다. .htaccess 파일을 생성했을때는 문제

## IP 입력시 403 반환
```
# vi httpd.conf
<VirtualHost *:80>
    DocumentRoot /home/hkvenus/public_html
    ServerName x.x.x.x
    Redirect 403 /
    ErrorDocument 403 "nope"
    UseCanonicalName Off
    UserDir Disabled
</VirtualHost>
```
 
## https 리다이렉트
```
# vi httpd.conf
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R,L]
```
* 도메인별로 VirtualHost 가 분리되어있다면 VirtualHost 항목안에서 별도로 설정하여 관리도 가능함.

## Downloading index.html
* AddType application/x-compress .Z 활성화되어있는지 확인
* httpd.conf 설정파일에 추가되어있는지 확인 
```
# vi httpd.conf
AddType application/x-httpd-php .php4 .php .html .htm .inc
AddType application/x-httpd-php-source .phps
```

## Section Container
### Apache 2.4 환경 OS 절대 경로
```
<Directory />
Require all denied
Require ip x.x.x.x
</Directory>
```
* 절대 경로인 "/" 아래에 있는 모든 파일 또는 디렉터리 접근을 차단한다.
  
### URL 경로 아래 있는 파일 단위
```
<Files ".html">
Require all denied
Require ip x.x.x.x
</Files>
```
* URL 밑에 있는 모든 .html 파일 접근을 차단한다. 
* 다른 제시어로는 <Files ~ "\.(htm|html|css|js|php)$"> 등이 있음.
  
### URL 도메인 아래의 경로 디렉터리
```
<Location  /aaa/bbb>
Require all denied
Require ip x.x.x.x
</Location>
```
* www.a.com/aaa/bbb 경로의 접근을 차단한다.
  
## semaphore 설정
* 접속자수 증가로 웹서버 부하가 발생하면 느려지거나 증상없이 간혈적으로 멈추곤 할때 의심해볼 수 있다.
```
# echo 250 32000 100 512 > /proc/sys/kernel/sem
# echo 512 32000 512 512 > /proc/sys/kernel/sem
# for i in `ipcs -s|grep nobody|awk '{print $2}'`;do ipcrm -s $i;done;
# mv #{APACHE_HOME}/logs/httpd.pid ${APACHE_HOME}/logs/httpd.pid.old
```

## Apache 403 Error
* Version : 2.4.x
* httpd.conf 파일의 디렉토리 접근제한 설정
```
# vi /usr/local/apache/conf/httpd.conf
<Directory />
 ~~~
 Order allow,deny
 Allow from all
 Require all granted
</Directory>
```
* include 파일 체크 : httpd.conf 파일에 include 구분중 httpd-userdir.conf 가 주석해제 되어있다면 httpd-userdir.conf 쪽 파일에도 Require all granted를 추가.
* HOME Directory 퍼미션 확인
```
# ll /home/
drwx-----x.  3 newlife  newlife     4096 2019-09-25 15:50 newlife
711 로 변경해주자
drwx--x--x.  3 newlife  newlife     4096 2019-09-25 15:50 newlife
```
