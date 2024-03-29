# 이슈 모음 (지극히 개인적인)
## bind 9.11 info logging
### 문제 : named ~~ client ~~ query failed (SERVFAIL) for ~~
* 이전 9.9 에서 9.11 로 업데이트 하고 발생하기 시작함.
* 이유도 못 찾겠는데 messages 로그를 가득가득채워서 트러블슈팅하는데 문제가 많았음
  
### 해결
```
# vi /etc/named.conf
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
* apache 2.4.52
```
# vi httpd.conf
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
# tune2fs -l /dev/[장치명] | grep ^M
max 값보다 높다. (많이높으면 확실함)
```
* 마운트해제
```
# umount /dev/[장치명]
```
* 파일시스템 체크
```
# e2fsck -p /dev/[장치명]
```
* 마운트 횟수 증설
```
# tune2fs -c 30 /dev/[장치명]
# mount /dev/[장치명] [Mount Point]
```

## 서버의 모든패킷의 순단현상이 확인 됨
### 문제 : kernel : nf_conntrack: table full, dropping packet.
* messages log에 "kernel : nf_conntrack: table full, dropping packet." 로그가 찍힌다.
* 증상은 네트워크가 간혈적으로 끊긴다. 그래서 확인하러 서버에 들어가도 중간에 멈추거나 연결이 끊기고 어느 로그에서도 확인이 잘 안됨.

### 해결 : nf_conrack_max 값 증가
```
# echo "0" > /proc/sys/net/nf_contrack_max
```
* 평상시 트래픽이 몰리는 서버가 아닌데 이와같은 현상이 발생한다면 원인을 찾아서 막는것이 올바르다

## 오라클 exadata 서버 싱글모드
### 문제 : 실수로 ssh 접근이 전부 막혀서 원격접속이 안된다.
* 보안팀의 요청으로 Oracle DB 서버인 Exadata 서버의 ISMS 보안작업을 진행하다 SSH 접근이 막혀버림
* 도저히 원격으로는 작업을 이어나갈 수 없을거같아서 콘솔로 붙은 후 작업을 해야겠다 생각함. 그런데 원래 서버실에서 IDC 서버실로 이전하며 랙을 잠궜는데 보관열쇠를 서버실이 아닌 사무실에 보관하고있었음.
* 열쇠를 전달하러 택시를 타고 서버실로 들어간 후 콘솔로 붙어서 확인해봤는데 콘솔로그인이 안됨. 뭐지 싶어서 다음날 복제서버로 접근하여 확인해보니 콘솔로그인을 막아놓았음. (이때부터 쎄했음)
* 다시 회의를 잡고 싱글모드에 진입하여 작업원복을 해야겠다며 작업일정 잡아달라고 함. (작업전 백업은 되도록 하려했는데 참 다행..)
* 오후 5시 밖에 안될거같다며 어쩔거냐고 물어보길래 견적을 내보니 싱글부팅이야 밥먹듯이 했어서 30분컷 가능하니 그렇게 하자고하고 IDC로 바로 출발.

### 해결 : 오라클 매뉴얼 이고뭐고 내가 테스트하고 처리함
랙잠금 풀고, 어렵게 모니터 키보드 연결하고 부팅을 했는데 오라클서버특성인지는 모르겠는데 부팅이 오질라게 느려서 10분정도걸리고 부트이미지 선택하는 화면도 다름. 얼타다가 부팅만 2~3번진행하면서 30분 그냥 날림.
관련 작업으로 대기하고있던 인원에게 상황설명하고 부트이미지가 어떤건지 찾았는데 이미지에 비밀번호 걸어놨음 ㅋㅋㅋㅋㅋㅋㅋ 3분 고민하다가 바로 관련작업자들 초대되어있는 단톡방에 케이스오픈하고 패스워드 수소문하기 시작함.
20분 지나니 패스워드 하나다 얻어걸렸음. 뚫고 싱글부팅하려했는데 행걸린것마냥 멈추고 진입이 안됨. 그 와중에 6시넘어서 퇴근해야한다고하는거 바짓가랑이 붙잡고 30분만 달라그랬더니 거절당해서 집으로돌아옴.
싱글모드 진입 작업하는데 부트옵션에 난생처음보는 옵션이있어서 신기해서 사진찍어놓은게 있는데 , 집에오자마자 씻고 내 PC에 같은 버전의 OS 가상화로 설정하고 부트옵션 설정하고 똑같이 작업하니 똑같이 멈춤.
열받아서 수십번씩 옵션하나씩 지우며 부팅했는데 console=?? 이라는 옵션 두개 지우니까 잘되는거 확인하고 다시 작업일정을 잡으려니. 오라클에 공식 SR 신청해서 답변 받으면 그때하자고하면서 내말은 안믿고 시간만 질질끌기 시작함.
한달 후 SR 도착하고 읽어보니 뭔 이상한 답변이왔길래. 기다리다가지쳐서 SR대로 진행하겠다고 뻥치고 내가 집에서 테스트한대로 진행해서 정사적으로 원복함.

## 리눅스 V3 설치 이후
### 문제 : rsyslog.conf 가 변조 되고있었음
* 언제부턴가 /etc/rsyslog.conf 그리고 /etc/logrotate.d/syslog 파일에 "kern.* /var/log/kern.log" 설정이 자동으로 두줄씩 추가가 됨
* 처음발견했을때는 syslog 설정하기위해서 파일을 열어봤는데 대여섯줄 추가가되어있길래 내가 졸린상태로 뭔가 잘못 추가한줄알았는데 다음날에보니 두줄이 또 추가가 되어있음
* stat 명령어를 이용해 파일이 수정 된 시간을 알아내고 messages 로그에 해당 시간대 로그를 추적하기 시작했음
* messsages 에는 rsyslog 가 두번. 재시작 된 로그 말고는 다른 로그는 없었고, 보지도 못하는 journalctl 을 이용해 해당 시간대로 검색해보니... 역시 재시작했다는 내용뿐임. (journalctl -o verbose)
* 몇일간 찾다가 포기하고 잊고있다가 다시 한번 처음부터 다시 시작... 결과는 위와 같았고 별기대없이 /var/log 경로 밑의 모든 로그파일에 grep을 거니 ahnlab...?? 관련된 라이브러리 로그들이 출력되기 시작함...
* 통합 syslog 를 생성해서 설정을 해두었기에 "signal 15" 로 grep 해서 검색해보니 다행히 처음 rsyslog 를 재시작했을때부터 기록이 남아있는거같았다. 추측이긴하지만 충분히 가능성있는 이야기일 수 있는 이유는 cmd.log 를 남기게 설정해놨기때문에 v3 설치 일자와 비교해볼 수 있었음.
* v3 설치 날짜와 rsyslog 가 저녁에 두번씩. 자동 재시작 되는 일자가 일치하는것을 서버 4대를 통해 신빙성을 높이고. 해당 자료를 또 직접 수집하여 v3로 케이스 오픈 요청함.
* ... 보안이 중요하긴한데.그건알겠는데 왜. 관리할 자신없다고 얘기했는데도! 요청한 사람은 손을 뗄려고 하는지 너무 무책임해보여서 이해가 안간다.
### 해결 : 버전 업데이트
* 문제가되던 버전에서 내부망 서버들의 업데이트가 안되고있었기때문에 정책허용해주니 업데이트 받고 이후에 rsyslog.conf 변조가 발생하지 않았다..

## systemctl --user
### 문제 : "failed to connect to bus no medium found"
* podman service 자동실행작업을 위해 systemctl --user enable ~~ 하게되면 "failed to connect to bus no medium found" 에러가 나오며 등록되지않는다.

### 해결 : 사용자 프로필 또는 export 활용. 
```
# vi {HOME}/.bash_profile
XDG_RUNTIME_DIR=/run/user/$(id -u)
```

## 리눅스 V3 설치 이후
### 문제 : system warning log에 AhnLab error 출력에 대하여..
* 버전업데이트 이후 rsyslog 및 syslog 파일의 중복설정값 추가(이하 변조)는 사라졌지만 시스템로그가 새롭게 생성되었음.
```
Oct 10 23:47:56 SERVER_HOSTkernel: <asm>:[INFO]Filter atamptl unregister
Oct 10 23:47:56 SERVER_HOSTkernel: <atamptl>:[INFO]Unloaded atamptl
Oct 10 23:47:56 SERVER_HOSTkernel: <asm>:[INFO]AhnLab. Security Module Version 3.4.3.8 unloaded
Oct 10 23:48:02 SERVER_HOSTkernel: Request for unknown module key 'AhnLab, Inc.: AhnLab Code Signing - 2022.001: a0ee2da1e4101391b6fc913cad09f966cd019fa8' err -11
Oct 10 23:48:02 SERVER_HOSTkernel: <asm>:[INFO]AhnLab. Security Module Version 3.4.3.8
Oct 10 23:48:02 SERVER_HOSTkernel: Request for unknown module key 'AhnLab, Inc.: AhnLab Code Signing - 2022.001: a0ee2da1e4101391b6fc913cad09f966cd019fa8' err -11
Oct 10 23:48:02 SERVER_HOSTkernel: <atamptl>:[INFO]Loaded atamptl 2.5.0.1
Oct 10 23:48:02 SERVER_HOSTkernel: <atamptl>:[INFO]ASM Version 3.4.3.8
Oct 10 23:48:02 SERVER_HOSTkernel: <asm>:[INFO]Filter atamptl register
```
### 해결(이 아닌조치) : warning 조회시 예외처리
* 유지보수 중계업체 엔지니어 답변 : **secure boot 활성화가 되지않아 발생하는 로그이며, v3동작에 영향은 없을것.**
* v3 epp 서버가 있으니 관리서버에서 내가모르는 기능이있다면 일괄적용으로 처리가 안되느냐고 물어봤지만 무응답. warning log 읽을때 관련 로그를 보이지않게 예외처리를 진행했고, 앞으로 별도확인하지않는 이상 warning system log에서 위와 관련된 로그를 볼 수 없게되었다.
