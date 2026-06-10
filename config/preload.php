<?php

$env = getenv('APP_ENV');
if (file_exists(dirname(__DIR__) . "/var/cache/{$env}/App_KernelProdContainer.preload.php")) {
    opcache_compile_file(dirname(__DIR__) . "/var/cache/{$env}/App_KernelProdContainer.preload.php");
}
