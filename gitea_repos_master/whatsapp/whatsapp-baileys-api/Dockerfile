# Build stage
FROM git.i-sphere.local/isphere-images/node:18-alpine AS builder
WORKDIR /app
ENV NODE_ENV production
COPY . .
RUN apk add --no-cache git &&  \
    npm i -g typescript ts-node --force &&  \
    yarn install --production --frozen-lockfile &&  \
    yarn build && \
    wget -nv -O - https://gobinaries.com/tj/node-prune | sh && \
    node-prune node_modules && \
    apk del git

# Runtime stage
FROM git.i-sphere.local/isphere-images/node:18-alpine AS runtime
ENV TZ="Europe/Moscow"
WORKDIR /app
COPY package.json .
RUN apk add --no-cache dumb-init
COPY --from=builder /app/scripts ./scripts
COPY --from=builder /app/node_modules ./node_modules
COPY --from=builder /app/dist ./dist
RUN chmod +x /app/scripts/update_avatar.sh; /app/scripts/update_avatar.sh
CMD [ "dumb-init", "yarn", "start:prod" ]