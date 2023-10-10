# Apache 개인 참고자료 정리

## URL 리플레이스
* 웹브라우저에서 a.com 이라는 도메인을 입력하면 b.com 으로 리다이렉팅 되게할때 사용
```
<VirtualHost  *:80>
  ServerName a.com
  Redirect / https://b.com
</VirtualHost>
```

## AllowOverride AuthConfig
* 클라이언트가 URL을 통해 접근할때 .htaccess 를 이용해 사용자인증을 받게할 수 있음
* $APACHE_PATH/bin/htpasswd -c [Directory_PATH]/passfilename userid
* 최초 설치시에만 c 옵션을 넣고하고 이후부터 c 넣으면 기존에있던 정보 다 지우니 조심
* 입력한 Dir PATH 로 이동 후 .htaccess 를 만듦
### Config
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
* 보안스크립트 떄문에 어쩔수없이 AuthConfig 옵션을 넣고 재구동해도 서비스에는 문제는없다. .htaccess 파일을 생성했을때는 문제지만
 
### Options 
* 지시자 보안취약 옵션들
  * Indexes (디렉토리 리스팅)
    * 사용자가 정상적으로 url 을 입력했을경우 3가지 경우가 발생한다
     * 첫째로 정상적인 웹 호출요청으로 인한 index.html php 등 뭐든 정상출력
     * 둘째로 해당 파일이 호출이 안됐을때 디렉터리 구조를 보여줌
      * 시스템에 대한 정보획득가능
      * 불필요한 정보노출 (백업데이터, CGI소스코드등)
     * 셋째로 에러페이지 출력.
    * FollowSymLinks (심볼릭 링크)
     * 심볼릭링크를 이용해 파일시스템에 접근가능하도록 설정되어있는 서버는 주의.
     * 최상위 디렉터리에 링크가 걸리게 되면 passwd 등 보안에 주요파일들의 정보 취득이 가능하다고 함. 서비스 구동 사용자 권한으로 모든 파일에 접근할 수 있다.
			 
* ServerTokens 불필요한 서버정보 header 에서 숨기기
* 	Prod (ProductOnly)
* 	Apache 만 보여줌 (버전도 안보여주고 Apahce, 딱이거만 보여줌)
 * Min (Minimal)
  * Apache 버전까지 표기
 * OS
  * Apache 버전과 운영체제를 보여줌
 * Full
  * 모두 보여줌.
