# AWS EKS Deployment

Quick guide for deploying to Amazon Elastic Kubernetes Service (EKS).

For complete deployment instructions including ingress setup, SSL certificates, and monitoring, see [helm/README.md](../helm/README.md).

## Prerequisites

1. [AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html)
2. [eksctl](https://eksctl.io/installation/)
3. [kubectl](https://kubernetes.io/docs/tasks/tools)
4. [Helm v3+](https://helm.sh/docs/intro/install/)

## Create Cluster

Create an EKS cluster via the AWS Console or eksctl:

```bash
# Create cluster via eksctl
eksctl create cluster \
  --name laravel-community \
  --region us-east-1 \
  --nodegroup-name worker-pool \
  --node-type t3.xlarge \
  --nodes 3 \
  --nodes-min 2 \
  --nodes-max 4 \
  --managed \
  --node-iam-policy arn:aws:iam::aws:policy/service-role/AmazonEBSCSIDriverPolicy

# Configure kubectl (if not automatic)  
aws eks update-kubeconfig --region us-east-1 --name laravel-community

# Install the EBS CSI driver addon
aws eks create-addon \
  --cluster-name laravel-community \
  --addon-name aws-ebs-csi-driver
  
# If the PVCs fail to mount, you may need to configure the default storage class on the cluster
kubectl patch storageclass gp2 \
  -p '{"metadata":{"annotations":{"storageclass.kubernetes.io/is-default-class":"true"}}}'

# Verify cluster access
kubectl cluster-info
```

**Recommended node sizes:**
- **Production**: `t3.xlarge` (4 vCPUs, 16GB RAM) - 3+ nodes
- **Staging**: `t3.large` (2 vCPUs, 8GB RAM) - 2+ nodes

See [helm/README.md](../helm/README.md#3-deploy-to-cloud-environment) for complete deployment instructions including:
- AWS Load Balancer Controller or NGINX Ingress setup
- SSL/TLS certificates with ACM or cert-manager
- Route53 DNS configuration
- Application deployment
- Monitoring and troubleshooting
