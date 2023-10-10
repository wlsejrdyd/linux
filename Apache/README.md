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

## http Response header to forwarded https
**상황설명** : 최상단 L7 장비가 있고 클라이언트나 내부서버에서 URL www.a.com 을 호출시 L7으로 패킷이 전송된다.
해당 패킷을 전송받은 L7 장비는 다시 https 로 리다이렉팅하여 사용자에게는 정상적으로 호출이 되지만 내부 서버간에 head request/resopone 작업 동작시 정상적인 호출을 받지 못하는 것을 확인. 
서버 backend에서 강제로 WAS서버에서 API URL 을 입력했을시 http 로 들어오는것이 확인 됨.
		1. CURL 명령어로 확인 시 호출은 서버에서 https 로 진행했지만 웹서버에서 http로 받아오는것을 확인 함.

	2. 여기서 문제,
		1. 보안정책상 서버내부끼리는 80 포트를 전체막아놓은상태.
		2. was 서버는 iframe 태그를 통해 웹페이지에 이미지를 띄워야하는데,
		3. L7를 거칠경우 사용자는 https 로 표시되지만 L7 -> Internal 서버끼리 통신은 https -> http 다시 반환됨.
	2. 해결
		1. L7 에서 해당 도메인으로 물고들어오는 요청패킷의 헤더를 다시!! https 헤더를 달아 was 서버로 넘겨줘야했다.
		2. httpd.conf 에 해당설정 추가 (apache 2.4.52)
    RequestHeader set  X-Forwarded-Proto "https"
    RequestHeader set  X-Forwarded-Port "443"
      		 
RequestHeader set X-Forwarded-Proto 의 역할은 다음과 같다.
1. X-Forarded-Proto : 헤더를 추가하면 웹 서버나 웹 애플리케이션이 이 정보를 활용하여 클라이언트가 현재 HTTP 또는 HTTPS로 연결되어 있는지를 감지할 수 있습니다. 
이것은 HTTP에서 HTTPS로의 리디렉션을 구현하거나, 보안 관련 작업에 유용합니다.
