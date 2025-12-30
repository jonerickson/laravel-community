# Kubernetes Helm Chart

This Helm chart deploys the Laravel application to Kubernetes with full production-ready infrastructure.

## Architecture

The deployment consists of the following components:

### Application Tier
- **Web (PHP-FPM + Nginx)**: Laravel application serving HTTP on port 8080
- **Caddy**: Reverse proxy handling SSL termination and automatic Let's Encrypt certificates
- **Horizon**: Laravel queue worker processing background jobs
- **Scheduler**: CronJob running Laravel scheduler every minute

### Data Tier
- **MySQL 8.0**: Primary database with persistent storage
- **Redis 7**: Cache, sessions, and queue backend with persistent storage

### Traffic Flow
```
Internet → LoadBalancer (Caddy) → ClusterIP (Web Service) → Web Pods (port 8080)
                ↓ (HTTPS/SSL)              ↓ (HTTP)
         Automatic Let's Encrypt     Laravel Application
```

## Prerequisites

1. **Kubernetes Cluster** (v1.24+)
   - Local: Minikube, Kind, Docker Desktop
   - Cloud: EKS, GKE, AKS, or any managed Kubernetes

2. **Helm** (v3.0+)
   ```bash
   # Install Helm
   curl https://raw.githubusercontent.com/helm/helm/main/scripts/get-helm-3 | bash
   ```

3. **kubectl** configured to access your cluster
   ```bash
   kubectl cluster-info
   ```

4. **Container Registry Access**
   - Images are hosted at `ghcr.io/jonerickson/mi`
   - For private registries, configure image pull secrets

## Quick Start

### 1. Build and Push Docker Image

```bash
# Build the production image
docker build --target production -t ghcr.io/jonerickson/mi:latest .

# Push to GitHub Container Registry
docker push ghcr.io/jonerickson/mi:latest
```

### 2. Deploy to Local Environment

```bash
# Install the chart
helm install app ./helm -f ./helm/values-local.yaml

# Check deployment status
kubectl get pods
kubectl get services

# Access the application (if using NodePort)
kubectl get svc app-caddy -o jsonpath='{.spec.ports[0].nodePort}'
# Visit http://localhost:<nodePort>

# Or forward to a different port
kubectl port-forward svc/app-caddy 8080:80
# Visit http://localhost:8080
```

### 3. Deploy to Staging

```bash
# Update staging values with your domain and email
# Edit helm/values-staging.yaml:
#   app.url: "https://staging.yourdomain.com"
#   caddy.email: "admin@yourdomain.com"

# Install or upgrade
helm upgrade --install app ./helm -f ./helm/values-staging.yaml

# Get LoadBalancer IP
kubectl get svc app-caddy
```

### 4. Deploy to Production

```bash
# Update production values
# Edit helm/values-production.yaml:
#   app.url: "https://yourdomain.com"

# Install or upgrade
helm upgrade --install app ./helm -f ./helm/values-production.yaml
```

## Configuration

### Environment-Specific Values

The chart includes three environment configurations:

- **`values-local.yaml`**: Development environment
  - NodePort service
  - Minimal resources
  - No SSL (HTTP only)
  - Single replicas
  - Autoscaling disabled

- **`values-staging.yaml`**: Staging environment
  - LoadBalancer service
  - Moderate resources
  - Automatic HTTPS via Caddy
  - 2-5 replicas with autoscaling

- **`values-production.yaml`**: Production environment
  - LoadBalancer service
  - High resources
  - Automatic HTTPS via Caddy
  - 3-20 replicas with autoscaling
  - Pod anti-affinity for high availability

### Required Secrets

Before deploying, you need to set the following secrets:

```bash
# Generate Laravel application key
php artisan key:generate --show

# Create a secrets file (do NOT commit this)
cat > helm-secrets.yaml <<EOF
app:
  key: "base64:YOUR_GENERATED_KEY_HERE"

mysql:
  auth:
    rootPassword: "your-secure-root-password"
    password: "your-secure-db-password"

redis:
  auth:
    password: "your-secure-redis-password"  # or leave empty for no auth

laravel:
  mail:
    username: "your-smtp-username"
    password: "your-smtp-password"
EOF

# Deploy with secrets
helm upgrade --install app ./helm \
  -f ./helm/values-production.yaml \
  -f ./helm-secrets.yaml
```

### SSL/TLS Configuration

SSL is automatically handled by Caddy:

1. **Local Environment**: SSL is disabled (HTTP only)
2. **Staging/Production**: Caddy automatically obtains Let's Encrypt certificates

**Important**: Ensure your domain's DNS points to the LoadBalancer IP before deploying staging/production, otherwise Let's Encrypt certificate issuance will fail.

```bash
# Get LoadBalancer IP
kubectl get svc app-caddy

# Point your DNS A record to this IP
# Example: staging.yourdomain.com → LoadBalancer IP
```

### Database Migrations

Migrations run automatically during web pod initialization via an init container. To manually run migrations:

```bash
kubectl exec -it deployment/app-web -- php artisan migrate --force
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
kubectl logs -f deployment/app-web

# Horizon worker logs
kubectl logs -f deployment/app-horizon

# Scheduler logs
kubectl logs -l app.kubernetes.io/component=scheduler

# Caddy logs
kubectl logs -f deployment/app-caddy
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
kubectl exec -it deployment/app-web -- /bin/bash

# Run artisan commands
kubectl exec -it deployment/app-web -- php artisan cache:clear
kubectl exec -it deployment/app-web -- php artisan config:cache
kubectl exec -it deployment/app-web -- php artisan queue:work --once
```

### Access Services

```bash
# Port forward to access services locally
kubectl port-forward svc/app-caddy 8080:80
kubectl port-forward svc/app-mysql 3306:3306
kubectl port-forward svc/app-redis 6379:6379
```

## Scaling

### Manual Scaling

```bash
# Scale web pods
kubectl scale deployment app-web --replicas=5

# Scale Horizon workers
kubectl scale deployment app-horizon --replicas=3

# Scale Caddy reverse proxy
kubectl scale deployment app-caddy --replicas=4
```

### Autoscaling

Horizontal Pod Autoscaling (HPA) is configured by default for production:

```bash
# View HPA status
kubectl get hpa

# View detailed HPA info
kubectl describe hpa app-web
kubectl describe hpa app-horizon
kubectl describe hpa app-caddy
```

## Backup and Restore

### Database Backup

```bash
# Backup MySQL database
kubectl exec -it statefulset/app-mysql -- \
  mysqldump -u root -p app > backup-$(date +%Y%m%d).sql

# Restore from backup
kubectl exec -i statefulset/app-mysql -- \
  mysql -u root -p app < backup-20250101.sql
```

### Persistent Volume Backups

Use your cloud provider's volume snapshot feature or tools like Velero for complete backups.

## Upgrading

### Application Updates

```bash
# Build and push new image with tag
docker build --target production -t ghcr.io/jonerickson/mi:v1.2.3 .
docker push ghcr.io/jonerickson/mi:v1.2.3

# Update image tag and upgrade
helm upgrade app ./helm \
  -f ./helm/values-production.yaml \
  --set image.tag=v1.2.3

# Monitor rollout
kubectl rollout status deployment/app-web
kubectl rollout status deployment/app-horizon
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
kubectl delete pvc -l app.kubernetes.io/instance=mi
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
kubectl logs statefulset/app-mysql

# Test connection from web pod
kubectl exec -it deployment/app-web -- \
  mysql -h app-mysql -u app -p
```

### SSL Certificate Issues

```bash
# Check Caddy logs for Let's Encrypt errors
kubectl logs deployment/app-caddy

# Verify DNS is pointing to LoadBalancer
kubectl get svc app-caddy
nslookup yourdomain.com

# Caddy stores certificates in /data - check PVC
kubectl exec -it deployment/app-caddy -- ls -la /data/caddy/certificates
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

## Support

For issues and questions:
- Review logs: `kubectl logs`
- Check events: `kubectl get events`
- Describe resources: `kubectl describe <resource>`
