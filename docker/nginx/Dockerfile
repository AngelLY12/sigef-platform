FROM nginx:stable-alpine

COPY default.conf /etc/nginx/conf.d/default.conf
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh
RUN mkdir -p /var/www

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
