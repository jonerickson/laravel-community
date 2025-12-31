# DigitalOcean Kubernetes Deployment

Quick guide for deploying to DigitalOcean Kubernetes (DOKS).

For complete deployment instructions including ingress setup, SSL certificates, and monitoring, see [helm/README.md](../helm/README.md).

## Prerequisites

1. [DigitalOcean CLI](https://docs.digitalocean.com/reference/doctl/how-to/install/)
2. [kubectl](https://kubernetes.io/docs/tasks/tools)
3. [Helm v3+](https://helm.sh/docs/intro/install/)

## Create Cluster

Create a DOKS cluster via the DigitalOcean dashboard or CLI:

```bash
# Create cluster via CLI
doctl kubernetes cluster create laravel-community \
  --region nyc1 \
  --version latest \
  --node-pool "name=worker-pool;size=s-4vcpu-8gb;count=3"

# Configure kubectl
doctl kubernetes cluster kubeconfig save laravel-community

# Verify cluster access
kubectl cluster-info
```

**Recommended Node Sizes:**
- **Production**: `s-4vcpu-8gb` (3+ nodes) - Total: 4 vCPUs, 8GB RAM
- **Staging**: `s-2vcpu-4gb` (2+ nodes) - Total: 2 vCPUs, 4GB RAM

See [helm/README.md](../helm/README.md#3-deploy-to-cloud-environment) for complete deployment instructions including:
- NGINX Ingress Controller setup
- SSL/TLS certificates with cert-manager
- DNS configuration
- Application deployment
- Monitoring and troubleshooting
