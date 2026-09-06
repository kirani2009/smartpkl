<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;

echo "=== FINAL LOGIN VERIFICATION ===\n\n";

$emails = [
    ['admin@smartpkl.id', 'admin'],
    ['guru@smartpkl.id', 'teacher'],
    ['guru2@smartpkl.id', 'teacher'],
    ['guru3@smartpkl.id', 'teacher'],
    ['rina@smartpkl.id', 'student'],
    ['dedi@smartpkl.id', 'student'],
    ['maya@smartpkl.id', 'student'],
    ['rizky@smartpkl.id', 'student'],
    ['diana@smartpkl.id', 'student'],
    ['fajar@smartpkl.id', 'student'],
    ['lestari@smartpkl.id', 'student'],
    ['adi@smartpkl.id', 'student'],
    ['nina@smartpkl.id', 'student'],
    ['hrd@techcorp.id', 'company'],
    ['info@digitalstudio.id', 'company'],
    ['recruit@multinet.id', 'company'],
];

$allPass = true;
foreach ($emails as [$email, $expectedRole]) {
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "FAIL: {$email} - user not found\n";
        $allPass = false;
        continue;
    }
    $pwCheck = Hash::check('password123', $user->password);
    $roleOk = $user->role === $expectedRole;
    $status = ($pwCheck && $roleOk) ? 'PASS' : 'FAIL';
    if ($status === 'FAIL') $allPass = false;
    echo "{$status}: {$email} | role={$user->role} (expected {$expectedRole}) | password=" . ($pwCheck ? 'OK' : 'WRONG') . "\n";
}

echo "\n" . ($allPass ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED') . "\n";
