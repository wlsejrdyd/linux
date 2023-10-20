# ISMS ...... 서버보안설정 ??
## 환경
* OS : CentOS 7.x, RHEL 7.x, Rocky 9.x, RHEL 9.x

## 배너
* /etc/motd , /etc/issue , /etc/issue.net
```
####################################################
Attention: This system is being monitored.
Illegal access may result in legal penalties.
####################################################
```

### 적용
* /etc/ssh/sshd_config
```
Banner /etc/issue
Banner /etc/issue.net
```

## 계정잠금 임계값
* faillock 사용
### 파일
* system-auth , password-auth

### 내용
```
auth  [default=die]  pam_faillock.so authfail audit deny=3 unlock_time=600
account required pam_faillock.so
```
* 시도횟수 3번 600초
* 횟수 초기화는 계정관리자(사용자)가 직업 --reset 해줘야 한다.
* retry옵션에 주어진 값은 기준은 잘 모르겠지만 연속성이 있는것으로 보여진다.
  * 1~2 스택이후 3회차에 성공하면 횟수초기화
  * 3 스택으로 계정에 lock이 걸려도 10분 후 횟수초기화
  * faillock 명령어를 통해 횟수가 여러번 찍혀있어도 위 조건만 충족 된다면 문제없던데

## 패스워드 복잡성
* 대/소문자, 특수문자, 숫자가 포함되지않는 패스워드는 입력이 불가능하도록 설정
### 설정
* /etc/security/pwquality.conf
```
minlen = 9
dcredit = -1
ucredit = -1
lcredit = -1
ocredit = -1
```
* /etc/pam.d/system-auth , /etc/pam.d/password-auth
```
password  requisite  pam_pwquality.so enforce_for_root
```

## 미사용 계정삭제
```
daemon:x:2:2:daemon:/sbin:/sbin/nologin
# userdel daemon

adm:x:3:4:adm:/var/adm:/sbin/nologin
# userdel -rf adm

lp:x:4:7:lp:/var/spool/lpd:/sbin/nologin
# yum remove cups -y && userdel -rf lp

sync:x:5:0:sync:/sbin:/bin/sync
# userdel sync

shutdown:x:6:0:shutdown:/sbin:/sbin/shutdown
# userdel shutdown

halt:x:7:0:halt:/sbin:/sbin/halt
# userdel halt

operator:x:11:0:operator:/root:/sbin/nologin
# userdel operator

games:x:12:100:games:/usr/games:/sbin/nologin
# userdel -rf games && groupdel games

ftp:x:14:50:FTP User:/var/ftp:/sbin/nologin
# userdel -rf ftp

systemd-network:x:192:192:systemd Network Management:/:/sbin/nologin
# userdel systemd-network

polkitd:x:999:998:User for polkitd:/:/sbin/nologin
# 삭제못함, 시스템 인증 관련 데몬의 계정. 서비스도 살아있음. (rocky는 없네)

libstoragemgmt:x:998:996:daemon account for libstoragemgmt:/var/run/lsm:/sbin/nologin
# 서비스 살아있어서 삭제안하겠음. 9버전대 os에는 없네... 명확하지않으니 삭제보류

colord:x:997:995:User for colord:/var/lib/colord:/sbin/nologin
# centos7 서비스 살아있어서 삭제안하겠음. rhel 7 9버전대 os에는 없네... 명확하지않으니 삭제보류

rpc:x:32:32:Rpcbind Daemon:/var/lib/rpcbind:/sbin/nologin
# userdel -rf rpc

saned:x:996:994:SANE scanner daemon user:/usr/share/sane:/sbin/nologin
# userdel -rf saned

saslauth:x:995:76:Saslauthd user:/run/saslauthd:/sbin/nologin
# yum remove cyrus-sasl -y && userdel -rf saslauth


abrt:x:173:173::/etc/abrt:/sbin/nologin
setroubleshoot:x:994:991::/var/lib/setroubleshoot:/sbin/nologin
rtkit:x:172:172:RealtimeKit:/proc:/sbin/nologin
pulse:x:171:171:PulseAudio System Daemon:/var/run/pulse:/sbin/nologin
radvd:x:75:75:radvd user:/:/sbin/nologin
unbound:x:992:987:Unbound DNS resolver:/etc/unbound:/sbin/nologin
qemu:x:107:107:qemu user:/:/sbin/nologin
tss:x:59:59:Account used by the trousers package to sandbox the tcsd daemon:/dev/null:/sbin/nologin
usbmuxd:x:113:113:usbmuxd user:/:/sbin/nologin
geoclue:x:991:985:User for geoclue:/var/lib/geoclue:/sbin/nologin
gluster:x:990:984:GlusterFS daemons:/run/gluster:/sbin/nologin
gdm:x:42:42::/var/lib/gdm:/sbin/nologin
rpcuser:x:29:29:RPC Service User:/var/lib/nfs:/sbin/nologin
nfsnobody:x:65534:65534:Anonymous NFS User:/var/lib/nfs:/sbin/nologin
gnome-initial-setup:x:989:983::/run/gnome-initial-setup/:/sbin/nologin
sshd:x:74:74:Privilege-separated SSH:/var/empty/sshd:/sbin/nologin
avahi:x:70:70:Avahi mDNS/DNS-SD Stack:/var/run/avahi-daemon:/sbin/nologin
postfix:x:89:89::/var/spool/postfix:/sbin/nologin
ntp:x:38:38::/etc/ntp:/sbin/nologin
```


## 기억나는대로 작성함.
1. hosts 600
2. su 4750
3. pam.d/su auth required pam_wheel.so use_uid # wheel group 에 속해있는 계정은 su 명령어를 통해 root 패스워드 검증을 통과 한 후 로그인가능
4. profile TMOUT=300
5. sshd_config permitrootlogin no
6. rsyslog.conf 640
7. remove at
8. stop&disable cupsd, rpcbind, postfix # 사용하는 서버예외
9. /etc/skel other - read
10. tcp_wrapper 활성화
11. world writeable 확인
```
# var1="/tmp /bin /sbin /etc /var /home"
# for i in ${var1} ; do find ${var1} -type f -perm -2 -exec ls -alLd {} \; | awk '{print $1 " : " $3 " : " $4 " : " $9}' ; done 
```

