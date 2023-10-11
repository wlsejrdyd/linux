# 양식
## 모니터링 반목문
```
# while true; do df -h ; sleep 1 ; done
# watch -n 1 df -h
```
* df -h 명령어를 1초 단위로 갱신하여 터미널에 표시해줌

## syslog 양식
```
# vi /etc/logrotate.d/syslog
/$dir2/log/apache/*log
/$dir2/log/redis/*log
/$dir2/log/tomcat/catalina.out
/$dir2/log/mariadb/*log
{
        copytruncate
        daily
        missingok
        rotate 180
        compress
        delaycompress
        notifempty
        dateext
}
```
* copytruncate : 로그파일을 복제한다. 말그대로 복제라 inode 값도 그대로 넘어옴. 아닌데?
  * 로그파일의 내용을 복사하고 원본파일의 크기를 0으로 생성한다?
  * 넣어야하는거였네 개좋음. 로테이트 돌고 로그가 안쌓이던게 해결됨.
* missingok : 말그대로 로그파일이없어도 무시한다. (에러결과아님처리)
* delaycompress : 다음 로테이트 동작시 이전 백업로그를 압축한다. (다음날되어야함, 명령어 계속 날려도 압축은 안됨)
	* 안쓸거임
* compress : 로그파일을 압축한다. (필요없는거같은데)
* notifempty : 로그파일이 비어있으면 작업하지않는다.
* dateext : 작업된 로그파일에 YYYYMMDD 형식 날짜 추가.

## PHP DB Connection
```
# vi db_con.php
<?
$Host = "locahost";
$User = "deok";
$Pass = "deok00##";
$DB = "deok";

$con = mysqli_connect($Host,$User,$Pass,$DB) or die('Failed');
$result = mysql_select_db($DB, $con);
?>
```

## PHP Error Reporting
```
# vi index.php
<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
```
* index.php 파일에 추가해준다.

## 파일이름 카운트
```
# vi test.pl
#!/usr/bin/perl
use strict; use warnings;
use utf8; 

# 글자 수 구하기
#my $s = "이름";
my $s = "이름";
print length($s), "\n";
# 출력 결과 (유니코드 글자 수): 4

# 바이트 수 구하기
do {
  use bytes;
    print length($s), "\n";
      # 출력 결과 (문자열의 바이트 수): 12
      };
```
* 간혹 파일이름이 감당할 수 없을 정도로 긴 파일들을 서버로 가지고와야할때 사용해볼만한 파이썬 스크립트이다