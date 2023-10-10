# 명령어 정리
## curl

## find
### 경로안의 특정 사용자를 검색하여 새로운 사용자 이름으로 변경하기.
```
find /home/* -user pdnsdeok -exec chown pdnsdeok. {} \;
명령어 : find [경로] -user [사용자] -exec chown [변경할 사용자] {} \;
```
### NNNN 퍼미션 검색
```
find -perm -NNNN
```

## screen
* 시간이 오래걸리는 명령어를 실행시켰는데 세션이 계속 종료된다면, 세션이 종료되어도 명령어가 계속 동작될 수 있게 끔 screen 명령어를 통해 끊임없이 작업진행이 가능하다
* rsync 로 대용량 데이터를 동기화한다는 가정
```
screen -S jdy  #(스크린 생성)
rsync -avz /data1/* /data2/
ctrl + a + d #(스크린 나가기)
screen -list #(켜져있는 스크린 보기)
screen -r jdy #(스크린 재접속)
exit
```

## sed
