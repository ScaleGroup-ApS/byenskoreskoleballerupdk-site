#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
REGISTRY="registry.scaleweb.dk"
NAMESPACE=${1:-"koereskole-template"}
PLATFORM="linux/arm64"

# Print colored message
log_info() {
  echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
  echo -e "${GREEN}✅${NC} $1"
}

log_warning() {
  echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
  echo -e "${RED}❌${NC} $1"
}

# Generate short SHA
SHORT_SHA=$(git rev-parse --short=8 HEAD)

# Check if we're in the right directory
if [[ ! -f "package.json" ]]; then
  log_error "Must be run from the root of the repository"
  exit 1
fi

# Ensure we're logged into the Docker registry if pushing
check_registry_login() {
  local config="${DOCKER_CONFIG:-$HOME/.docker}/config.json"
  local status

  if command -v curl >/dev/null 2>&1; then
    status=$(curl -s -o /dev/null -w "%{http_code}" "https://${REGISTRY}/v2/" || echo "000")
  fi

  if [[ "$status" == "200" ]]; then
    log_info "Registry ${REGISTRY} is accessible without authentication."
    return 0
  fi

  if [[ -f "$config" ]] && grep -q "\"${REGISTRY}\"" "$config"; then
    log_info "Docker appears to be logged in to ${REGISTRY}."
    return 0
  fi

  log_warning "Docker might not be logged in to ${REGISTRY}."
  echo "If pushing fails, please authenticate with:"
  echo "  docker login ${REGISTRY}"
  echo ""
}

check_registry_login

build_and_push_image() {
  local image_name=$1
  local dockerfile=$2

  log_info "Building ${image_name} from ${dockerfile}..."

  docker buildx build \
    --platform "${PLATFORM}" \
    --file "./${dockerfile}" \
    --tag "${REGISTRY}/${image_name}:latest" \
    --tag "${REGISTRY}/${image_name}:${SHORT_SHA}" \
    --push \
    .

  log_success "${image_name} built and pushed"
  echo ""
}

build_local_image() {
  local image_name=$1
  local dockerfile=$2

  log_info "Building ${image_name} locally from ${dockerfile} (for docker compose)..."

  # We use standard docker build, load into local daemon, using host arch
  docker build \
    --file "./${dockerfile}" \
    --tag "${REGISTRY}/${image_name}:latest" \
    --tag "${REGISTRY}/${image_name}:${SHORT_SHA}" \
    .

  log_success "${image_name} built and loaded locally"
  echo ""
}

# Parse arguments
if [[ "$1" == "--local" ]]; then
  log_info "Building base image locally for docker compose..."
  build_local_image "koereskole-base" "Dockerfile.base"
  build_local_image "koereskole-template" "Dockerfile"
  log_success "Images built locally! You can now run 'docker compose up'."
  exit 0
fi

log_info "Building and deploying images with tag: ${SHORT_SHA} for ${PLATFORM}"
echo ""

# Step 0: Build frontend assets natively (avoids slow QEMU ARM64 emulation)
log_info "Building frontend assets natively..."
bun run build
log_success "Frontend assets built"
echo ""

# Step 1: Build base image FIRST since the template depends on it
build_and_push_image "koereskole-base" "Dockerfile.base"

# Step 2: Build main image
build_and_push_image "koereskole-template" "Dockerfile"

log_success "All images built and pushed successfully!"
echo ""

# Check if user wants to continue with deployment
read -p "Do you want to deploy to kubernetes now? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log_info "Skipping kubernetes deployment."
    exit 0
fi

# Step 2: Apply Kubernetes manifests
log_info "Preparing and applying Kubernetes manifests..."

# Similar to .github/workflows/deploy.yml
if [ -f "infra/deployment.yaml" ]; then
    cp infra/deployment.yaml infra/deployment.yaml.tmp
    sed -i "s/{{NAMESPACE}}/$NAMESPACE/g" infra/deployment.yaml.tmp
    sed -i "s/{{CUSTOMER_SLUG}}/$NAMESPACE/g" infra/deployment.yaml.tmp
    sed -i "s/{{CUSTOMER_ID}}/${NAMESPACE}/g" infra/deployment.yaml.tmp
    sed -i "s/{{CUSTOMER_NAME}}/${NAMESPACE}/g" infra/deployment.yaml.tmp
    sed -i "s/{{CUSTOMER_DOMAIN}}/${NAMESPACE}.dk/g" infra/deployment.yaml.tmp
    sed -i "s/{{IMAGE_NAME}}/koereskole-template/g" infra/deployment.yaml.tmp
    
    kubectl apply -f infra/deployment.yaml.tmp
    rm -f infra/deployment.yaml.tmp
    log_success "Manifests applied"
else
    log_warning "infra/deployment.yaml not found. Skipping kubectl apply."
fi
echo ""

# Step 3: Update deployment images
log_info "Updating deployment images to ${SHORT_SHA}..."
echo ""

kubectl set image deployment/customer-site \
  customer-site="${REGISTRY}/koereskole-template:${SHORT_SHA}" \
  -n "${NAMESPACE}"

log_success "Deployments updated"
echo ""

# Step 4: Monitor rollout
log_info "Checking rollout status..."
kubectl rollout status deployment/customer-site -n "${NAMESPACE}" --timeout=5m

echo ""
log_success "Deployment complete!"
echo ""
echo "Images deployed:"
echo "  - koereskole-base:      ${REGISTRY}/koereskole-base:${SHORT_SHA}"
echo "  - koereskole-template:  ${REGISTRY}/koereskole-template:${SHORT_SHA}"
