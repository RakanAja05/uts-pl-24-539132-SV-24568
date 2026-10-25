# =============================================================================
# Frontend Dockerfile — Multi-Stage Build
# Stage 1 : node:22-alpine  → build Vite/Vue SPA
# Stage 2 : nginx:1.27-alpine → serve static files + reverse proxy /api
# =============================================================================

# ── Stage 1: Build ────────────────────────────────────────────────────────────
FROM node:22-alpine AS builder

WORKDIR /app

# Layer cache: copy lock files first so npm ci only re-runs on dependency changes
COPY package.json package-lock.json ./
RUN npm ci

# VITE_API_URL is embedded at build time by Vite (import.meta.env.VITE_API_URL)
# Default to /api (relative) so the browser hits Nginx which reverse-proxies
# the request to the backend service in the Docker Compose network.
ARG VITE_API_URL=/api
ENV VITE_API_URL=${VITE_API_URL}

# Copy source and build for production
COPY . .
RUN npm run build

# ── Stage 2: Serve ────────────────────────────────────────────────────────────
FROM nginx:1.27-alpine AS runner

# Remove default nginx config
RUN rm /etc/nginx/conf.d/default.conf

# Copy our custom nginx config (SPA routing + /api reverse proxy)
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy only the built static files — node_modules is NOT included
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
