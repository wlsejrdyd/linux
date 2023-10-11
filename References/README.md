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
* vi /etc/sysconfig/vncservers
```
VNCSERVERS="2:root"
VNCSERVERARGS[2]="-geometry 800x600 -nolisten tcp -localhost"VNCSERVERS="2:root"
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