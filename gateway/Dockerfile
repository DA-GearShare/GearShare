# Production gateway: builds the Vue SPA and serves it with nginx, next to the
# /api proxy and the media routes. Built by compose.prod.yaml with
#   context            ./frontend
#   additional context "gateway" = ./gateway
# Dev does not use this image (plain nginx:alpine + Vite dev server).

FROM node:22-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=gateway prod.conf /etc/nginx/conf.d/default.conf
COPY --from=gateway snippets /etc/nginx/snippets
COPY --from=build /app/dist /usr/share/nginx/html
