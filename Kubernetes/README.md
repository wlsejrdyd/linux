# Kubernetes
## Install
* 환경
```
AWS EC2
CPU : 2c
Ram : 8g
OS : RedhatEnterprise 9.2
```
* 진행
```
swapoff -a
systemctl stop firewalld && systemctl stop disable firewalld
setenforce 0
VAR1=/etc/sysctl.d/k8s.conf && echo "net.bridge.bridge-nf-call-ip6tables = 1" >>${VAR1} && echo "net.bridge.bridge-nf-call-iptables = 1" >>${VAR1} && echo "net.ipv4.ip_forward = 1" >>${VAR1} && sysctl --system
cat <<EOF | sudo tee /etc/modules-load.d/k8s.conf
overlay
br_netfilter
EOF
dnf install container-tools bash-completion
dnf update -y && init 6
export VERSION=1.25 && export OS=CentOS_8
curl -L -o /etc/yum.repos.d/devel:kubic:libcontainers:stable.repo https://download.opensuse.org/repositories/devel:/kubic:/libcontainers:/stable/$OS/devel:kubic:libcontainers:stable.repo
curl -L -o /etc/yum.repos.d/devel:kubic:libcontainers:stable:cri-o:$VERSION.repo https://download.opensuse.org/repositories/devel:kubic:libcontainers:stable:cri-o:$VERSION/$OS/devel:kubic:libcontainers:stable:cri-o:$VERSION.repo
dnf install crio && systemctl enable --now crio
hostnamectl set-hostname master-node-new (hosts 에도 추가)
cat <<EOF > /etc/yum.repos.d/kubernetes.repo
[kubernetes]
name=Kubernetes
baseurl=https://packages.cloud.google.com/yum/repos/kubernetes-el7-x86_64
enabled=1
gpgcheck=1
repo_gpgcheck=1
gpgkey=https://packages.cloud.google.com/yum/doc/yum-key.gpg https://packages.cloud.google.com/yum/doc/rpm-package-key.gpg
EOF
dnf --showduplicates list  kubeadm
dnf install -y kubeadm-1.25.7-0 kubelet-1.25.7-0 kubectl-1.25.7-0 && systemctl enable kubelet.service
modprobe br_netfilter && kubeadm init --pod-network-cidr=172.30.0.0/16| tee kubeadm.out
만약 worker node 일 경우 master kubeadm.out file 을 확인해서 kubeadm join 하자
mkdir -p $HOME/.kube && cp -i /etc/kubernetes/admin.conf $HOME/.kube/config && chown $(id -u):$(id -g) $HOME/.kube/config && export KUBECONFIG=/etc/kubernetes/admin.conf
echo 'source <(kubectl completion bash)' >>~/.bashrc && echo 'alias k=kubectl' >>~/.bashrc && echo 'complete -o default -F __start_kubectl k' >>~/.bashrc && source ~/.bashrc
curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl-convert"
curl -LO "https://dl.k8s.io/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl-convert.sha256"
echo "$(cat kubectl-convert.sha256) kubectl-convert" | sha256sum --check
install -o root -g root -m 0755 kubectl-convert /usr/local/bin/kubectl-convert
kubectl convert --help
rm kubectl-convert kubectl-convert.sha256
source ~/.bashrc
```
