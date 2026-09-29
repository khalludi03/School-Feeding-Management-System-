import fs from 'fs';
let content = fs.readFileSync('Dockerfile', 'utf-8');
content = content.replace(/RUN echo "\\n\{\\n\\tfrankenphp.*?caddyfile/s, 'COPY Caddyfile /etc/caddy/Caddyfile\nCMD php artisan storage:link --force || true; php artisan config:cache; php artisan route:cache; php artisan view:cache; frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile');
fs.writeFileSync('Dockerfile', content);
