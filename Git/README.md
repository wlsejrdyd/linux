# Git 사용법 정리
### 환경
```
OS : RedHat Enterprise Linux 9.2
CPU : 2c
RAM : 8g
AWS EC2
```
### Git 설치
```
# yum install git
```

### Global 환경설정
```
# git config --global user.name wlsejrdyd
# git config --global user.email wlsejrdyd@gmail.com
# git config --global --list
user.name=wlsejrdyd
user.email=wlsejrdyd@gmail.com
```

### Git Repository 연결
```
# git init
# git remote add origin https://github.com/wlsejrdyd/scripts
# git remote -v
origin  https://github.com/wlsejrdyd/scripts (fetch)
origin  https://github.com/wlsejrdyd/scripts (push)
```
* init : Git 저장소를 **생성**하거나 기존 저장소를 **다시 초기화**하세요.
* remote add origin : 연결할(될) URL 주소 명시.

### file upload
* 연결됐으니 서버에서 깃 저장소로 파일을 올려봅시다.
```
# echo "Upload test file 01" > update_file_01
# git add ./update_file_01
# git status
On branch master

No commits yet

Changes to be committed:
  (use "git rm --cached <file>..." to unstage)
        new file:   update_file_01
# git commit -m "write here history comment"
# git push -u origin master
```
* git push -u orgin master 진행시 **"remote: Support for password authentication was removed on August 13, 2021."** 라고 한다.
* commit 이후 push 명령 실행시 위와 같은 에러가 나온다면 github home 으로 가셔서 [SSH Token](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token) 을 발급
  * **"세분화된 개인 액세스 토큰 만들기"** 로 진행함.

### 
* "./{filename}" 을 명시하지 않고 "." 를 입력할 경우 전체 파일을 선택하는 것이며, git rm --cached <file> 명령어로 등록 된 파일을 지울 수도 있다.
```
[root@worker02 ~]# git rm --cached .bash_profile .bashrc .cshrc .gitconfig .lesshst .ssh/authorized_keys .tcshrc
rm '.bash_profile'
rm '.bashrc'
rm '.cshrc'
rm '.gitconfig'
rm '.lesshst'
rm '.ssh/authorized_keys'
rm '.tcshrc'
[root@worker02 ~]# git status
On branch master

No commits yet

Changes to be committed:
  (use "git rm --cached <file>..." to unstage)
        new file:   .bash_logout
        new file:   update_file_01

Untracked files:
  (use "git add <file>..." to include in what will be committed)
        .bash_history
        .bash_profile
        .bashrc
        .cshrc
        .gitconfig
        .lesshst
        .ssh/
        .tcshrc
```

### 