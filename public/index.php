<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config\DatabaseConfig;
use App\Config\Env;
use App\Infrastructure\Database;
use App\Infrastructure\Cache;
use App\Controller\PaymentController;
use App\Controller\WalletController;
use App\Controller\WebhookController;
use App\Service\PaymentService;
use App\Service\WalletService;

// --- Bootstrap ---
Env::load(dirname(__DIR__) . '/.env');

$db = new Database(DatabaseConfig::fromFile());

$cache = new Cache(getenv('MEMCACHED_HOST') ?: 'memcached');

$paymentService = new PaymentService($db, $cache);
$walletService  = new WalletService($db);

$paymentCtrl = new PaymentController($paymentService);
$walletCtrl  = new WalletController($walletService);
$webhookCtrl = new WebhookController($paymentService, getenv('WEBHOOK_SECRET') ?: 'secret');

// --- Routing ---
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');

try {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        $body = [];
    } else {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON body', 400);
        }
        $body = $decoded;
    }

    match (true) {
        $method === 'POST' && preg_match('#^/wallets/(\d+)/deposit$#', $uri, $m)
            => $walletCtrl->deposit((int)$m[1], $body),

        $method === 'POST' && preg_match('#^/wallets/(\d+)/withdraw$#', $uri, $m)
            => $walletCtrl->withdraw((int)$m[1], $body),

        $method === 'POST' && $uri === '/payments'
            => $paymentCtrl->create($body, $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? null),

        $method === 'POST' && $uri === '/webhooks/payment'
            => $webhookCtrl->handle($body, $_SERVER['HTTP_X_SIGNATURE'] ?? ''),

        default => throw new RuntimeException('Not Found', 404),
    };
} catch (\Throwable $e) {
    $code = $e->getCode() && $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode([
        'error' => $e->getMessage(),
        'code'  => $code,
    ]);
}