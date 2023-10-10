# 로그
## remote rsyslog
* OS : CentOS 7.7
* **전달 서버**
  * vi /etc/rsyslog.conf
  * rsyslog.conf 에 어느 서버로 어떤것을 전달할지 설정한다.
```
$ModLoad imuxsock # provides support for local system logging (e.g. via logger command)
$ModLoad imjournal # provides access to the systemd journal
$WorkDirectory /var/lib/rsyslog
$ActionFileDefaultTemplate RSYSLOG_TraditionalFileFormat
$IncludeConfig /etc/rsyslog.d/*.conf
$OmitLocalLogging on
$IMJournalStateFile imjournal.state
*.info;mail.none;authpriv.none;cron.none;local4.none;local5.none                /var/log/messages
authpriv.*                                              /var/log/secure
mail.*                                                  -/var/log/maillog
cron.*                                                  /var/log/cron
*.emerg                                                 :omusrmsg:*
uucp,news.crit                                          /var/log/spooler
local7.*                                                /var/log/boot.log
local4.notice   /var/log/.cmd.log
local5.*        @@192.168.57.2
```
  * vi /etc/rsyslog.d/deok.conf
  * 전달할 로그의 설정을 진행한다
```
$ModLoad imfile
$InputFileName /var/log/{cmd.log,secure,messages}
$InputFileTag cmd-:
$InputFileStateFile stat-file1
$InputFileSeverity info
$InputFileFacility local5
$InputRunFileMonitor
```
* $ModLoad imfile : 여러설정파일을 만든다면 rsyslog.conf에 추가해도됨
 
* **수집 서버**
* vi /etc/rsyslog.conf
  * 설정 후 주석을 제외한 설정파일 내용
```
$ModLoad imuxsock # provides support for local system logging (e.g. via logger command)
$ModLoad imjournal # provides access to the systemd journal
$ModLoad imtcp
$InputTCPServerRun 514
$WorkDirectory /var/lib/rsyslog
$ActionFileDefaultTemplate RSYSLOG_TraditionalFileFormat
$IncludeConfig /etc/rsyslog.d/*.conf
$OmitLocalLogging on
$IMJournalStateFile imjournal.state
*.info;mail.none;authpriv.none;cron.none;local4.none;local5.none                /var/log/messages
authpriv.*                                              /var/log/secure
mail.*                                                  -/var/log/maillog
cron.*                                                  /var/log/cron
*.emerg                                                 :omusrmsg:*
uucp,news.crit                                          /var/log/spooler
local7.*                                                /var/log/boot.log
local4.notice   /var/log/.cmd.log
$template FILENAME1,"/remote_log/%fromhost-ip%/%$YEAR%/system-%$MONTH%-%$DAY%.log"
local5.* ?FILENAME1
```
* $template : 수집한 로그에 대한 위치설정
* firewall-cmd --permanent --add-port=514/tcp
  * tcp 전송이기때문에 tcp만 열어줘도된다. (@ = UDP , @@ = TCP)
* 기타
  * 모든 서버 재시작
  * 만약 Server3을 추가하려면 Server1에서 했던것처럼 똑같이 추가만하고 rsyslog 재시작해주면 됨
  * 만약 udp 할꺼면 Server1/etc/rsyslog.conf 의 @@부분을 @로 바꾸고 Server2에서 ModLoad imudp와 InputUDPServerRun 514 주석을 제거한다. 그래도 안된다면 $UDPServerAddress 0.0.0.0 를 추가해준다
  * 같은 대역안에있는 서버끼리의 통신은 대략 10초가량의 딜레이발생
  * 가끔안되기도하는데 로그만 살아있다면 밀린로그들 전부 가지고온다.


## SFTP 로그
* sftp log 남기기
* vi /etc/ssh/sshd_config
```
# Subsystem       sftp     /usr/libexec/openssh/sftp-server  (기존에있던거 주석)
Subsystem      sftp     /usr/libexec/openssh/sftp-server -f local2 -l INFO
```
* vi /etc/rsyslog.conf
```
local2.* /var/log/.sftp.log
```
* 로그 파일 수동생성
```
touch /var/log/.sftp.log
```		 
* 서비스 재시작
```
systemctl restart sshd
systemctl restart rsyslog
```
* 실패했다고 나올 수 있는데 두번은 해줘야함
