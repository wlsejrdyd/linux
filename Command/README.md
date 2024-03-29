# 안외워지는 명령어 정리
## curl

## find

## screen
* 시간이 오래걸리는 명령어를 실행시켰는데 세션이 계속 종료된다면, 세션이 종료되어도 명령어가 계속 동작될 수 있게 끔 screen 명령어를 통해 끊임없이 작업진행이 가능하다
* rsync 로 대용량 데이터를 동기화한다는 가정
```
# 스크린 생성
screen -S jdy  
rsync -avz /data1/* /data2/

# 스크린 나가기
ctrl + a + d

# 켜져있는 스크린 보기
screen -list

# 스크린 재접속
screen -r jdy
exit
```

## sed
* 문서 한줄씩 읽어들이고 싶을때 사용
```
# 13번 라인의 줄만 가져와서 출력
sed -n '13p' test.txt

# for 반복문을 사용하여 전체 라인을 한줄씩 출력 예제
line=10
for ((i=0; i<=line; i++)); do
sed -n ${i}p test.txt
done
```

## sort
* 검색 된 값의 n번째 열로 정렬하고 싶을때 사용
```
# 4번째 컬럼의 값으로 정렬
ps -ef | sort -k4
```

## grep
```
# 검색하는 문자열이 포함된 결과값이 많을때 문자열 강조
cat /var/named/domain.zone | grep "\b0.0.0.0\b"
```
