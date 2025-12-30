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
   - Images are hosted at `ghcr.io/jonerickson/laravel-community`
   - For private registries, configure image pull secrets
   - For local deployment, images are not pulled. They should be built before deploying.

## Quick Start

### 1. Build Docker Images

```bash
# Build the production image
docker build --target production -t laravel-community:latest .

# Build the CLI image
docker build --target cli -t laravel-community-cli:latest .
```

### 2. Deploy to Local Environment

```bash
# Install the chart
helm install app ./helm -f ./helm/values-local.yaml

# Check deployment status
kubectl get pods
kubectl get services

# Forward the port to access locally
kubectl port-forward svc/app-laravel-community-web 8080:8080
# Visit http://localhost:8080
```

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

## Support

For issues and questions:
- Review logs: `kubectl logs`
- Check events: `kubectl get events`
- Describe resources: `kubectl describe <resource>`
