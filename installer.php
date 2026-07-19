<?php

/**
 * Akaunting JalaliDate Full Installer & Core Patcher
 * Target: Akaunting 3.x
 */

if (!function_exists('exec') || !function_exists('passthru')) {
    die("❌ Error: 'exec' or 'passthru' functions are disabled on this server. Please enable them in php.ini or contact support.");
}

define('MODULE_NAME', 'JalaliDate');
define('MODULE_ALIAS', 'jalali-date');
define('REPO_URL', 'https://github.com/Abdipour/akaunting-jalali-date/archive/refs/heads/main.zip');
define('TRAIT_PATH', '/app/Traits/');

echo "🚀 Starting JalaliDate Installation...\n";

$basePath = getcwd();

// 1. check access to Trait file
$traitFiles = ['Modules.php', 'Plans.php'];
foreach ($traitFiles as $fileName) {
    $traitFile = $basePath . TRAIT_PATH . $fileName;
    if (file_exists($traitFile)) {
        copy($traitFile, $traitFile . '.bak');
        echo "[✓] " . $fileName . " backup created at " . TRAIT_PATH . ".bak\n";
    } else {
        die("❌ Error: Core file not found at $traitFile.\n");
    }
}

// 2. Download and extract module
echo "📦 Downloading module from GitHub...\n";
$zipFile = "module-temp.zip";
file_put_contents($zipFile, fopen(REPO_URL, 'r'));

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $extractPath = $basePath . '/modules/temp_extract';
    $zip->extractTo($extractPath);
    $zip->close();
    unlink($zipFile);

    $folders = glob($extractPath . '/*', GLOB_ONLYDIR);
    $modulePath = $basePath . '/modules/' . MODULE_NAME;

    if (is_dir($modulePath)) {
        echo "⚠️ Warning: Module folder already exists. Overwriting...\n";
        exec("rm -rf " . escapeshellarg($modulePath));
    }

    rename($folders[0], $modulePath);
    exec("rm -rf " . escapeshellarg($extractPath));
    echo "[✓] Module files placed in /modules/" . MODULE_NAME . "\n";
} else {
    die("❌ Error: Failed to extract ZIP file.\n");
}

// 3. Check and install Composer
echo "⚙️ Checking Composer...\n";
$composerPath = $basePath . '/composer.phar';
exec("composer --version", $output, $returnVar);

if ($returnVar !== 0) {
    $composerCmd = "php " . $composerPath;
    exec($composerCmd . " --version", $output, $returnVar);
    if ($returnVar !== 0) {
        echo "⚠️ Local Composer not found. Downloading composer.phar...\n";
        copy('https://getcomposer.org/composer.phar', $composerPath);
    }
} else {
    $composerCmd = "composer";
}

// 4. Install Dependencies
echo "⏳ Running 'composer install' for module (This may take a minute)...\n";
chdir($basePath . '/modules/' . MODULE_NAME);
passthru("$composerCmd install --no-dev --ignore-platform-reqs");
chdir($basePath);

// 5. Patching core (Modules Trait)
echo "🛠 Patching Core Modules Trait to prevent auto-uninstall...\n";
$traitFile = $basePath . TRAIT_PATH . 'Modules.php';
$content = file_get_contents($traitFile);

if (strpos($content, MODULE_ALIAS) !== false) {
    echo "[✓] Core Modules Trait is already patched.\n";
} else {
    // edit the file to add our module to the whitelist
    $search = "if (\$alias == 'core') {";
    $replace = "if (\$alias == 'core' || \$alias == '" . MODULE_ALIAS . "') {";
    $newContent = str_replace($search, $replace, $content);

    if (file_put_contents($traitFile, $newContent)) {
        echo "[✓] Core Modules Trait Patched: Module added to whitelist.\n";
    } else {
        echo "❌ Error: Could not write to $traitFile. Check permissions.\n";
    }
}


// 6. Patching core (Plans Trait)
echo "🛠 Patching Core Plans Trait to remove limits and work offline...\n";
$traitFile = $basePath . TRAIT_PATH . 'Plans.php';
$content = file_get_contents($traitFile);

if (strpos($content, 'unlimit') !== false) {
    echo "[✓] Core Plans Trait is already patched.\n";
} else {
    // edit the file to bypass plans check to remove limits
    $search = "\$key = 'plans.limits';";
    $replace = "\$key = 'plans.limits';\n
        \$unlimit = new \stdClass();
        \$unlimit->action_status = true;
        \$unlimit->view_status = true;
        \$unlimit->message = \"\";\n
        \$data = new \stdClass();
        \$data->user = \$unlimit;
        \$data->company = \$unlimit;
        \$data->invoice = \$unlimit;\n
        return Cache::remember(\$key, Date::now()->addHour(), fn() => \$data);";

    $newContent = str_replace($search, $replace, $content);

    if (file_put_contents($traitFile, $newContent)) {
        echo "[✓] Core Plans Trait Patched: Plan check bypassed.\n";
    } else {
        echo "❌ Error: Could not write to $traitFile. Check permissions.\n";
    }
}

// 7. Activate Module
echo "🚀 Activating module in Akaunting...\n";
passthru("php artisan module:install " . MODULE_NAME . " 1");
passthru("php artisan optimize:clear");

echo "\n✨ INSTALLATION COMPLETE! ✨\n";
echo "You can now use Jalali Date in your Akaunting dashboard without limitations.\n";
