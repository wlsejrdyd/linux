# 양식
## 모니터링 반목문
```
while true; do 명령어 ; sleep SEC ; done
watch -n SEC 명령어
```

## syslog 양식
```
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
* 180일보관

1. copytruncate : 로그파일을 복제한다. 말그대로 복제라 inode 값도 그대로 넘어옴. 아닌데?
	1. 로그파일의 내용을 복사하고 원본파일의 크기를 0으로 생성한다?
	2. 넣어야하는거였네 개좋음. 로테이트 돌고 로그가 안쌓이던게 해결됨.
2. missingok : 말그대로 로그파일이없어도 무시한다. (에러결과아님처리)
3. delaycompress : 다음 로테이트 동작시 이전 백업로그를 압축한다. (다음날되어야함, 명령어 계속 날려도 압축은 안됨)
	1. 안쓸거임
4. compress : 로그파일을 압축한다. (필요없는거같은데)
5. notifempty : 로그파일이 비어있으면 작업하지않는다.
6. dateext : 작업된 로그파일에 YYYYMMDD 형식 날짜 추가.

## PHP DB Connection
```
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
<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
```

## 파일이름 카운트
* vi test.pl
```
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
