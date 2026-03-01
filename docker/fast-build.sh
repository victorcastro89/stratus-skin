#!/bin/bash
# Fast Docker build script with BuildKit optimizations

# Enable Docker BuildKit for faster builds
export DOCKER_BUILDKIT=1
export COMPOSE_DOCKER_CLI_BUILD=1

# Build with BuildKit cache
docker compose build --parallel

# Or rebuild without cache when needed
# docker compose build --no-cache --parallel
