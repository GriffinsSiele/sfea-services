FROM node:16-alpine AS assets
WORKDIR /var/www/html/dashbord
COPY src/package.json src/package-lock.json ./
RUN npm install
COPY src/resources/ resources/
COPY src/public/ public/
COPY src/webpack.mix.js ./
RUN npm run production

FROM nginx:alpine
WORKDIR /var/www/html/dashbord
COPY --from=assets /var/www/html/dashbord/public/ public/
COPY --from=assets /var/www/html/dashbord/fonts/ public/fonts/
COPY docker/nginx/default.conf.template \
     /etc/nginx/templates/default.conf.template
