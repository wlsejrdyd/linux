# 개인 프로젝트

- [x] 1. 쿠버네티스 클러스터 구성
- [x] 2. 도커 private registry 구성
- [x] 3. 폐쇄망 환경에서 도커 개인 레지스트리를 이용하여 이미지 가져오기
- [x] 4. 쿠버네티스 대쉬보드 설치
- [x] 5. 간단한 DockerFile 만들기
- [x] 6. private registry 에 DockerFile push
- [x] 7. private 또는 local docker image 를 이용하여 kubernetes로 배포하기
- [x] 8. NodePort 또는 LoadBalancer Type 의 경우 nodeport를 80으로 변경하기
- [ ] 9. CI/CD 구성하여 배포 테스트

## Kubernetes cluster
* 환경 : localhost hyper-v
* vm : rocky 9.3 minimal VM *2
* resource : 2cpu, 2gb

* master node command history
```
swapoff -a
systemctl stop firewalld && systemctl disable firewalld
sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config
sed -i '/swap/d' /etc/fstab
hostnamectl set-hostname master
echo "10.10.10.10 master" >> /etc/hosts
echo "10.10.10.11 node01" >> /etc/hosts

cat << EOF > /etc/yum.repos.d/kubernetes.repo
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
echo 'source <(kubectl completion bash)' >>~/.bashrc && echo 'alias k=kubectl' >>~/.bashrc && echo 'complete -o default -F __start_kubectl k' >>~/.bashrc && source ~/.bashrc
curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl-convert"
curl -LO "https://dl.k8s.io/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl-convert.sha256"
echo "$(cat kubectl-convert.sha256) kubectl-convert" | sha256sum --check
install -o root -g root -m 0755 kubectl-convert /usr/local/bin/kubectl-convert
kubectl convert --help
rm kubectl-convert kubectl-convert.sha256

yum update && init 6

kubeadm init --control-plane-endpoint=master
또는 kubeadm init --pod-network-cidr=10.244.0.0/16
mkdir -p ~/.kube && cp -ar /etc/kubernetes/admin.conf ~/.kube/config
```
* networks 를 설정하지않으면 flannel 설치 후에 IP 할당이 제대로 안돼서 cidr 를 다시 설정해야하므로 아싸리 두번째걸로 하는게 좋아보임

* worker node command history
```
yum update && init 6 까지 동일
.
.
.
vi /etc/kubernetes/admin.conf
(Master node 내용복붙)
mkdir -p ~/.kube && cp -ar /etc/kubernetes/admin.conf ~/.kube/config

master node 와 join 진행
```

* 다시 Master node 작업
```
kubectl apply -f https://github.com/flannel-io/flannel/releases/latest/download/kube-flannel.yml
kubectl get nodes
```
* 위 주소로 pod networks addon 실행이 안될 시 : kubectl apply -f https://raw.githubusercontent.com/projectcalico/calico/v3.26.1/manifests/calico.yaml

## Docker Private Registry 구성
* 환경 : 리소스 와 테섭이 없기때문에 master worker node를 재활용한다.

* master node command history
```
docker pull registry
docker container run -d -p 5000:5000 --name registry registry
curl -X GET http://localhost:5000/v2/_catalog
docker pull nginx
docker save -o nginx.tar nginx:latest
docker load -i nginx.tar
docker tag nginx:latest localhost:5000/nginx-test:latest
docker image push localhost:5000/nginx-test:latest
```
* 재부팅 후 registry 목록이 날아감,,, 이게 맞긴한데 저장하는 방법이 필요할듯

* worker node command history
```
cat <<EOF> /etc/docker/daemon.json
{
  "insecure-registries":["10.10.10.10:5000"]
}
EOF

systemctl restart docker
docker pull 10.10.10.10:5000/nginx-test
```

## Kubernetes dashboard install
* kubernetes-dashboard service 는 공식 홈페이지에서 [다운로드](https://kubernetes.io/ko/docs/tasks/access-application-cluster/web-ui-dashboard/) 받음.

```
kubectl apply -f https://raw.githubusercontent.com/kubernetes/dashboard/v2.6.1/aio/deploy/recommended.yaml
kubectl get namespaces
kubectl get -n kubernetes-dashboard svc
kubectl describe -n kubernetes-dashboard svc kubernetes-dashboard
kubectl edit -n kubernetes-dashboard svc kubernetes-dashboard
# 내용 수정 type: ClusterIP => NodePort

cat <<EOF> clusterRoleBinding.yaml
apiVersion: rbac.authorization.k8s.io/v1
kind: ClusterRoleBinding
metadata:
  name: admin-user
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: ClusterRole
  name: admin
subjects:
- kind: ServiceAccount
  name: admin-user
  namespace: kubernetes-dashboard
EOF

cat <<EOF> serviceAccount.yaml
apiVersion: v1
kind: ServiceAccount
metadata:
  name: admin-user
  namespace: kubernetes-dashboard
EOF

kubectl create -f clusterRoleBinding.yaml
kubectl create -f serviceAccount.yaml
kubectl describe -n kubernetes-dashboard svc kubernetes-dashboard
kubectl -n kubernetes-dashboard create token admin-user | tee admin-user.token

kubectl proxy &
```
* 대시보드 token 으로 로그인하고, 시간제한이있나봄. token 자체를 다시 발급해야하므로 발급 명령어를 알아두는 것이 좋아보임.
* 대시보드에서 신규 리소스를 생성 하여 외부 접근 테스트 완료.

## Sample DockerFile 업로드
* acadamine dockerfile 사용해서... 2차 배포는 절대 금지
* docker registry 에 올라간 이미지가 kubentes 에서 가져와서 쓸 수 있는지?

* Master Node에서 acadmine docker file 을 사용. (github 에 있음)
```
cd /root/docker/Section-3/data-volumes-03-adj-node-code/data-volumes-03-adj-node-code
docker build . -t node-01:latest
```

## Sample Docker image Push (private registry)
* 위 구성해둔 registry 에 생성한 Dockerfile 을 push 한다.

* matser node command history
```
docker tag node-01:latest localhost:5000/node-01:latest
docker image push localhost:5000/node-01:latest
```

* worker node command history
```
docker pull 10.10.10.10:5000/node-01:latest
docker save -o node-01:latest.tar 10.10.10.10:5000/node-01:latest
ctr -a /run/containerd/containerd.sock --namespace k8s.io image import node-01.tar
ctr -a /run/containerd/containerd.sock --namespace k8s.io image ls | grep node-01
kubectl create deployment local-image-node-01 --replicas=2 --image=10.10.10.10:5000/node-01 --dry-run=client -o yaml > local-image-node-01.yaml
vi local-image-node-01.yaml
# 내용 추가 : imagePullPolicy: Never
kubectl create -f local-image-node-01.yaml
kubectl expose deployment local-image-node-01 --name="local-image-node-01" --type=NodePort --port=80 --target-port=80
kubectl get service
curl localhost:31458

# 참고 양식 (Deployment)
apiVersion: apps/v1
kind: Deployment
metadata:
  creationTimestamp: null
  labels:
    app: local-image-node-01
  name: local-image-node-01
spec:
  replicas: 2
  selector:
    matchLabels:
      app: local-image-node-01
  strategy: {}
  template:
    metadata:
      creationTimestamp: null
      labels:
        app: local-image-node-01
    spec:
      containers:
      - image: 10.10.10.10:5000/node-01
        imagePullPolicy: Never
        name: node-01
        resources: {}
status: {}
```
* 참고 : 삭제는 **ctr -a /run/containerd/containerd.sock --namespace k8s.io images del 10.10.10.10:5000/node-01:latest**
    - k8s는 containerd runtime 사용하고있어서 docker를 인식할 수 없음
    + IfNotPresent (local 먼저, 없으면 repository), Always (항상 repository), Never (항상 local)
    + 위 작업은 docker image를 containerd image 로 변환 하는 작업이 포함되어있고, **모든 노드에 이미지가 등록되어야 함**

    ## NodePort 또는 LoadBalancer Type 의 경우 nodeport를 80으로 변경하기
    * 포트자체를 바꾸지 않고, reverse proxy (nginx) 를 이용하여 구성함
    ```
    yum install nginx
    systemctl enable --now nginx
    vi /etc/nginx/nginx.conf


    http {
      server {
        listen       80;
        server_name  deok.kr;
        location / {
                proxy_pass http://192.168.219.3:30345;
        }
      }
      server {
        listen       80;
        server_name www.deok.kr;
        location / {
                proxy_pass http://192.168.219.3:30345;
        }
      }
      server {
        listen       80;
        server_name docker.deok.kr;
        location / {
                proxy_pass http://192.168.219.2:5000;
        }
      }
      server {
        listen       80;
        server_name k8s.deok.kr;
        location / {
                proxy_pass https://192.168.219.3:31259;
        }
      }
    }
    ```
    * 상단에 공유기가 있어서 포트를 하나밖에 못 사용하는 상황임, 공유기에서 해결할까 하다가 nginx 를 설치하여 proxy_pass derective 사용함.
    * 참고로 master , node01 서버의 아이피가 잠깐 바뀜.
