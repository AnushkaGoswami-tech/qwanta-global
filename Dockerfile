FROM php:8.3-cli AS runtime
RUN apt-get update && apt-get install -y --no-install-recommends libsqlite3-dev libpq-dev python3 && docker-php-ext-install pdo_sqlite pdo_pgsql && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY . .
RUN php -l router.php && php -l app/db.php && php -l app/legacy-enquiry.php && php -l public/admin/index.php && php -l public/enquiry.php && php -l public/image.php
RUN mkdir -p private && python3 download_photos.py
ENV QWANTA_PRIVATE_DIR=/app/private
EXPOSE 10000
CMD ["sh", "-c", "export QWANTA_RATE_SECRET=${QWANTA_RATE_SECRET:-$(php -r 'echo bin2hex(random_bytes(32));')}; exec php -S 0.0.0.0:${PORT:-10000} -t public router.php"]
