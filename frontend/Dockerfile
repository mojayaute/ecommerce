FROM node:24-alpine AS deps

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund


FROM deps AS source

COPY . .


FROM source AS test

CMD ["sh", "-c", "npm run typecheck && npm run lint && npm run format:check && npm test"]


FROM source AS build

RUN npm run build


FROM mcr.microsoft.com/playwright:v1.65.1-noble AS e2e

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY playwright.config.ts ./
COPY e2e ./e2e

CMD ["./node_modules/.bin/playwright", "test"]


FROM nginxinc/nginx-unprivileged:1.29-alpine AS runtime

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 8080
