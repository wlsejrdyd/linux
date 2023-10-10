# 양식
## 모니터링 반목문
```
while true; do 명령어 ; sleep SEC ; done
watch -n SEC 명령어
```

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
