# Apache 개인 참고자료 정리

## URL 리플레이스
* 웹브라우저에서 www.a.com 이라는 도메인을 입력하면 www.b.com 으로 리다이렉팅 되게할때 사용
```
<VirtualHost  *:80>
  ServerName www.a.com
  Redirect / https://www.b.com
</VirtualHost>
```

## AllowOverride AuthConfig
* 클라이언트가 URL을 통해 접근할때 .htaccess 를 이용해 사용자인증을 받게할 수 있음
```
${APACHE_PATH}/bin/htpasswd -c passfilename userid
```
* 최초 설치시에만 c 옵션을 넣고하고 이후부터 c 넣으면 기존에있던 정보 다 지우니 조심
* passfilename 에 위치한 경로에 .htaccess 파일 생성필요
### .htaccess
```
#AuthType Basic
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

## IP Deny
* Include 가 되어있다면 extra/httpd-vhost.conf 에 , 아니면 httpd.conf 에 추가 해주면 됨
```
<VirtualHost *:80>
    DocumentRoot /home/hkvenus/public_html
    ServerName 디나이할IP입력
    Redirect 403 /
    ErrorDocument 403 "nope"
    UseCanonicalName Off
    UserDir Disabled
</VirtualHost>
```
