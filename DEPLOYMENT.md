# OpenPBBG - Live Server Deployment Guide

This guide covers deploying the OpenPBBG Vue 3 frontend application to a live production server.

## Prerequisites

- Node.js 18+ installed on the server
- Nginx or Apache web server
- SSL certificate (recommended for production)
- Domain name configured
- SSH access to your server

## Deployment Methods

### Method 1: Build Locally and Upload (Recommended for Small Projects)

#### Step 1: Build the Application Locally

```bash
# Navigate to project directory
cd /path/to/OpenPBBG

# Install dependencies
npm install

# Build for production
npm run build
```

This creates an optimized production build in the `dist/` directory.

#### Step 2: Upload to Server

```bash
# Using SCP
scp -r dist/* user@your-server.com:/var/www/openpbbg/

# Or using rsync
rsync -avz --delete dist/ user@your-server.com:/var/www/openpbbg/
```

---

### Method 2: Build on Server (Recommended for Larger Projects)

#### Step 1: Clone Repository on Server

```bash
# SSH into your server
ssh user@your-server.com

# Navigate to web directory
cd /var/www

# Clone the repository
git clone https://github.com/Lindon11/OpenPBBG.git openpbbg
cd openpbbg

# Install dependencies
npm install
```

#### Step 2: Build on Server

```bash
# Build for production
npm run build

# The built files are now in the dist/ directory
```

---

## Web Server Configuration

### Nginx Configuration

Create a new Nginx configuration file:

```bash
sudo nano /etc/nginx/sites-available/openpbbg
```

Add the following configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/openpbbg/dist;
    index index.html;

    # Enable gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/openpbbg /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

Create a `.htaccess` file in the `dist/` directory:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>

# Enable compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType application/font-woff "access plus 1 year"
  ExpiresByType application/font-woff2 "access plus 1 year"
</IfModule>
```

Create VirtualHost configuration:

```bash
sudo nano /etc/apache2/sites-available/openpbbg.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/openpbbg/dist

    <Directory /var/www/openpbbg/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/openpbbg_error.log
    CustomLog ${APACHE_LOG_DIR}/openpbbg_access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2enmod rewrite
sudo a2ensite openpbbg
sudo systemctl reload apache2
```

---

## SSL Configuration (HTTPS)

### Using Let's Encrypt (Certbot)

#### For Nginx:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

#### For Apache:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

Auto-renewal is configured automatically. Test it with:

```bash
sudo certbot renew --dry-run
```

---

## Environment Configuration

### API Backend URL

Update the API base URL in the production build:

1. Edit `src/services/api.js`:

```javascript
const baseURL = import.meta.env.VITE_API_URL || 'https://api.yourdomain.com'
```

2. Create `.env.production` file:

```env
VITE_API_URL=https://api.yourdomain.com
```

3. Rebuild the application:

```bash
npm run build
```

---

## Continuous Deployment (CI/CD)

### Using GitHub Actions

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  build-and-deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        
    - name: Install dependencies
      run: npm ci
      
    - name: Build
      run: npm run build
      env:
        VITE_API_URL: https://api.yourdomain.com
        
    - name: Deploy to Server
      uses: easingthemes/ssh-deploy@main
      env:
        SSH_PRIVATE_KEY: ${{ secrets.SSH_PRIVATE_KEY }}
        REMOTE_HOST: ${{ secrets.REMOTE_HOST }}
        REMOTE_USER: ${{ secrets.REMOTE_USER }}
        TARGET: /var/www/openpbbg/dist
        SOURCE: "dist/"
```

Add secrets in GitHub repository settings:
- `SSH_PRIVATE_KEY`: Your SSH private key
- `REMOTE_HOST`: Your server IP or domain
- `REMOTE_USER`: SSH user (e.g., `root` or `deploy`)

---

## Manual Deployment Script

Create `deploy.sh` in the project root:

```bash
#!/bin/bash

echo "🚀 Starting OpenPBBG deployment..."

# Build the application
echo "📦 Building application..."
npm run build

# Deploy to server
echo "📤 Uploading to server..."
rsync -avz --delete \
  --exclude='.git' \
  --exclude='node_modules' \
  dist/ user@your-server.com:/var/www/openpbbg/

echo "✅ Deployment complete!"
```

Make it executable:

```bash
chmod +x deploy.sh
```

Run deployment:

```bash
./deploy.sh
```

---

## Docker Deployment (Optional)

### Create Dockerfile

```dockerfile
FROM node:18-alpine as build

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

### Create nginx.conf

```nginx
server {
    listen 80;
    root /usr/share/nginx/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Build and Run

```bash
# Build image
docker build -t openpbbg:latest .

# Run container
docker run -d -p 80:80 --name openpbbg openpbbg:latest
```

### Docker Compose

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  frontend:
    build: .
    ports:
      - "80:80"
    restart: unless-stopped
    environment:
      - NODE_ENV=production
```

Run:

```bash
docker-compose up -d
```

---

## Post-Deployment Checklist

- [ ] Verify the site is accessible at your domain
- [ ] Test all routes and navigation
- [ ] Check API connectivity
- [ ] Verify HTTPS is working
- [ ] Test on mobile devices
- [ ] Check browser console for errors
- [ ] Verify static assets are loading
- [ ] Test user authentication flow
- [ ] Check performance with Google PageSpeed Insights
- [ ] Set up monitoring (e.g., UptimeRobot, Pingdom)
- [ ] Configure error tracking (e.g., Sentry)

---

## Performance Optimization

### Enable HTTP/2

For Nginx, add to server block:

```nginx
listen 443 ssl http2;
```

### Enable Brotli Compression (Nginx)

```bash
sudo apt install nginx-module-brotli
```

Add to nginx config:

```nginx
load_module modules/ngx_http_brotli_filter_module.so;
load_module modules/ngx_http_brotli_static_module.so;

http {
    brotli on;
    brotli_comp_level 6;
    brotli_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
}
```

### CDN Integration

Consider using a CDN like Cloudflare, AWS CloudFront, or Fastly for:
- Global content delivery
- DDoS protection
- Automatic asset optimization

---

## Troubleshooting

### 404 Errors on Refresh

Ensure your web server is configured to redirect all requests to `index.html` (Vue Router history mode).

### API CORS Issues

Make sure your Laravel backend has CORS properly configured in `config/cors.php`:

```php
'allowed_origins' => ['https://yourdomain.com'],
```

### Assets Not Loading

Check the `base` property in `vite.config.ts`:

```javascript
export default defineConfig({
  base: '/',
  // ...
})
```

### Blank Page After Deployment

1. Check browser console for errors
2. Verify API URL is correct
3. Check file permissions: `chmod -R 755 /var/www/openpbbg/dist`
4. Check Nginx/Apache error logs

---

## Monitoring and Maintenance

### Log Monitoring

```bash
# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Apache logs
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log
```

### Automated Backups

Create backup script:

```bash
#!/bin/bash
tar -czf /backups/openpbbg-$(date +%Y%m%d).tar.gz /var/www/openpbbg/dist
find /backups -name "openpbbg-*.tar.gz" -mtime +30 -delete
```

---

## Support

For issues and questions:
- GitHub: https://github.com/Lindon11/OpenPBBG
- Documentation: See README.md

---

**Last Updated:** February 2, 2026
