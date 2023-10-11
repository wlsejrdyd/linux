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

