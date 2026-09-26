<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

$request = \Illuminate\Http\Request::create('/dashboard?month=6&year=2026', 'GET');
$response = $app->handle($request);

echo "HTTP Status: " . $response->getStatusCode() . "\n";
$content = $response->getContent();

echo "Contains 'trendChart': " . (str_contains($content, 'trendChart') ? 'YES' : 'NO') . "\n";
echo "Contains 'kelompokChart': " . (str_contains($content, 'kelompokChart') ? 'YES' : 'NO') . "\n";
echo "Contains 'poliChart': " . (str_contains($content, 'poliChart') ? 'YES' : 'NO') . "\n";
echo "Contains 'BPJS MANDIRI': " . (str_contains($content, 'BPJS MANDIRI') ? 'YES' : 'NO') . "\n";
echo "Contains 'PENYAKIT DALAM': " . (str_contains($content, 'PENYAKIT DALAM') ? 'YES' : 'NO') . "\n";
