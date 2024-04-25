# 개인 프로젝트

1. 쿠버네티스 클러스터 구성
2. 도커 private registry 구성
3. 폐쇄망 환경에서 도커 개인 레지스트리를 이용하여 이미지 가져오기
4. 쿠버네티스 대쉬보드 설치
5. 간단한 DockerFile 만들기
6. private registry 에 DockerFile 푸쉬하기
7. CI/CD 구성하여 배포 테스트

## Kubernetes cluster
* 환경 : localhost hyper-v
* vm : rocky 9.3 minimal VM *2
* resource : 2cpu, 2gb

* Master Node CMD HISTORY
```
swapoff -a
systemctl stop firewall-cmd && systemctl disable firewalld
sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config
sed -i '/swap/d' /etc/fstab
hostnamectl set-hostname master
echo "10.10.10.10 master" >> /etc/hosts
echo "10.10.10.11 node01" >> /etc/hosts

cat << EOF > kubernetes.repo
[kubernetes]
name=Kubernetes
baseurl=https://pkgs.k8s.io/core:/stable:/v1.29/rpm/
enabled=1
gpgcheck=1
gpgkey=https://pkgs.k8s.io/core:/stable:/v1.29/rpm/repodata/repomd.xml.key
EOF

yum install -y yum-utils
yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
yum install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin -y
systemctl enable --now docker

cat <<EOF > /etc/modules-load.d/k8s.conf
overlay
br_netfilter
EOF

cat <<EOF > /etc/sysctl.d/k8s.conf
net.bridge.bridge-nf-call-iptables  = 1
net.bridge.bridge-nf-call-ip6tables = 1
net.ipv4.ip_forward                 = 1
EOF

modprobe overlay && modprobe br_netfilter
sysctl --system
containerd config default | tee /etc/containerd/config.toml >/dev/null 2>&1
sed -i 's/SystemdCgroup \= false/SystemdCgroup \= true/g' /etc/containerd/config.toml

yum install -y kubelet kubeadm
systemctl enable kubelet && systemctl start kubelet

yum update && init 6

mkdir -p ~/.kube && cp -ar /etc/kubernetes/admin.conf ~/.kube/config
kubeadm init --control-plane-endpoint=master
```

* Worker Node CMD HISTORY
```
yum update && init 6 까지 동일
.
.
.
vi /etc/kubernetes/admin.conf
(Master node 내용을 복/붙 합니다.)
cp -ar /etc/kubernetes/admin.conf ~/.kube/config

master node 와 join 진행
```

## Docker Private Registry 구성
* 환경 : 리소스 와 테섭이 없기때문에 master worker node를 재활용한다.

* master node CMD HISTORY
```
docker pull registry
docker container run -d -p 5000:5000 --name registry registry
curl -X GET http://localhost:5000/v2/_catalog
docker pull nginx
docker save -o nginx.tar nginx:latest
docker load -i nginx.tar
docker tag nginx:latest localhost:5000/nginx-test:latest
docker image push localhost:5000/nginx-test:latest
curl -X GET http://localhost:5000/v2/_catalog
```

* worker node CMD HISTORY
```
cat <<EOF> /etc/docker/daemon.json
{
  "insecure-registries":["10.10.10.10:5000"]
}
EOF

systemctl restart docker
docker pull 10.10.10.10:5000/nginx-test
```