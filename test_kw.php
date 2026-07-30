<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = App\Models\Article::whereHas('manuscript', fn($q) => $q->whereHas('keywords', fn($k) => $k->where('keyword_text', 'ilike', '%iterate%')))->count();
echo "COUNT: " . $count . "\n";
