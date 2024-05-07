# Kubernetes

## 목적
* 다중 호스트에서 컨테이너화된 어플리케이션을 **배포,확장,관리**하기 위한 컨테이너 오케스트레이션 플랫폼
* 어플리케이션의 복잡한 마이크로서비스 아키텍처를 관리하는 데 사용함

## 주요기능
* 클러스터  **관리,자동배포,스케일링,서비스 디스커버리 등**
* 다수의 호스트에서 여러 컨테이너를 효율적으로 관리

## 사용예시
* 프로덕션 환경에서 대규모 서비스를 운영하고자 할 때 사용함.

## Install
* 환경
```
AWS EC2
CPU : 2c
Ram : 8g
OS : RedhatEnterprise 9.2
Version : 1.25.7.0
```

### 진행 (순서대로 진행)
* 환경
```
swapoff -a
systemctl stop firewalld && systemctl stop disable firewalld
setenforce 0
```
* RHEL 9 버전은 `setenforce 0` 명령어는 먹지 않으니 설정파일에서 직접 변경해준다.
* EC2는 스왑설정이없지만 fstab 파일 확인해서 주석해놓자.

```
VAR1=/etc/sysctl.d/k8s.conf && echo "net.bridge.bridge-nf-call-ip6tables = 1" >>${VAR1} && echo "net.bridge.bridge-nf-call-iptables = 1" >>${VAR1} && echo "net.ipv4.ip_forward = 1" >>${VAR1} && sysctl --system
```
* 확인 `lsmod | grep br_netfilter` , 만약 적용이 안되어있다면 `modprobe br_netfilter` 명령어로 적용.

```
cat <<EOF | sudo tee /etc/modules-load.d/k8s.conf
overlay
br_netfilter
EOF
```
```
dnf install container-tools bash-completion
dnf update -y && init 6
```
```
export VERSION=1.25 && export OS=CentOS_8
curl -L -o /etc/yum.repos.d/devel:kubic:libcontainers:stable.repo https://download.opensuse.org/repositories/devel:/kubic:/libcontainers:/stable/$OS/devel:kubic:libcontainers:stable.repo
curl -L -o /etc/yum.repos.d/devel:kubic:libcontainers:stable:cri-o:$VERSION.repo https://download.opensuse.org/repositories/devel:kubic:libcontainers:stable:cri-o:$VERSION/$OS/devel:kubic:libcontainers:stable:cri-o:$VERSION.repo
dnf install crio && systemctl enable --now crio
```
```
hostnamectl set-hostname HOSTNAME (hosts 에도 추가)
cat <<EOF | sudo tee /etc/yum.repos.d/kubernetes.repo
[kubernetes]
name=Kubernetes
baseurl=https://pkgs.k8s.io/core:/stable:/v1.25/rpm/
enabled=1
gpgcheck=1
gpgkey=https://pkgs.k8s.io/core:/stable:/v1.25/rpm/repodata/repomd.xml.key
EOF
dnf --showduplicates list  kubeadm
systemctl enable kubelet.service
```

```
kubeadm init --pod-network-cidr=172.30.0.0/16| tee kubeadm.out
mkdir -p $HOME/.kube && cp -i /etc/kubernetes/admin.conf $HOME/.kube/config && chown $(id -u):$(id -g) $HOME/.kube/config && export KUBECONFIG=/etc/kubernetes/admin.conf
```
* worker node에서 이 부분 작업을 진행하다보니 다양한 에러가 많이 나옴.
  * `The connection to the server localhost:8080 was refused - did you specify the right host or port?` : **Master Node의 /etc/kubernetes/admin.conf 내용 복사하여 생성**
  * `Unable to connect to the server: x509: certificate signed by unknown authority (possibly because of "crypto/rsa: verification error" while trying to verify candidate authority certificate "kubernetes")` : **기존에있던 ${HOME}/.kube/config 파일을 수정.**
* `systemctl status kubelet` 서비스 확인하여 동작중임을 확인해준다.
* worker node 일 경우 master kubeadm.out file 을 확인해서 kubeadm join 하자

```
echo 'source <(kubectl completion bash)' >>~/.bashrc && echo 'alias k=kubectl' >>~/.bashrc && echo 'complete -o default -F __start_kubectl k' >>~/.bashrc && source ~/.bashrc
curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl-convert"
curl -LO "https://dl.k8s.io/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl-convert.sha256"
echo "$(cat kubectl-convert.sha256) kubectl-convert" | sha256sum --check
install -o root -g root -m 0755 kubectl-convert /usr/local/bin/kubectl-convert
kubectl convert --help
rm kubectl-convert kubectl-convert.sha256
source ~/.bashrc
```
### 만약 worker node 에서 join 시 에러가 나온다거나, admin.conf 가 없다고 나온다면 
* Master node 파일정보를 가지고 올것.
```
apiVersion: v1
clusters:
- cluster:
    certificate-authority-data: 
    encrypt syntax
    server: https://MASTER NODE IP:6443
  name: kubernetes
contexts:
- context:
    cluster: kubernetes
    user: kubernetes-admin
  name: kubernetes-admin@kubernetes
current-context: kubernetes-admin@kubernetes
kind: Config
preferences: {}
users:
- name: kubernetes-admin
  user:
    client-certificate-data: 
    encrypt syntax
    client-key-data: 
    encrypt syntax
```
* admin.conf 생성 이후 mkdir 부터 다시진행.

