<?php
// Colocar en wp-content/plugins/EIPSI-Forms/clear-cache.php y acceder vía navegador
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPCache cleared!";
}
