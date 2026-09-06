<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'http://127.0.0.1:8201/api';

// Helper to login and get token
function login($email, $password, $baseUrl) {
    $resp = Http::withHeaders(['Accept' => 'application/json'])
        ->post("$baseUrl/auth/login", ['email' => $email, 'password' => $password]);
    $data = $resp->json();
    return $data['data']['token'] ?? null;
}

function authGet($token, $url, $baseUrl) {
    $resp = Http::withHeaders([
        'Accept' => 'application/json',
        'Authorization' => "Bearer $token",
    ])->get("$baseUrl$url");
    return ['status' => $resp->status(), 'body' => $resp->json()];
}

// ===== TEST ALL ENDPOINTS =====
$accounts = [
    ['guru@smartpkl.id', 'teacher', ['/me/teacher', '/teacher/partnerships', '/teacher/dashboard']],
    ['rina@smartpkl.id', 'student', ['/me/student', '/student/internships', '/student/applications']],
    ['hrd@techcorp.id', 'company', ['/me/company', '/company/partnerships', '/company/internships']],
    ['admin@smartpkl.id', 'admin', ['/me']],
];

foreach ($accounts as [$email, $role, $endpoints]) {
    $token = login($email, 'password123', $baseUrl);
    echo "\n=== {$role} ({$email}) ===\n";
    if (!$token) {
        echo "  LOGIN FAILED!\n";
        continue;
    }
    echo "  Login: OK\n";
    
    foreach ($endpoints as $ep) {
        $r = authGet($token, $ep, $baseUrl);
        $status = $r['status'];
        $success = $r['body']['success'] ?? false;
        $msg = $r['body']['message'] ?? 'no message';
        $code = $status === 200 && $success ? 'OK' : 'FAIL';
        echo "  {$ep}: {$code} (HTTP {$status}) {$msg}\n";
    }
}
