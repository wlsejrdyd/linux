
# command

## curl

## find

## screen
* 스크린 생성
```
screen -S jdy  
rsync -avz /data1/* /data2/
```
* 스크린 나가기
```
ctrl + a + d
```
  * 스크린 리스트 보기
```
screen -list
```
* 스크린 재접속
```
screen -r jdy
```
## sed
* 13 line print
```
sed -n '13p' test.txt
```

* for 문 : 전체 라인 한줄씩 출력
```
line=10
for ((i=0 i<=line; i++)); do
sed -n ${i}p {FILE}
done
```
  

## sort
* 4번쨰 컬럼의 값으로 정렬
```
ps -ef | sort -k4
```

## grep
- 검색하는 문자열이 포함 된 결과 값이 많을 때 문자열을 강조
```
cat /var/named/domain.zone | grep "\b1.1.1.1\b"
```
- 예제 : grep "1.1.1.1" 검색 할 경우 1.1.1.10~ 무수히 많은 중복 값이 발생 함