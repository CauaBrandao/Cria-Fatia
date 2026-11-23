FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

RUN install-php-extensions pdo_mysql

COPY Caddyfile /etc/caddy/Caddyfile
COPY cria-fatia/ ./public/
COPY vercel-entrypoint.sh /usr/local/bin/cria-fatia-entrypoint

RUN chmod +x /usr/local/bin/cria-fatia-entrypoint

ENV PORT=80
EXPOSE 80

CMD ["cria-fatia-entrypoint"]
