# 이슈 모음 (지극히 개인적인)
## bind 9.11 info logging
### 문제 : named ~~ client ~~ query failed (SERVFAIL) for ~~
* 이전 9.9 에서 9.11 로 업데이트 하고 발생하기 시작함.
* 이유도 못 찾겠는데 messages 로그를 가득가득채워서 트러블슈팅하는데 문제가 많았음
  
### 해결
* vi /etc/named.conf
```
logging {
     channel query-errors_log {
          file "/var/named/log/query-errors" versions 5 size 20m;
          print-time yes;
          print-category yes;
          print-severity yes;
          severity dynamic;
     };
     
    category query-errors {query-errors_log; };
}
```
* 사용자가 적지않아서 용량이 생각보다 많아가지고 고민이었는데 9.11 이슈가 좀 있었는지 찾긴 어려웠지만 글이 있었음
* logging 섹션을 새로만들라는게 아니라 channel query-errors_log { ~ 추가하란 것

## http Response header to forwarded https
### 문제 : http 프로토콜을 사용하면 페이지가 안나옴.
* 최상단 L7 장비가 있고 클라이언트나 내부서버에서 URL www.a.com 을 호출시 L7으로 패킷이 전송된다.
* 해당 패킷을 전송받은 L7 장비는 다시 https 로 리다이렉팅하여 사용자에게는 정상적으로 호출이 되지만 내부 서버간에 head request/resopone 작업 동작시 정상적인 호출을 받지 못하는 것을 확인.
* 서버 backend에서 강제로 WAS서버에서 API URL 을 입력했을시 http 로 들어오는것이 확인 됨.
 * curl 명령어로 확인 시 호출은 서버에서 https 로 진행했지만 웹서버에서 http로 받아오는것을 확인 함.
* 여기서 문제,
  * 보안정책상 서버내부끼리는 80 포트를 전체막아놓은상태.
  * was 서버는 iframe 태그를 통해 웹페이지에 이미지를 띄워야하는데 L7를 거칠경우 사용자는 https 로 표시되지만 L7 -> Internal 서버끼리 통신은 https -> http 다시 반환됨.
    
### 해결 : L7 에서 해당 도메인으로 물고들어오는 요청패킷의 헤더를 다시!! https 헤더를 달아 was 서버로 넘겨줘야했다.
* vi httpd.conf (apache 2.4.52)
```
RequestHeader set  X-Forwarded-Proto "https"
RequestHeader set  X-Forwarded-Port "443"
```
* RequestHeader set X-Forwarded-Proto 의 역할은 다음과 같다.
  * X-Forarded-Proto : 헤더를 추가하면 웹 서버나 웹 애플리케이션이 이 정보를 활용하여 클라이언트가 현재 HTTP 또는 HTTPS로 연결되어 있는지를 감지할 수 있습니다.
  * 이것은 HTTP에서 HTTPS로의 리디렉션을 구현하거나, 보안 관련 작업에 유용합니다.
 
## 디스크 용량 이상
### 문제 : maximal mount count reached running e2fsck
* mount count가 장치에 설정되어있는 값을 가득사용했을때 또는 초과했을때 나타나는 에러
* df -h 를 보면 어딘가 망가져 있거나 정상처럼 보일때도 간혹 있는거같다

### 해결 : 마운트 횟수 증설
* 현재 count 를 확인
```
tune2fs -l /dev/[장치명] | grep ^M
max 값보다 높다. (많이높으면 확실함)
```
* 마운트해제
```
umount /dev/[장치명]
```
* 파일시스템 체크
```
e2fsck -p /dev/[장치명]
```
* 마운트 횟수 증설
```
tune2fs -c 30 /dev/[장치명]
mount /dev/[장치명] [Mount Point]
```
