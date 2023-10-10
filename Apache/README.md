### URL 리플레이스
* 웹브라우저에서 a.com 이라는 도메인을 입력하면 b.com 으로 리다이렉팅 되었으면 좋겠을때 사용하는 방법
```
<VirtualHost  *:80>
  ServerName 클라이언드접속URL
  Redirect / https://리다이렉트시킬 URL주소
</VirtualHost>
```
