<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

$parentHt = '/home2/ugelhuanucogob/inventario.ugelhuanuco.gob.pe/.htaccess';

if (file_exists($parentHt) && is_writable($parentHt)) {
    $parentContent = <<<EOT
<IfModule mod_rewrite.c>
    RewriteEngine On
    # No reescribir subdirectorios como Henrry
    RewriteCond %{REQUEST_URI} ^/Henrry(/|$) [NC]
    RewriteRule ^ - [L]

    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# Deny access to sensitive files
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
EOT;
    file_put_contents($parentHt, $parentContent);
    echo "Parent .htaccess UPDATED successfully!\n";
    echo file_get_contents($parentHt) . "\n";
} else {
    echo "Parent .htaccess is not writable or does not exist\n";
}
