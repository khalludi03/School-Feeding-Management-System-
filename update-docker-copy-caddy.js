import fs from 'fs';

let content = fs.readFileSync('Dockerfile', 'utf-8');
content = content.replace(/RUN echo ".*? > \/etc\/caddy\/Caddyfile/s, 'COPY Caddyfile /etc/caddy/Caddyfile');
fs.writeFileSync('Dockerfile', content);
