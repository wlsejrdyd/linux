# 이슈 모음 (지극히 개인적인)
## bind 9.11 info logging
### 문제 : 9.10 bind 버전을 사용하다가 취약하다고 보안팀에서 압박들어와서 마지못해 업데이트 침. 업데이트 당일에도 옵션문제로 slave 로 동기화가 안되던 차에 어떻게 에러로그 캐치해서 구글링 후 조치했는데 나중돼서 서버점검해보니 bind log 가 /var/log/messages 로그에 쌓이고 있는 상황이었음.
### 해결
* vi /etc/named.conf
```
logging {
.
.
.
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
* 사용자가 적지않아서 용량이 생각보다 많아가지고 고민이었는데 9.11 이슈가 좀 있었는지 찾긴 어려웠지만 글이 있었음..
