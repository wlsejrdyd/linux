# RHEL 계열 Linux 서버 System Log 정리

## journald priority error
```
pam_systemd_(sshd:session): Failed to release session: Interrupted system call
```
* 시스템에서 세션을 해제하려고 할 때 인터럽트된 작업을 나타내는 로그

## journald priority warning
```
kernel: [drm:drm_atomic_helper_wait_for_dependencies [drm_ksm_helper]] *ERROR* [CRTC:37:crtc-0] flip_done timed out
```
* VMWare 관련로그
* VMWare VM RHEL 7,8 에서만 발견되는 로그이며, VMWare 엔지니어링 팀은 이 메세지가 하이퍼바이저에 무해하다고 밝혔고, VMWare 사내에서 문제를 재현할 수 없기때문에 "수정되지 않음" 으로 VMWare 담당자에 의해 종료되었음.
* [Redhat](https://access.redhat.com/solutions/4490391) 공식 사이트 참조

```
rtkit-daemon: The canary thread is apparently starving. Taking action.
```
* 쓰레드 우선순위 조절된거라는 로그라는데 잘 이해가 안가서 확인이 더 필요함.
* 우선순위가 rtkit-daemon 으로부터 문제가 생겨 우선순위가 조정되었다면 소프트웨어 검증을 통해 이상이없는지 확인이 필요하지않을까?

```
gnome-software: failed to call gs_plugin_refresh on packagekit-refresh: failure:
gnome-software: failed to call gs_plugin_refresh on shell-extenstions:
gnome-software: failed to call gs_plugin_refresh on odrs:
```
* gnome-software 가 백그라운드에서 네트워크에 연결하여 자동 업데이트 요청을 보내는데 실패했다는 로그
  * 보통 외부랑 단절 된 네트워크에 물려있는 서버에서 발생하는데 보안 업데이트를 못받는거다.
  * 취약한 버전은 수동으로 올릴 생각이므로 비활성화가 필요
* 해결 :
```
# vi /etc/xdg/autostart/gnome-software-service.desktop
[Desktop Entry]
X-GNOME-Autostart-enables = false
```
* 재부팅을 통해 활성화가 된다. *(아직안해봄)*

```
named: managed-keys-zone: No DNSKEY RRSIGs found for '.': success
```
* bind service는 dnskey rrsig 시스템로그를 지속적으로 보고한다. DNSKEY에 대한것을 찾을수는 없지만, success. 동작에는 문제가 없다라는 로그이다. 내부 DNS서버라 불필요에 의해 설정이 빠져있을 수 있어서 참고만 하는게 좋을거같다.

```
kernel: net_ratelimit: 7 callbacks suppressed
```
* 리눅스 커널에서 발생하는 메시지 중 하나로, 네트워크 관련 이벤트를 처리하는 동안 발생하는 로그다.
* 이 메세지는 네트워크 또는 커널이벤트의 로깅을 제한하고 네트워크 레이트미트제한을 적용하여 로그 파일이 지나치게 커지는 것을 방지하기 위해 나타날 수 있다고 한다.

```
kerenl: <asm>:[INFO]Filter atamptl unregister
kernel: <atamptl>:[INFO]Unloaded atamptl
kernel: <asm>:[INFO]AhnLab. Securiry Module Version 3.4.3.8 unloaded
kernel: Request for unknown module key 'AhnLab, Inc.: AhnLab Code Signig - 2022.001: a0ee2da1~~~ err -11
kernel: <atamptl>:[INFO]ASM Version 3.4.3.8
```
* 리눅스서버에 V3를 설치하고 최신버전으로 업데이트 받은 이후 발생하기 시작함.
  * 라이선스 제공업체의 담당 엔지니어말로는 secure boot 환경이 아닌 시스템에서 발생하는 로그라고 하였지만 비활성화되어있는 서버중 해당 로그가 발생하지 않는 서버가 몇대있다.
  * V3 기능으로 로그발생 예외처리에 대한 문의를 했지만 아직 답변이없음. 그래서 문자열 <atm>,<atamptl>,AhnLab 3개를 예외처리하고 점검하기로 했다. (다른 케이스의 로그를 확인하려면 별도로 시스템로그를 다시 찾아봐야함.)

```
systemd-tmpfiles: failed to parse ACL "group:adm:r--,group:wheel:r--": Invalid argument. Ignoring
```
* ISMS 보안 취약점 조치로 사용하지않는 그룹 adm 를 삭제했더니 발생하기 시작함.
* 해결
```
# vi /usr/lib/tmpfiles.d/systemd.conf
adm 포함되어있는 문자열 전체삭제
```
* 재부팅 이후 모니터링 중인데 아직 발견되지않고 있다.

## messages log

## secure log
```
dispatch_procotol_error: type 11 seq 11
```
* 이 메시지는 주로 통신 및 네트워크 프로토콜 관련 소프트웨어에서 발생한다.
* ?
