# 디스크
## 디스크 확장
* Rocky 9 / RHEL 9 에서 테스트진행
* rocky 9 에서는 parted 를 이용 , rhel 9 에서는 lvm을 이용해서 테스트진행
* [Pated]
  * parted [device name] resizepart [시작점] [끝점]
  * 확인하는 질문이 나오지만 거리낌없이 Y
  * resize2fs [device name] 입력 후 확인해보면 문제없이 확장 됨
* [Lvm]
  * lvmextend -L+1G /dev/vo/lvdata
  * 1기가바이트 확장 함
  * resize2fs /dev/vo/lvdata 로 업데이트 진행하고 확인해보면 확장되어있음
* 재부팅후 fstab 내용대로 마운트도 확인
* 참고로 xfs filesystem 은 resize2fs 가 안먹고 xfs_growfs 를 이용해야함
  * 마운트 상태에서도 라이브에서 잘 동작하는것으로 보인다.


## 디스크 속도체크
```
dd if=/dev/zero of=/root/testfile count=1000 bs=1024k
```
* dd 명령어를 이용해서 if 읽기 /  of,if=/dev/zero  쓰기 테스트

### 쓰기 테스트
```
time dd if=/dev/zero of=/var/test bs=8k count=1000000 
1000000+0 records in
1000000+0 records out
8192000000 bytes (8.2 GB) copied, 8.92194 s, 918 MB/s
real    0m8.924s
user    0m0.151s
sys     0m8.763s
```
### 읽기 테스트
```
time dd if=/var/test of=/dev/null bs=8k count=1000000 
1000000+0 records in
1000000+0 records out
8192000000 bytes (8.2 GB) copied, 9.4147 s, 870 MB/s
real    0m9.420s
user    0m0.153s
sys     0m6.676s
```
### 읽기.쓰기 테스트 
```
time dd if=/var/test of=/tmp/test bs=8k count=1000000 
1000000+0 records in
1000000+0 records out
8192000000 bytes (8.2 GB) copied, 17.7223 s, 462 MB/s
real    0m17.727s
user    0m0.209s
sys     0m14.241s
```
