#!/bin/bash
set -e

ssh magical-mclean 'cd /var/www/vhosts/pepvguides.com/repo/frontend && npm run build && rsync -avz --exclude="index.php" --exclude=".htaccess" --exclude="storage" --exclude="admin" --exclude="vendor" --exclude="css" --exclude="js" --exclude="install" --exclude="robots.txt" dist/ /var/www/vhosts/pepvguides.com/repo/backend/public/ && ln -sf /var/www/vhosts/pepvguides.com/repo/backend/storage/app/public /var/www/vhosts/pepvguides.com/repo/backend/public/storage && chown -h pepvguides:psacln /var/www/vhosts/pepvguides.com/repo/backend/public/storage'

echo "Deploy complete"
