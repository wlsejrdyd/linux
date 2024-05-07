# 기타 참고자료 모음
## autofs 테스트
### 환경
```
서버 2대
CentOS 7.x
Node1 (NFS 제공 서버)
Node2 (autofs 서버)
```

### 작업
* node1 서버에서 nfs-utils 설치
* node2 서버에서 nfs-utils 및 autofs 설치한다.
* node1 /etc/export 에 마운트하고자하는 디렉터리와, IP 및 권한을 설정한다.
    * ex) /nfs_test/node1_dir 192.168.1.1(rw,sync,no_root_squash)
* node1 서버에서 nfs-server 서비스를 시작한다. 활성화도 하든지.
* node2 서버에서 mount 명령어로 nfs 붙는지 먼저 확인하는것도 좋을거같다.
* node2 서버 /etc/auto.master.d 디렉터리로 이동해서 무슨무슨매핑 파일을 만든다. 파일명 형식은 아래와 같다.
	* ex) test.autofs
	* 내용 : ex ) /- /etc/test.test
* node2 에서 autofs 재시작해준다.
* df 를 본다. 없다. 마운트 된 경로로 들어가면 그때부터 마운트된다. 이상하다.
	* umount 로 해제해도 마운트 된 디렉터리에 액션이 발생하면 자동으로 잡힌다. 해제도 안된다. 서비스꺼야함.
	* 참고로 node2 서버에서 디렉터리를 설정하지않아도 자동으로 생성된다. 

## bash 취약점 확인
```
env x='() { :;}; echo vulnerable' bash -c "echo this is a test"
```
* vulnerable 구문이 보일시 업데이트 요망

## CentOS XRDP
### 환경
```
CentOS 6.x
```

### 설치
```
yum groupinstall "X Window System" "Desktop"
sed –i "s/id\:3/id\:5/g" /etc/inittab
/sbin/chkconfig —list | grep "5:on" | awk '{print $1}' | while read service ;do /sbin/chkconfig —level 5 &service off ;done
/sbin/chkconfig —list | grep "3:on" | awk '{print $1}' | while read service ;do /sbin/chkconfig —level 5 &service on ;done
yum install tigervnc-server
yum install xrdp
```

### 설정
```
cat << EOF >> /etc/sysconfig/vncservers
VNCSERVERS="2:root"
VNCSERVERARGS[2]="-geometry 800x600 -nolisten tcp -localhost"VNCSERVERS="2:root"
EOF
vncpasswd
방화벽 3389 포트 오픈
service vncserver restart
service xrdp restart
```
* 윈도우에서 원격데스크톱연결 프로그램을 사용해서 접속 테스트 진행

* 한글이 깨진다면
```
yum groupinstall korean-support -x xorg-x11-server-Xorg
fc-cache 
```

## 이더넷 이름 변경
```
# vi /etc/default/grub
GRUB_CMDLINE_LINUX 라인 맨뒤에 "net.ifnames=0 biosdevname=0" 추가
grub2-mkconfig -o /boot/grub2/grub.cfg
ifcfg 파일 수정 후 재부팅
```

## SSL CRT, KEY 파일비교
```
dom1="{DOM}"
openssl rsa -noout -modulus -in ${dom1}.key | openssl md5
openssl x509 -noout -modulus -in ${dom1}.crt | openssl md5
```

## 필수 설치 유틸리티
```
yum install kernel kernel-devel bash openssl openssl-devel gcc gcc-c++ net-tools vim bash-completion sysstat yum-utils wget nmap-ncat lsof tcpdump rsync
```
* 물론 OS 첫 설치 이후이며, 서비스중인 운영서버에 반영할 시 커널업데이트같은 운영에 민감한 패키지도 같이 업데이트 되므로 주의.

## 방화벽 IP 별 정책 추가
```
firewall-cmd --add-rich-rule='rule family="ipv4" source address=192.168.57.1/32 port port="22" protocol="tcp" accept' --zone=public --permanent
```

## history 시간표시
```
cat <<EOF >> /etc/profile
HISTTIMEFORMAT="%Y-%m-%d [%H:%M:%S] "
export HISTTIMEFORMAT
EOF
```

## ipv6 disable
```
echo "install ipv6 /bin/true" > /etc/modprobe.d/disable_ipv6.conf
echo "net.ipv6.conf.default.disable_ipv6=1" >> /etc/sysctl.conf
echo "net.ipv6.conf.all.disable_ipv6 = 1" >> /etc/sysctl.conf
echo "net.ipv6.conf.lo.disable_ipv6 = 1" >> /etc/sysctl.conf
sysctl -p
vi /etc/default/grub
  GRUB_CMDLINE_LINUX="$GRUB_CMDLINE_LINUX ipv6.disable=1"
grub2-mkconfig -o /boot/grub2/grub.cfg
init 6
```

## filesystem 선택기준
* 용도 및 성능: 파일 시스템의 사용 용도와 필요한 성능에 따라 선택할 수 있습니다. XFS는 대용량 파일 및 데이터베이스 작업에 강점을 가지며, 대부분의 입출력 작업에서 높은 성능을 발휘합니다. ext4는 다양한 용도에서 잘 작동하지만, 중소규모의 파일 시스템에서 더 널리 사용됩니다.
큰 파일 지원: XFS는 대용량 파일 처리에 최적화되어 있습니다. 따라서 매우 큰 파일을 다루는 시나리오에서 XFS를 고려할 수 있습니다.
* 확장성: XFS는 동적 크기 조정을 통해 파일 시스템 크기를 확장할 수 있는 기능을 가지고 있습니다. 대용량 시스템에서 XFS를 사용하여 유연한 용량 관리를 할 수 있습니다.
* 데이터 일관성: ext4는 일반적으로 XFS보다 더 일관성 있는 데이터 저장을 지원합니다. 따라서 파일 시스템의 일관성과 안정성이 중요한 시나리오에서는 ext4를 고려할 수 있습니다.
* 백업과 복구: 파일 시스템의 백업과 복구 절차도 고려해야 합니다. ext4는 일반적으로 더 간단한 백업 및 복구 프로세스를 가지고 있습니다.
* 지원과 안정성: 둘 다 Linux 시스템에서 잘 지원되며 안정적으로 동작하지만, 특정 배포판이나 환경에 따라 지원 수준이 다를 수 있습니다.
* 요약하자면, ext4는 다양한 용도에서 잘 작동하며 데이터의 안정성과 일관성을 중요시하는 시스템에 적합할 수 있습니다. 반면에 XFS는 대용량 파일 및 데이터 처리를 위해 최적화되어 있으며, 확장성과 성능이 중요한 시스템에 적합할 수 있습니다. 선택은 사용하는 시스템의 요구 사항과 용도에 따라 결정되어야 합니다.

## 사용자 PC의 CMD SSH 접근 시 hostname 으로 접근하기 위한 설정
* 사용자 Profile 경로의 .ssh 폴더에서, config 파일을 생성한다.
	* 만약 .ssh 폴더가 없다면 CMD에서 서버로 접근 해보자.
```
아래 포맷을 이용하여 적절한 설정값을 입력한다.

#Host test-server-01
#	HostName {IP}
#	User {ID}
#	Port {PORT}
#	IdentityFile C:\Users\SNUH\.ssh\{PEM FILE}
#	HostKeyAlgorithms ssh-dss,ssh-rsa,rsa-sha2-512,rsa-sha2-256,ecdsa-sha2-nistp256,ssh-ed25519
```
* PEM => ssh-keygen 명령어로 생성 된 계정의 PRIVATE KEY 내용을 가지고 있는 파일. ~~(공개키말고)~~ 
	* 참고로 테스트는 -t ecdsa 암호화 알고리즘을 사용 함
