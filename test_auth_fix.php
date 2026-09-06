<?php
/**
 * COMPREHENSIVE AUTH DIAGNOSTIC & TEST SCRIPT
 * 
 * Run: php test_auth_fix.php
 * 
 * This script tests:
 * 1. Database connection
 * 2. personal_access_tokens table existence
 * 3. Existing user login (password verification)
 * 4. New user registration
 * 5. Register → Login flow
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Company;

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║       SMARTPKL AUTH DIAGNOSTIC & TEST SCRIPT        ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$allPass = true;

// =====================================================
// TEST 1: Database Connection
// =====================================================
echo "━━━ TEST 1: Database Connection ━━━\n";
try {
    DB::connection()->getPdo();
    echo "  ✅ Database connected successfully\n";
    echo "  📌 Driver: " . DB::connection()->getDriverName() . "\n";
    echo "  📌 Database: " . DB::connection()->getDatabaseName() . "\n";
} catch (Exception $e) {
    echo "  ❌ Database connection FAILED: " . $e->getMessage() . "\n";
    echo "  ⚠️  Check .env DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD\n";
    $allPass = false;
}

// =====================================================
// TEST 2: Required Tables Exist
// =====================================================
echo "\n━━━ TEST 2: Required Tables ━━━\n";
$requiredTables = ['users', 'personal_access_tokens', 'teachers', 'students', 'companies'];
foreach ($requiredTables as $table) {
    if (Schema::hasTable($table)) {
        echo "  ✅ Table '{$table}' exists\n";
    } else {
        echo "  ❌ Table '{$table}' MISSING!\n";
        if ($table === 'personal_access_tokens') {
            echo "     → Run: php artisan migrate\n";
        }
        $allPass = false;
    }
}

// =====================================================
// TEST 3: Users table has role column
// =====================================================
echo "\n━━━ TEST 3: Users Table Schema ━━━\n";
if (Schema::hasTable('users')) {
    $columns = Schema::getColumnListing('users');
    $requiredCols = ['id', 'name', 'email', 'password', 'role'];
    foreach ($requiredCols as $col) {
        if (in_array($col, $columns)) {
            echo "  ✅ Column '{$col}' exists\n";
        } else {
            echo "  ❌ Column '{$col}' MISSING!\n";
            echo "     → Run: php artisan migrate\n";
            $allPass = false;
        }
    }
}

// =====================================================
// TEST 4: APP_KEY is set
// =====================================================
echo "\n━━━ TEST 4: APP_KEY ━━━\n";
if (config('app.key')) {
    echo "  ✅ APP_KEY is set\n";
} else {
    echo "  ❌ APP_KEY is NOT set!\n";
    echo "     → Run: php artisan key:generate\n";
    $allPass = false;
}

// =====================================================
// TEST 5: Existing Users & Password Verification
// =====================================================
echo "\n━━━ TEST 5: Existing Users & Password ━━━\n";
$userCount = User::count();
echo "  📌 Total users: {$userCount}\n";

$testAccounts = [
    ['admin@smartpkl.id', 'admin', 'admin123'],
    ['guru@smartpkl.id', 'teacher', 'password123'],
    ['rina@smartpkl.id', 'student', 'password123'],
    ['hrd@techcorp.id', 'company', 'password123'],
];

foreach ($testAccounts as [$email, $expectedRole, $expectedPassword]) {
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "  ⚠️  {$email}: user not found in DB (may need seeding)\n";
        continue;
    }
    
    $pwCheck = Hash::check($expectedPassword, $user->password);
    $roleOk = $user->role === $expectedRole;
    
    if ($pwCheck && $roleOk) {
        echo "  ✅ {$email}: role={$user->role} password=OK\n";
    } else {
        echo "  ❌ {$email}: role={$user->role}" . ($roleOk ? '' : " (expected {$expectedRole})") 
             . " password=" . ($pwCheck ? 'OK' : 'WRONG') . "\n";
        
        if (!$pwCheck) {
            echo "     → Password hash mismatch. This user may have been seeded with a different password.\n";
            echo "     → Check if the password was double-hashed during import.\n";
        }
        $allPass = false;
    }
}

// =====================================================
// TEST 6: Sanctum Token Creation
// =====================================================
echo "\n━━━ TEST 6: Sanctum Token Creation ━━━\n";
$firstUser = User::first();
if ($firstUser) {
    try {
        // Clean up old test tokens
        $firstUser->tokens()->where('name', 'auth-test-token')->delete();
        
        $token = $firstUser->createToken('auth-test-token')->plainTextToken;
        echo "  ✅ Token created successfully for user: {$firstUser->email}\n";
        
        // Clean up
        $firstUser->tokens()->where('name', 'auth-test-token')->delete();
        echo "  ✅ Test token cleaned up\n";
    } catch (Exception $e) {
        echo "  ❌ Token creation FAILED: " . $e->getMessage() . "\n";
        echo "     → Ensure 'personal_access_tokens' table exists and is properly migrated.\n";
        echo "     → Run: php artisan migrate\n";
        $allPass = false;
    }
} else {
    echo "  ⚠️  No users in database — cannot test token creation\n";
}

// =====================================================
// TEST 7: Registration Flow (API Simulation)
// =====================================================
echo "\n━━━ TEST 7: Registration Flow ━━━\n";
$testEmail = 'test_autofix_' . time() . '@test.com';
$testPassword = 'TestPassword123!';

try {
    // Simulate what the controller does
    $validated = [
        'name' => 'Test User Fix',
        'email' => $testEmail,
        'password' => $testPassword,
        'role' => 'student',
    ];
    
    // User model 'hashed' cast should auto-hash the password
    $user = User::create($validated);
    
    // Verify password was hashed correctly
    $pwOk = Hash::check($testPassword, $user->password);
    
    if ($pwOk) {
        echo "  ✅ User created: {$user->email} (id={$user->id})\n";
        echo "  ✅ Password hashing verified\n";
    } else {
        echo "  ❌ Password hash verification FAILED after creation!\n";
        $allPass = false;
    }
    
    // Create token
    $token = $user->createToken('auth-token')->plainTextToken;
    echo "  ✅ Token created for new user\n";
    
    // Simulate login verification
    $loginUser = User::where('email', $testEmail)->first();
    $loginPwOk = Hash::check($testPassword, $loginUser->password);
    
    if ($loginPwOk) {
        echo "  ✅ Login verification: password matches\n";
    } else {
        echo "  ❌ Login verification: password does NOT match!\n";
        $allPass = false;
    }
    
    // Clean up test user
    $user->tokens()->delete();
    $user->delete();
    echo "  ✅ Test user cleaned up\n";
    
} catch (Exception $e) {
    echo "  ❌ Registration flow FAILED: " . $e->getMessage() . "\n";
    $allPass = false;
}

// =====================================================
// TEST 8: HTTP Endpoint Test (if server running)
// =====================================================
echo "\n━━━ TEST 8: HTTP Endpoints (requires running server) ━━━\n";
$baseUrl = 'http://127.0.0.1:8000/api';
try {
    $resp = \Illuminate\Support\Facades\Http::timeout(3)
        ->withHeaders(['Accept' => 'application/json'])
        ->get("{$baseUrl}/health");
    
    if ($resp->successful()) {
        echo "  ✅ Health endpoint responding: " . $resp->json('message') . "\n";
        
        // Test login
        $loginResp = \Illuminate\Support\Facades\Http::timeout(3)
            ->withHeaders(['Accept' => 'application/json'])
            ->post("{$baseUrl}/auth/login", [
                'email' => 'admin@smartpkl.id',
                'password' => 'admin123',
            ]);
        
        if ($loginResp->successful() && $loginResp->json('success')) {
            echo "  ✅ Login endpoint: WORKING\n";
        } else {
            echo "  ❌ Login endpoint: FAILED (HTTP " . $loginResp->status() . ")\n";
            echo "     Response: " . json_encode($loginResp->json()) . "\n";
            $allPass = false;
        }
        
        // Test register
        $regEmail = 'http_test_' . time() . '@test.com';
        $regResp = \Illuminate\Support\Facades\Http::timeout(3)
            ->withHeaders(['Accept' => 'application/json'])
            ->post("{$baseUrl}/auth/register", [
                'name' => 'HTTP Test User',
                'email' => $regEmail,
                'password' => 'TestPassword123!',
                'password_confirmation' => 'TestPassword123!',
                'role' => 'student',
            ]);
        
        if ($regResp->successful() && $regResp->json('success')) {
            echo "  ✅ Register endpoint: WORKING\n";
            // Clean up
            $testUser = User::where('email', $regEmail)->first();
            if ($testUser) {
                $testUser->tokens()->delete();
                // Delete related records first
                if ($testUser->student) $testUser->student->delete();
                $testUser->delete();
            }
        } else {
            echo "  ❌ Register endpoint: FAILED (HTTP " . $regResp->status() . ")\n";
            echo "     Response: " . json_encode($regResp->json()) . "\n";
            $allPass = false;
        }
    } else {
        echo "  ⚠️  Server not reachable at {$baseUrl}\n";
        echo "     Start server with: php artisan serve\n";
    }
} catch (Exception $e) {
    echo "  ⚠️  Server not running or not reachable: " . $e->getMessage() . "\n";
    echo "     Start server with: php artisan serve\n";
}

// =====================================================
// SUMMARY
// =====================================================
echo "\n╔══════════════════════════════════════════════════════╗\n";
if ($allPass) {
    echo "║  ✅ ALL TESTS PASSED — Auth should work correctly  ║\n";
} else {
    echo "║  ❌ SOME TESTS FAILED — See details above           ║\n";
}
echo "╚══════════════════════════════════════════════════════╝\n";
