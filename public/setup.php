<?php
// Force show ALL errors
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
set_time_limit(180);

define('SETUP_TOKEN', 'smartmailer_setup_2026');

if (!isset($_GET['token']) || $_GET['token'] !== SETUP_TOKEN) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2>');
}

$action = $_GET['action'] ?? 'menu';
$token  = SETUP_TOKEN;

// ── HTML Header ───────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html>
<head>
<title>SmartMailer Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light" style="max-width:860px;margin:auto">
<h3 class="mb-3 fw-bold">⚙️ SmartMailer Setup</h3>
<div class="alert alert-warning mb-4"><strong>⚠️ Delete this file from server after setup!</strong></div>
<?php

// ── STEP: Generate App Key manually (no Laravel needed) ───────────────────────
if ($action === 'key') {
    echo '<h5 class="mb-3">1. Generate App Key</h5>';

    $envPath = __DIR__ . '/../.env';

    if (!file_exists($envPath)) {
        echo '<div class="alert alert-danger">❌ .env file not found!</div>';
    } else {
        // Generate a random base64 key
        $key     = 'base64:' . base64_encode(random_bytes(32));
        $envContent = file_get_contents($envPath);

        if (strpos($envContent, 'APP_KEY=') !== false) {
            // Replace existing APP_KEY line
            $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $envContent);
        } else {
            // Add APP_KEY after APP_NAME
            $envContent = preg_replace('/^APP_NAME=.*$/m', "APP_NAME=SmartMailer\nAPP_KEY=" . $key, $envContent);
        }

        if (file_put_contents($envPath, $envContent) !== false) {
            echo '<div class="alert alert-success">✅ App key generated and saved to .env!<br><code>APP_KEY=' . htmlspecialchars($key) . '</code></div>';
        } else {
            echo '<div class="alert alert-danger">❌ Could not write to .env file. Check permissions.</div>';
        }
    }

    showMenu($token);
    echo '</body></html>';
    exit;
}

// ── STEP: Diagnostics (no Laravel needed) ─────────────────────────────────────
if ($action === 'diag') {
    echo '<h5>System Diagnostics</h5>';
    $envPath = __DIR__ . '/../.env';
    $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

    // Parse APP_KEY from .env
    preg_match('/^APP_KEY=(.*)$/m', $envContent, $keyMatch);
    $appKey = trim($keyMatch[1] ?? '');

    $rows = [
        'PHP Version'                => PHP_VERSION,
        'vendor/autoload.php exists' => file_exists(__DIR__ . '/../vendor/autoload.php') ? '✅ Yes' : '❌ No',
        '.env file exists'           => file_exists($envPath) ? '✅ Yes' : '❌ No',
        'APP_KEY set'                => !empty($appKey) ? '✅ Yes: ' . substr($appKey, 0, 20) . '...' : '❌ Empty — Run Step 1 first!',
        'bootstrap/cache writable'   => is_writable(__DIR__ . '/../bootstrap/cache') ? '✅ Yes' : '❌ No',
        'storage/ writable'          => is_writable(__DIR__ . '/../storage') ? '✅ Yes' : '❌ No',
        'PDO MySQL'                  => extension_loaded('pdo_mysql') ? '✅ Loaded' : '❌ Missing',
        'OpenSSL'                    => extension_loaded('openssl')   ? '✅ Loaded' : '❌ Missing',
        'Mbstring'                   => extension_loaded('mbstring')  ? '✅ Loaded' : '❌ Missing',
        'max_execution_time'         => ini_get('max_execution_time') . 's',
        'memory_limit'               => ini_get('memory_limit'),
    ];

    echo '<table class="table table-bordered bg-white">';
    foreach ($rows as $k => $v) {
        $class = str_contains((string)$v, '❌') ? 'table-danger' : 'table-success';
        echo "<tr class=\"$class\"><td><strong>$k</strong></td><td>" . htmlspecialchars((string)$v) . "</td></tr>";
    }
    echo '</table>';

    // Show .env (hide sensitive values)
    if (file_exists($envPath)) {
        $display = preg_replace('/(PASSWORD=|SECRET=|KEY=(?!base64)).+/i', '$1[hidden]', $envContent);
        echo '<h6>Current .env</h6>';
        echo '<pre class="bg-dark text-success p-3 rounded" style="font-size:.78rem">' . htmlspecialchars($display) . '</pre>';
    }

    showMenu($token);
    echo '</body></html>';
    exit;
}

// ── Bootstrap Laravel for remaining steps ─────────────────────────────────────
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo '<div class="alert alert-danger">❌ vendor/autoload.php not found. Run PHP Composer Install first.</div>';
    showMenu($token);
    echo '</body></html>';
    exit;
}

if (!file_exists(__DIR__ . '/../.env')) {
    echo '<div class="alert alert-danger">❌ .env file not found. Create it via Plesk File Manager.</div>';
    showMenu($token);
    echo '</body></html>';
    exit;
}

// Check APP_KEY before bootstrapping
$envContent = file_get_contents(__DIR__ . '/../.env');
preg_match('/^APP_KEY=(.*)$/m', $envContent, $keyMatch);
$appKey = trim($keyMatch[1] ?? '');

if (empty($appKey)) {
    echo '<div class="alert alert-danger">❌ <strong>APP_KEY is empty!</strong> Run <strong>Step 1 — Generate App Key</strong> first, then come back here.</div>';
    showMenu($token);
    echo '</body></html>';
    exit;
}

// Bootstrap
echo '<div class="alert alert-info">⏳ Bootstrapping Laravel...</div>';
flush();

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app    = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo '<div class="alert alert-success">✅ Laravel bootstrapped successfully.</div>';
    flush();
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">';
    echo '<strong>❌ Laravel bootstrap failed:</strong><br><code>';
    echo htmlspecialchars($e->getMessage());
    echo '</code><br><small>' . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . '</small>';
    echo '</div>';
    showMenu($token);
    echo '</body></html>';
    exit;
}

// ── Artisan commands ──────────────────────────────────────────────────────────
function runCmd(string $label, string $command, array $params = []): void
{
    echo '<p class="mb-1"><strong>' . htmlspecialchars($label) . '</strong></p>';
    flush();
    try {
        \Illuminate\Support\Facades\Artisan::call($command, $params);
        $out = \Illuminate\Support\Facades\Artisan::output();
        echo '<pre class="bg-dark text-success p-3 rounded mb-3" style="font-size:.8rem">' . htmlspecialchars($out ?: '✅ Done.') . '</pre>';
    } catch (\Throwable $e) {
        echo '<pre class="bg-dark text-danger p-3 rounded mb-3">❌ ' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
    flush();
}

switch ($action) {

    case 'enable_debug':
        $envPath3 = __DIR__ . '/../.env';
        $envC = file_get_contents($envPath3);
        $envC = preg_replace('/^APP_DEBUG=.*$/m', 'APP_DEBUG=true', $envC);
        file_put_contents($envPath3, $envC);
        runCmd('Clearing config cache (debug mode ON)...', 'config:clear');
        echo '<div class="alert alert-warning mt-2">
            ⚠️ <strong>Debug mode ENABLED.</strong><br>
            Now visit <a href="/login" target="_blank">/login</a>, click Sign In, and you will see the real error.<br>
            Come back here and click <strong>"Disable Debug"</strong> when done.
        </div>';
        break;

    case 'disable_debug':
        $envPath3 = __DIR__ . '/../.env';
        $envC = file_get_contents($envPath3);
        $envC = preg_replace('/^APP_DEBUG=.*$/m', 'APP_DEBUG=false', $envC);
        file_put_contents($envPath3, $envC);
        runCmd('Clearing config cache...', 'config:clear');
        runCmd('Re-caching config...', 'config:cache');
        echo '<div class="alert alert-success mt-2">✅ Debug mode disabled and config re-cached.</div>';
        break;

    case 'migrate_seed':
        echo '<h5 class="mb-3">2. Run Migrations + Seed</h5>';
        runCmd('Creating database tables...', 'migrate', ['--force' => true]);
        runCmd('Seeding plans and admin user...', 'db:seed', ['--force' => true]);
        echo '<div class="alert alert-success mt-3">
            ✅ <strong>Database ready!</strong><br>
            Admin login: <code>admin@smartmailer.com</code> / <code>Admin@123456</code>
        </div>';
        break;

    case 'migrate_fresh':
        echo '<h5 class="mb-3">2b. Fresh Migration + Seed (DROP ALL TABLES &amp; RECREATE)</h5>';
        echo '<div class="alert alert-warning">⚠️ This drops ALL tables and recreates them. Safe during initial setup.</div>';
        runCmd('Dropping all tables and running fresh migrations...', 'migrate:fresh', ['--force' => true]);
        runCmd('Seeding plans and admin user...', 'db:seed', ['--force' => true]);
        echo '<div class="alert alert-success mt-3">
            ✅ <strong>Database ready!</strong><br>
            Admin login: <code>admin@smartmailer.com</code> / <code>Admin@123456</code>
        </div>';
        break;

    case 'storage':
        echo '<h5 class="mb-3">3. Create Storage Link</h5>';
        runCmd('Creating storage symlink...', 'storage:link', ['--force' => true]);
        break;

    case 'cache_clear':
        echo '<h5 class="mb-3">4. Clear All Caches</h5>';

        // Laravel 11 uses CACHE_STORE (not CACHE_DRIVER) — auto-fix the .env
        $envPath2 = __DIR__ . '/../.env';
        $envContent2 = file_get_contents($envPath2);
        $needsWrite = false;

        // Ensure CACHE_STORE=file is set
        if (preg_match('/^CACHE_STORE=/m', $envContent2)) {
            $envContent2 = preg_replace('/^CACHE_STORE=.*$/m', 'CACHE_STORE=file', $envContent2);
            $needsWrite = true;
        } elseif (preg_match('/^CACHE_DRIVER=/m', $envContent2)) {
            // Add CACHE_STORE after CACHE_DRIVER line
            $envContent2 = preg_replace('/^(CACHE_DRIVER=.*)$/m', "$1\nCACHE_STORE=file", $envContent2);
            $needsWrite = true;
        } else {
            $envContent2 .= "\nCACHE_STORE=file\n";
            $needsWrite = true;
        }

        if ($needsWrite) {
            file_put_contents($envPath2, $envContent2);
            echo '<div class="alert alert-info">ℹ️ Added <code>CACHE_STORE=file</code> to .env (Laravel 11 requirement).</div>';
        }

        runCmd('Clearing config cache...', 'config:clear');
        runCmd('Clearing app cache...', 'cache:clear');
        runCmd('Clearing route cache...', 'route:clear');
        runCmd('Clearing view cache...', 'view:clear');
        echo '<div class="alert alert-success">✅ All caches cleared.</div>';
        break;

    case 'optimize':
        echo '<h5 class="mb-3">5. Optimize for Production</h5>';
        runCmd('Caching config...', 'config:cache');
        runCmd('Caching routes...', 'route:cache');
        runCmd('Caching views...', 'view:cache');
        echo '<div class="alert alert-success">✅ Optimized for production.</div>';
        break;

    case 'env_check':
        echo '<h5 class="mb-3">0. Environment Check</h5>';
        $rows = [
            'PHP Version'    => PHP_VERSION,
            'APP_KEY set'    => !empty(env('APP_KEY')) ? '✅ Yes' : '❌ No',
            'APP_ENV'        => env('APP_ENV'),
            'APP_URL'        => env('APP_URL'),
            'DB_DATABASE'    => env('DB_DATABASE'),
            'DB_HOST'        => env('DB_HOST'),
            'CACHE_STORE'    => env('CACHE_STORE', '(not set — defaults to database)'),
            'CACHE_DRIVER'   => env('CACHE_DRIVER', '(not set)'),
            'SESSION_DRIVER' => env('SESSION_DRIVER'),
            'QUEUE_CONNECTION' => env('QUEUE_CONNECTION'),
            'storage/sessions writable' => is_writable(__DIR__ . '/../storage/framework/sessions') ? '✅ Yes' : '❌ No',
        ];
        echo '<table class="table table-bordered bg-white">';
        foreach ($rows as $k => $v) {
            $class = str_contains((string)$v, '❌') ? 'table-danger' : (str_contains((string)$v, 'database') || str_contains((string)$v, 'not set') ? 'table-warning' : '');
            echo "<tr class=\"$class\"><td><strong>$k</strong></td><td>" . htmlspecialchars((string)$v) . "</td></tr>";
        }
        echo '</table>';
        break;

    case 'logs':
        echo '<h5 class="mb-3">Laravel Error Log (last 50 lines)</h5>';
        $logFile = __DIR__ . '/../storage/logs/laravel.log';
        if (!file_exists($logFile)) {
            echo '<div class="alert alert-info">No log file found yet.</div>';
        } else {
            $lines = file($logFile);
            $last  = array_slice($lines, -50);
            echo '<pre class="bg-dark text-warning p-3 rounded" style="font-size:.72rem;max-height:600px;overflow-y:auto">';
            echo htmlspecialchars(implode('', $last));
            echo '</pre>';
        }
        break;

    case 'clear_log':
        $logFile = __DIR__ . '/../storage/logs/laravel.log';
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            echo '<div class="alert alert-success">✅ Log file cleared. Now try visiting /login again, then check logs.</div>';
        } else {
            echo '<div class="alert alert-info">No log file to clear.</div>';
        }
        break;

    case 'check_db':
        echo '<h5 class="mb-3">Database Tables Check</h5>';
        try {
            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $db  = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
            echo '<div class="alert alert-info">Connected to database: <strong>' . htmlspecialchars($db) . '</strong></div>';
            $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
            if (empty($tables)) {
                echo '<div class="alert alert-danger">❌ No tables found in database <strong>' . htmlspecialchars($db) . '</strong>!</div>';
            } else {
                echo '<div class="alert alert-success">✅ Found ' . count($tables) . ' tables:</div>';
                echo '<ul class="list-group">';
                foreach ($tables as $t) {
                    $t = (array)$t;
                    echo '<li class="list-group-item py-1">' . htmlspecialchars(array_values($t)[0]) . '</li>';
                }
                echo '</ul>';
            }
        } catch (\Throwable $e) {
            echo '<div class="alert alert-danger">❌ DB Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        break;

    case 'fix_session':
        echo '<h5 class="mb-3">Fix Session &amp; Storage Permissions</h5>';
        // Ensure session directory exists
        $sessionPath = __DIR__ . '/../storage/framework/sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0775, true);
            echo '<div class="alert alert-success">✅ Created storage/framework/sessions directory.</div>';
        } else {
            echo '<div class="alert alert-info">ℹ️ Session directory exists.</div>';
        }
        // Test write
        $testFile = $sessionPath . '/test_' . time();
        if (file_put_contents($testFile, 'test') !== false) {
            unlink($testFile);
            echo '<div class="alert alert-success">✅ Session directory is writable.</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Session directory NOT writable — fix permissions in Plesk File Manager (set storage/ to 0775).</div>';
        }
        // Fix CACHE_STORE in .env
        $envPathFix = __DIR__ . '/../.env';
        $envFix = file_get_contents($envPathFix);
        if (!preg_match('/^CACHE_STORE=/m', $envFix)) {
            if (preg_match('/^CACHE_DRIVER=/m', $envFix)) {
                $envFix = preg_replace('/^(CACHE_DRIVER=.*)$/m', "$1\nCACHE_STORE=file", $envFix);
            } else {
                $envFix .= "\nCACHE_STORE=file\n";
            }
            file_put_contents($envPathFix, $envFix);
            echo '<div class="alert alert-success">✅ Added CACHE_STORE=file to .env.</div>';
        } else {
            echo '<div class="alert alert-info">ℹ️ CACHE_STORE already set in .env.</div>';
        }
        runCmd('Re-clearing config cache...', 'config:clear');
        runCmd('Re-caching config...', 'config:cache');
        echo '<div class="alert alert-success mt-2">✅ Done. Try visiting <a href="/login" target="_blank">/login</a> now.</div>';
        break;

    default:
        echo '<p class="text-muted">Run each step in order. Start with <strong>Diagnostics</strong> to check the server.</p>';
}

showMenu($token);
echo '</body></html>';

// ── Menu ──────────────────────────────────────────────────────────────────────
function showMenu(string $token): void
{
    echo '
    <div class="list-group mt-4">
        <a href="?token=' . $token . '&action=diag" class="list-group-item list-group-item-action list-group-item-warning">
            🔍 <strong>Diagnostics</strong>
        </a>
        <a href="?token=' . $token . '&action=logs" class="list-group-item list-group-item-action list-group-item-warning">
            📋 <strong>View Error Logs</strong>
        </a>
        <a href="?token=' . $token . '&action=clear_log" class="list-group-item list-group-item-action list-group-item-warning">
            🗑️ <strong>Clear Error Log</strong>
        </a>
        <a href="?token=' . $token . '&action=check_db" class="list-group-item list-group-item-action list-group-item-warning">
            🗄️ <strong>Check DB Tables</strong>
        </a>
        <a href="?token=' . $token . '&action=fix_session" class="list-group-item list-group-item-action list-group-item-warning">
            🔧 <strong>Fix Session / Cache</strong>
        </a>
        <a href="?token=' . $token . '&action=enable_debug" class="list-group-item list-group-item-action list-group-item-danger">
            🐛 <strong>Enable Debug Mode</strong> (see real errors)
        </a>
        <a href="?token=' . $token . '&action=disable_debug" class="list-group-item list-group-item-action list-group-item-success">
            ✅ <strong>Disable Debug Mode</strong>
        </a>
        <a href="?token=' . $token . '&action=env_check" class="list-group-item list-group-item-action">
            <strong>0.</strong> Environment Check
        </a>
        <a href="?token=' . $token . '&action=key" class="list-group-item list-group-item-action">
            <strong>1.</strong> Generate App Key
        </a>
        <a href="?token=' . $token . '&action=migrate_seed" class="list-group-item list-group-item-action list-group-item-primary">
            <strong>2.</strong> Run Migrations + Seed
        </a>
        <a href="?token=' . $token . '&action=migrate_fresh" class="list-group-item list-group-item-action list-group-item-danger">
            <strong>2b.</strong> Fresh Migration + Seed (DROP &amp; RECREATE ALL TABLES)
        </a>
        <a href="?token=' . $token . '&action=storage" class="list-group-item list-group-item-action">
            <strong>3.</strong> Create Storage Link
        </a>
        <a href="?token=' . $token . '&action=cache_clear" class="list-group-item list-group-item-action">
            <strong>4.</strong> Clear All Caches
        </a>
        <a href="?token=' . $token . '&action=optimize" class="list-group-item list-group-item-action">
            <strong>5.</strong> Optimize for Production
        </a>
    </div>
    <div class="alert alert-danger mt-4">
        🗑️ <strong>Delete <code>public/setup.php</code> after setup is complete!</strong>
    </div>';
}
