# 이슈 모음 (지극히 개인적인)
## bind 9.11 info logging
### 문제 : named ~~ client ~~ query failed (SERVFAIL) for ~~
* 이전 9.9 에서 9.11 로 업데이트 하고 발생하기 시작함.
* 이유도 못 찾겠는데 messages 로그를 가득가득채워서 트러블슈팅하는데 문제가 많았음
  
### 해결
* vi /etc/named.conf
```
logging {
     channel query-errors_log {
          file "/var/named/log/query-errors" versions 5 size 20m;
          print-time yes;
          print-category yes;
          print-severity yes;
          severity dynamic;
     };
     
    category query-errors {query-errors_log; };
}
```
* 사용자가 적지않아서 용량이 생각보다 많아가지고 고민이었는데 9.11 이슈가 좀 있었는지 찾긴 어려웠지만 글이 있었음
* logging 섹션을 새로만들라는게 아니라 channel query-errors_log { ~ 추가하란 것
