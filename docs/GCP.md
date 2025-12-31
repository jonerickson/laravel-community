# Google Cloud Platform (GCP) Deployment

Quick guide for deploying to Google Kubernetes Engine (GKE).

For complete deployment instructions including ingress setup, SSL certificates, and monitoring, see [helm/README.md](../helm/README.md).

## Prerequisites

1. [Google Cloud SDK (gcloud)](https://cloud.google.com/sdk/docs/install)
2. [kubectl](https://kubernetes.io/docs/tasks/tools)
3. [Helm v3+](https://helm.sh/docs/intro/install/)

## Create Cluster

Create a GKE cluster via the Google Cloud Console or gcloud CLI:

```bash
# Set your project ID
gcloud config set project YOUR_PROJECT_ID

# Create cluster via gcloud
gcloud container clusters create laravel-community \
  --zone us-central1-a \
  --machine-type e2-standard-2 \
  --disk-type pd-standard \
  --disk-size 50 \
  --num-nodes 2 \
  --enable-autoscaling \
  --min-nodes 2 \
  --max-nodes 3 \
  --enable-autorepair \
  --enable-autoupgrade

# Configure kubectl
gcloud container clusters get-credentials laravel-community --zone us-central1-a

# Verify cluster access
kubectl cluster-info
```

**Recommended machine types:**
- **Production**: `e2-standard-2` (2 vCPUs, 8GB RAM) - 2-3 nodes
- **Staging**: `e2-medium` (2 vCPUs, 4GB RAM) - 2 nodes

**Note:** This configuration fits within default GCP quotas (12 vCPUs, 250GB storage). Uses zonal cluster instead of regional to reduce resource requirements. For higher availability, consider requesting quota increases for regional clusters.

See [helm/README.md](../helm/README.md#cloud-deployments-digitalocean-aws) for complete deployment instructions including:
- NGINX Ingress Controller setup with GCP Load Balancer
- SSL/TLS certificates with cert-manager or Google-managed certificates
- Cloud DNS configuration
- Application deployment
- Monitoring and troubleshooting