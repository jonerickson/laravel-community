# Kubernetes Helm Chart

This Helm chart deploys the Laravel application to Kubernetes with full production-ready infrastructure.

## Architecture

The deployment consists of the following components:

### Application Tier
- **Web (PHP-FPM + Nginx)**: Laravel application serving HTTP on port 8080
- **Horizon**: Laravel queue worker processing background jobs
- **Scheduler**: CronJob running Laravel scheduler every minute

### Data Tier
- **MySQL 8.4**: Primary database with persistent storage
- **Redis 7**: Cache, sessions, and queue backend with persistent storage

## Prerequisites

1. Kubernetes cluster (v1.24+):
   - Local: Minikube, Kind, Docker Desktop
   - Cloud: [EKS](../docs/AWS.md), [DOKS](../docs/DIGITALOCEAN.md), [GKE](../docs/GCP.md), AKS, or any managed Kubernetes

2. Cluster requirements:

   Minimum cluster requirements vary by environment:

   **Local (Development)**
   - CPU: 1.5 vCPUs
   - Memory: 3Gi
   - Storage: 6Gi
   - Autoscaling: Disabled

   **Staging**
   - CPU: 2 vCPUs (4+ recommended for autoscaling)
   - Memory: 5Gi (10Gi+ recommended for autoscaling)
   - Storage: 30Gi
   - Autoscaling: Enabled

   **Production**
   - CPU: 2.5 vCPUs (5+ recommended for autoscaling)
   - Memory: 6Gi (12Gi+ recommended for autoscaling)
   - Storage: 60Gi
   - Autoscaling: Enabled

   **Note:** These are base resource requests. Autoscaling requires additional headroom.
   Consider cluster overhead (system pods, networking, kubernetes dashboard, ingress controller, etc.) when sizing nodes.

3. [Helm v3+](https://helm.sh/docs/intro/install/)

4. [kubectl](https://kubernetes.io/docs/tasks/tools) configured to access your cluster:
   ```bash
   # Verify connection to cluster, either local or cloud
   kubectl cluster-info
   ```

5. Container registry access:
   - Images are hosted at `ghcr.io/jonerickson/laravel-community`
   - For private registries, configure image pull secrets
   - For local deployment, images are not pulled. They should be built before deploying.

## Quick Start

### 1. Build Docker Images

```bash
# Build the production images
# If you are deploying to the cloud, you will need to push the images to a public repository and update the Helm values to pull the correct image.
docker build --target production -t laravel-community:latest .
docker build --target cli -t laravel-community-cli:latest .
```

### 2. Deploy to Local Environment

```bash
# Install the chart
helm install app ./helm -f ./helm/values-local.yaml

# Forward the port to access locally
kubectl port-forward svc/app-laravel-community-web 8080:8080
# Visit http://localhost:8080

# You can optionally install the Ingress Controller as well, but it is not needed
```

### 3. Deploy to Cloud Environment

1. Install [Kubernetes Dashboard](#kubernetes-dashboard). This is optional but provides a UI for managing the cluster.

2. Install the Helm Chart.
```bash
# Install the chart
helm install app ./helm -f ./helm/values-production.yaml
```

3. Install the [Ingress Controller](#ingress-controller). Make sure to update the values in `values-production.yaml` to match the domains you intend to point to the app.
   * If you plan to access the site via SSL (https), make sure to follow the steps to install `cert-manager`.
   * Follow the instructions to update your domain's DNS records to point to the Load Balancer.

### Database Migrations

Migrations run automatically during web pod initialization. To manually run migrations:

```bash
kubectl exec -it deployment/app-laravel-community-web -- php artisan migrate --force
```

### Customization

Override any default value by creating your own values file:

```bash
# Create custom values
cat > my-values.yaml <<EOF
web:
  replicaCount: 5

mysql:
  persistence:
    size: 200Gi
EOF

# Deploy with custom values
helm upgrade --install app ./helm \
  -f ./helm/values-production.yaml \
  -f ./my-values.yaml
```

## Monitoring and Debugging

### View Logs

```bash
# Web application logs
kubectl logs -f deployment/app-laravel-community-web

# Horizon worker logs
kubectl logs -f deployment/app-laravel-community-horizon

# Scheduler logs
kubectl logs -l app.kubernetes.io/component=scheduler
```

### Check Pod Status

```bash
# All pods
kubectl get pods

# Specific component
kubectl get pods -l app.kubernetes.io/component=web
```

### Execute Commands in Pods

```bash
# Access web pod shell
kubectl exec -it deployment/app-laravel-community-web -- /bin/bash

# Run artisan commands
kubectl exec -it deployment/app-laravel-community-web -- php artisan cache:clear
kubectl exec -it deployment/app-laravel-community-web -- php artisan config:cache
kubectl exec -it deployment/app-laravel-community-web -- php artisan queue:work --once
```

### Access Services

```bash
# Port forward to access services locally
kubectl port-forward svc/app-laravel-community-mysql 3306:3306
kubectl port-forward svc/app-laravel-community-redis 6379:6379
```

## Scaling

### Manual Scaling

```bash
# Scale web pods
kubectl scale deployment app-laravel-community-web --replicas=5

# Scale Horizon workers
kubectl scale deployment app-laravel-community-horizon --replicas=3
```

### Autoscaling

Horizontal Pod Autoscaling (HPA) is configured by default for production:

```bash
# View HPA status
kubectl get hpa

# View detailed HPA info
kubectl describe hpa app-laravel-community-web
kubectl describe hpa app-laravel-community-horizon
```

## Backup and Restore

### Database Backup

```bash
# Backup MySQL database
kubectl exec -it statefulset/app-laravel-community-mysql -- \
  mysqldump -u root -p app > backup-$(date +%Y%m%d).sql

# Restore from backup
kubectl exec -i statefulset/app-laravel-community-mysql -- \
  mysql -u root -p app < backup-20250101.sql
```

### Persistent Volume Backups

Use your cloud provider's volume snapshot feature or tools like Velero for complete backups.

## Upgrading

### Application Updates

```bash
# Build and push new image with tag
docker build --target production -t laravel-community:v1.2.3 .
docker push laravel-community:v1.2.3

# Update image tag and upgrade
helm upgrade app ./helm \
  -f ./helm/values-production.yaml \
  --set image.tag=v1.2.3

# Monitor rollout
kubectl rollout status deployment/app-laravel-community-web
kubectl rollout status deployment/app-laravel-community-horizon
```

### Rollback

```bash
# View release history
helm history app

# Rollback to previous release
helm rollback app

# Rollback to specific revision
helm rollback app 3
```

## Uninstalling

```bash
# Delete the Helm release
helm uninstall app

# Delete persistent volumes (CAUTION: This deletes all data)
kubectl delete pvc -l app.kubernetes.io/instance=app
```

## Troubleshooting

### Pods Not Starting

```bash
# Check pod status and events
kubectl describe pod <pod-name>

# Check logs
kubectl logs <pod-name>

# Check init container logs
kubectl logs <pod-name> -c init-migrations
```

### Database Connection Issues

```bash
# Verify MySQL is running
kubectl get pods -l app.kubernetes.io/component=mysql

# Check MySQL logs
kubectl logs statefulset/app-laravel-community-mysql

# Test connection from web pod
kubectl exec -it deployment/app-laravel-community-web -- \
  mysql -h app-laravel-community-mysql -u root -p
```

### Scheduler Not Running

```bash
# Check CronJob status
kubectl get cronjobs

# View recent jobs
kubectl get jobs

# Check job logs
kubectl logs job/<job-name>
```

## Production Checklist

Before going to production, ensure:

- [ ] DNS points to LoadBalancer IP
- [ ] `app.key` is set with a secure random key
- [ ] All database passwords are strong and unique
- [ ] `app.debug` is set to `false`
- [ ] `app.env` is set to `production`
- [ ] Persistent volumes have adequate size
- [ ] Resource limits are appropriate for your traffic
- [ ] Monitoring and alerting are configured
- [ ] Backup strategy is in place
- [ ] Rollback procedure is tested

## Ingress Controller

The application uses an Ingress controller to route external traffic to your Laravel application. Setup varies by environment.

### Local Development (Minikube, Kind, Docker Desktop)

**Install NGINX Ingress Controller:**

```bash
# For Minikube
minikube addons enable ingress

# For Kind
kubectl apply -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/main/deploy/static/provider/kind/deploy.yaml

# For Docker Desktop
kubectl apply -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/main/deploy/static/provider/cloud/deploy.yaml
```

**Add local DNS entry:**

```bash
# Add to /etc/hosts (macOS/Linux)
echo "127.0.0.1 laravel-community.local" | sudo tee -a /etc/hosts

# For Minikube, use the cluster IP instead
echo "$(minikube ip) laravel-community.local" | sudo tee -a /etc/hosts
```

**Access the application:**

```bash
# Visit http://laravel-community.local
# For Minikube, you may need to run: minikube tunnel
```

### Cloud Deployments

#### Amazon (AWS) EKS, DigitalOcean (DOKS), or Google Cloud Platform (GCP) GKE

**1. Install NGINX Ingress Controller:**

```bash
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo update

helm install ingress-nginx ingress-nginx/ingress-nginx \
  --namespace ingress-nginx \
  --create-namespace \
  --set controller.service.type=LoadBalancer
```

**2. Get the LoadBalancer IP:**

```bash
kubectl get svc -n ingress-nginx ingress-nginx-controller

# Wait for EXTERNAL-IP to be assigned (may take 2-3 minutes)
# Example output:
# NAME                       TYPE           EXTERNAL-IP       PORT(S)
# ingress-nginx-controller   LoadBalancer   64.225.123.45     80:31234/TCP,443:32123/TCP
```

**3. Configure DNS:**

- Go to your DNS provider (Cloudflare, Route53, etc.)
- Create an A or CNAME record pointing to the LoadBalancer IP/URL:
  - `laravel-community.com` → `64.225.123.45`
  - `staging.laravel-community.com` → `64.225.123.45`

**4. Install `cert-manager` for SSL:**

```bash
# Install cert-manager
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.14.0/cert-manager.yaml

# Create Let's Encrypt ClusterIssuer
cat <<EOF | kubectl apply -f -
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: admin@laravel-community.com
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: nginx
EOF
```

**5. Deploy:**

```bash
helm upgrade --install app ./helm -f ./helm/values-production.yaml

# Check the status of the ingress controller
kubectl describe ingress app-laravel-community
```

**How it works:**
- NGINX Ingress creates a DigitalOcean Load Balancer
- Traffic flows: Internet → DO Load Balancer → Ingress Controller → Service → Pods
- Load Balancer costs ~$12/month
- Supports multiple applications on same LoadBalancer using different hostnames

#### Amazon EKS (AWS) Only

**1. Install AWS Load Balancer Controller:**

```bash
# Create IAM policy
curl -o iam_policy.json https://raw.githubusercontent.com/kubernetes-sigs/aws-load-balancer-controller/main/docs/install/iam_policy.json

aws iam create-policy \
  --policy-name AWSLoadBalancerControllerIAMPolicy \
  --policy-document file://iam_policy.json
  
# Create OAuth provider for service account
eksctl utils associate-iam-oidc-provider \
  --cluster=laravel-community \
  --approve

# Create IAM role and service account (replace with your account ID)
eksctl create iamserviceaccount \
  --cluster=laravel-community \
  --namespace=kube-system \
  --name=aws-load-balancer-controller \
  --attach-policy-arn=arn:aws:iam::111122223333:policy/AWSLoadBalancerControllerIAMPolicy \
  --approve

# Install controller
helm repo add eks https://aws.github.io/eks-charts
helm repo update

helm install aws-load-balancer-controller eks/aws-load-balancer-controller \
  -n kube-system \
  --set clusterName=laravel-community \
  --set serviceAccount.create=false \
  --set serviceAccount.name=aws-load-balancer-controller
```

**2. Update ingress configuration:**

```yaml
# In values-production.yaml
ingress:
  enabled: true
  className: alb
  annotations:
    alb.ingress.kubernetes.io/scheme: internet-facing
    alb.ingress.kubernetes.io/target-type: ip
    alb.ingress.kubernetes.io/certificate-arn: arn:aws:acm:us-east-1:123456789:certificate/abc123
    alb.ingress.kubernetes.io/ssl-redirect: '443'
```

**3. Deploy:**

```bash
helm upgrade --install app ./helm -f ./helm/values-production.yaml

# Check the status of the ingress controller
kubectl describe ingress app-laravel-community
```

**How it works:**
- **ALB (Application Load Balancer)**: Layer 7, integrates with ACM for SSL, costs based on usage
- **NLB (Network Load Balancer)**: Layer 4, faster, costs ~$16/month + data transfer
- Traffic flows: Internet → ALB/NLB → Ingress → Service → Pods
- ALB can route multiple applications using host-based routing

### Ingress Configuration Options

**Basic HTTP:**

```yaml
ingress:
  enabled: true
  hosts:
    - host: myapp.com
      paths:
        - path: /
          pathType: Prefix
```

**With SSL/TLS:**

```yaml
ingress:
  enabled: true
  annotations:
    cert-manager.io/cluster-issuer: letsencrypt-prod
  hosts:
    - host: myapp.com
      paths:
        - path: /
          pathType: Prefix
  tls:
    - secretName: myapp-tls
      hosts:
        - myapp.com
```

**Multiple domains:**

```yaml
ingress:
  enabled: true
  hosts:
    - host: laravel-community.com
      paths:
        - path: /
          pathType: Prefix
    - host: www.laravel-community.com
      paths:
        - path: /
          pathType: Prefix
```

## Kubernetes Dashboard

Deploy the Kubernetes Dashboard to monitor and manage your cluster through a web UI.

### Option 1: Helm Chart Installation

Recommended for most Kubernetes clusters (EKS, DOKS, local clusters).

```bash
# Add the official Kubernetes Dashboard Helm repository
helm repo add kubernetes-dashboard https://kubernetes.github.io/dashboard/
helm repo update

# Install the dashboard
helm upgrade --install kubernetes-dashboard kubernetes-dashboard/kubernetes-dashboard \
  --create-namespace \
  --namespace kubernetes-dashboard
```

**Create Admin User:**

```bash
cat <<EOF | kubectl apply -f -
apiVersion: v1
kind: ServiceAccount
metadata:
  name: admin-user
  namespace: kubernetes-dashboard
---
apiVersion: rbac.authorization.k8s.io/v1
kind: ClusterRoleBinding
metadata:
  name: admin-user
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: ClusterRole
  name: cluster-admin
subjects:
- kind: ServiceAccount
  name: admin-user
  namespace: kubernetes-dashboard
EOF
```

**Access Dashboard:**

```bash
# Start proxy
kubectl -n kubernetes-dashboard port-forward svc/kubernetes-dashboard-kong-proxy 8443:443

# Visit: https://localhost:8443
```

**Get Login Token:**

```bash
kubectl -n kubernetes-dashboard create token admin-user
```

### Option 2: Direct Manifest Installation

Recommended for GKE clusters to avoid Kong compatibility issues.

```bash
# Deploy the dashboard
kubectl apply -f https://raw.githubusercontent.com/kubernetes/dashboard/v2.7.0/aio/deploy/recommended.yaml
```

**Access Dashboard:**

```bash
# Start proxy
kubectl proxy

# Visit: http://localhost:8001/api/v1/namespaces/kubernetes-dashboard/services/https:kubernetes-dashboard:/proxy/
```

**Get Login Token:**

```bash
kubectl -n kubernetes-dashboard create token admin-user
```

### Cleanup Dashboard

```bash
# Option 1: Remove Helm installation
helm uninstall kubernetes-dashboard -n kubernetes-dashboard

# Option 2: Remove manifest installation
kubectl delete -f https://raw.githubusercontent.com/kubernetes/dashboard/v2.7.0/aio/deploy/recommended.yaml

# Remove admin user (both options)
kubectl -n kubernetes-dashboard delete serviceaccount admin-user
kubectl delete clusterrolebinding admin-user

# Delete namespace (if needed)
kubectl delete namespace kubernetes-dashboard
```

## Support

For issues and questions:
- Review logs: `kubectl logs`
- Check events: `kubectl get events`
- Describe resources: `kubectl describe <resource>`
