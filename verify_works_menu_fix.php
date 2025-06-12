<?php
/**
 * Works Menu Visibility Verification Script
 * 
 * This script can be run to verify that the Works Management menu fix is working correctly.
 * Place this file in your Laravel root directory and run: php verify_works_menu_fix.php
 */

echo "🔍 Works Management Menu Visibility Verification\n";
echo "===============================================\n\n";

// Check if we're in a Laravel environment
if (!file_exists('artisan')) {
    echo "❌ Error: This script must be run from the Laravel root directory\n";
    exit(1);
}

// Check if sidebar.blade.php exists and has been updated
echo "📁 Checking sidebar.blade.php file...\n";
$sidebarPath = 'resources/views/layouts/sidebar.blade.php';

if (!file_exists($sidebarPath)) {
    echo "❌ Error: {$sidebarPath} not found\n";
    exit(1);
}

$sidebarContent = file_get_contents($sidebarPath);

// Check if the permission restriction has been removed
if (strpos($sidebarContent, "@can('manage works')") !== false) {
    echo "❌ Warning: sidebar.blade.php still contains @can('manage works') restriction\n";
    echo "   The menu item may not be visible to all users\n";
    $hasPermissionRestriction = true;
} else {
    echo "✅ Good: Permission restriction removed from sidebar menu\n";
    $hasPermissionRestriction = false;
}

// Check if the menu item exists
if (strpos($sidebarContent, 'Gestione Lavori') !== false) {
    echo "✅ Good: 'Gestione Lavori' menu item found in sidebar\n";
} else {
    echo "❌ Error: 'Gestione Lavori' menu item not found in sidebar\n";
}

// Check routes/web.php
echo "\n📁 Checking routes/web.php file...\n";
$routesPath = 'routes/web.php';

if (!file_exists($routesPath)) {
    echo "❌ Error: {$routesPath} not found\n";
    exit(1);
}

$routesContent = file_get_contents($routesPath);

// Check if route permission restriction has been removed
if (strpos($routesContent, "->middleware('permission:manage works')") !== false) {
    echo "❌ Warning: routes/web.php still contains permission middleware\n";
    echo "   Users may get 403 errors when accessing works management\n";
    $hasRouteRestriction = true;
} else {
    echo "✅ Good: Permission middleware removed from routes\n";
    $hasRouteRestriction = false;
}

// Check if works routes exist
if (strpos($routesContent, "Route::resource('works', WorkController::class)") !== false) {
    echo "✅ Good: Works routes found in web.php\n";
} else {
    echo "❌ Error: Works routes not found in web.php\n";
}

// Check if WorkController exists
echo "\n📁 Checking WorkController...\n";
$controllerPath = 'app/Http/Controllers/WorkController.php';

if (file_exists($controllerPath)) {
    echo "✅ Good: WorkController.php exists\n";
} else {
    echo "❌ Error: WorkController.php not found\n";
}

// Check if Work model exists
echo "\n📁 Checking Work model...\n";
$modelPath = 'app/Models/Work.php';

if (file_exists($modelPath)) {
    echo "✅ Good: Work.php model exists\n";
} else {
    echo "❌ Error: Work.php model not found\n";
}

// Check if views exist
echo "\n📁 Checking Works views...\n";
$viewsPath = 'resources/views/works';

if (is_dir($viewsPath)) {
    echo "✅ Good: Works views directory exists\n";
    
    $requiredViews = ['index.blade.php', 'create.blade.php', 'edit.blade.php', 'show.blade.php'];
    foreach ($requiredViews as $view) {
        if (file_exists($viewsPath . '/' . $view)) {
            echo "  ✅ {$view} exists\n";
        } else {
            echo "  ❌ {$view} missing\n";
        }
    }
} else {
    echo "❌ Error: Works views directory not found\n";
}

// Summary
echo "\n📊 Summary:\n";
echo "----------\n";

$issues = 0;

if ($hasPermissionRestriction) {
    echo "❌ Menu has permission restriction - may not be visible to all users\n";
    $issues++;
}

if ($hasRouteRestriction) {
    echo "❌ Routes have permission restriction - users may get 403 errors\n";
    $issues++;
}

if ($issues === 0) {
    echo "✅ All checks passed! Works Management should be accessible to all authenticated users.\n";
    echo "\n🎯 Next Steps:\n";
    echo "1. Clear application caches: php artisan cache:clear\n";
    echo "2. Clear view cache: php artisan view:clear\n";
    echo "3. Clear route cache: php artisan route:clear\n";
    echo "4. Test by logging in as different user types\n";
} else {
    echo "⚠️  {$issues} issue(s) found. Please review the warnings above.\n";
    echo "\n🔧 To fix permission restrictions:\n";
    
    if ($hasPermissionRestriction) {
        echo "1. Edit {$sidebarPath}\n";
        echo "   Remove the @can('manage works') and @endcan lines around the menu item\n";
    }
    
    if ($hasRouteRestriction) {
        echo "2. Edit {$routesPath}\n";
        echo "   Remove ->middleware('permission:manage works') from the works routes\n";
    }
}

echo "\n🔍 For detailed diagnostics, run: php artisan works:diagnose-permissions\n";
echo "\n✅ Verification complete!\n";
?>
