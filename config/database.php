<?php
declare(strict_types=1);

use App\Config\Env;

return [
    'host'     => Env::get('DB_HOST', 'db'),
    'port'     => (int) Env::get('DB_PORT', '3306'),
    'name'     => Env::get('DB_NAME', 'punto_pago'),
    'user'     => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', 'secret'),
    'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
];
