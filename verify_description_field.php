<?php
/**
 * Works Description Field Verification Script
 * 
 * This script verifies that the description field has been properly implemented
 * Place this file in your Laravel root directory and run: php verify_description_field.php
 */

echo "📝 Works Description Field Implementation Verification\n";
echo "===================================================\n\n";

// Check if we're in a Laravel environment
if (!file_exists('artisan')) {
    echo "❌ Error: This script must be run from the Laravel root directory\n";
    exit(1);
}

$issues = 0;

// Check migration file
echo "📁 Checking Migration File...\n";
$migrationPath = 'database/migrations/2025_01_27_100000_add_description_to_works_table.php';

if (file_exists($migrationPath)) {
    echo "✅ Migration file exists: {$migrationPath}\n";
    
    $migrationContent = file_get_contents($migrationPath);
    if (strpos($migrationContent, "table->text('description')->nullable()") !== false) {
        echo "✅ Migration contains correct description column definition\n";
    } else {
        echo "❌ Migration missing correct description column definition\n";
        $issues++;
    }
} else {
    echo "❌ Migration file not found: {$migrationPath}\n";
    $issues++;
}

// Check Work model
echo "\n📁 Checking Work Model...\n";
$modelPath = 'app/Models/Work.php';

if (file_exists($modelPath)) {
    echo "✅ Work model exists\n";
    
    $modelContent = file_get_contents($modelPath);
    if (strpos($modelContent, "'description'") !== false) {
        echo "✅ Model includes 'description' in fillable attributes\n";
    } else {
        echo "❌ Model missing 'description' in fillable attributes\n";
        $issues++;
    }
} else {
    echo "❌ Work model not found\n";
    $issues++;
}

// Check WorkController
echo "\n📁 Checking WorkController...\n";
$controllerPath = 'app/Http/Controllers/WorkController.php';

if (file_exists($controllerPath)) {
    echo "✅ WorkController exists\n";
    
    $controllerContent = file_get_contents($controllerPath);
    
    // Check store method validation
    if (strpos($controllerContent, "'description' => 'nullable|string|max:5000'") !== false) {
        echo "✅ Store method has correct description validation\n";
    } else {
        echo "❌ Store method missing correct description validation\n";
        $issues++;
    }
    
    // Check update method validation
    $updateValidationCount = substr_count($controllerContent, "'description' => 'nullable|string|max:5000'");
    if ($updateValidationCount >= 2) {
        echo "✅ Update method has correct description validation\n";
    } else {
        echo "❌ Update method missing correct description validation\n";
        $issues++;
    }
} else {
    echo "❌ WorkController not found\n";
    $issues++;
}

// Check create view
echo "\n📁 Checking Create View...\n";
$createViewPath = 'resources/views/works/create.blade.php';

if (file_exists($createViewPath)) {
    echo "✅ Create view exists\n";
    
    $createContent = file_get_contents($createViewPath);
    
    if (strpos($createContent, 'name="description"') !== false) {
        echo "✅ Create view has description textarea\n";
    } else {
        echo "❌ Create view missing description textarea\n";
        $issues++;
    }
    
    if (strpos($createContent, 'Descrizione') !== false) {
        echo "✅ Create view has description label\n";
    } else {
        echo "❌ Create view missing description label\n";
        $issues++;
    }
    
    if (strpos($createContent, 'col-span-1 md:col-span-2') !== false) {
        echo "✅ Create view has full-width description field\n";
    } else {
        echo "❌ Create view missing full-width description field\n";
        $issues++;
    }
} else {
    echo "❌ Create view not found\n";
    $issues++;
}

// Check edit view
echo "\n📁 Checking Edit View...\n";
$editViewPath = 'resources/views/works/edit.blade.php';

if (file_exists($editViewPath)) {
    echo "✅ Edit view exists\n";
    
    $editContent = file_get_contents($editViewPath);
    
    if (strpos($editContent, 'name="description"') !== false) {
        echo "✅ Edit view has description textarea\n";
    } else {
        echo "❌ Edit view missing description textarea\n";
        $issues++;
    }
    
    if (strpos($editContent, '$work->description') !== false) {
        echo "✅ Edit view populates existing description value\n";
    } else {
        echo "❌ Edit view missing existing description value\n";
        $issues++;
    }
} else {
    echo "❌ Edit view not found\n";
    $issues++;
}

// Check show view
echo "\n📁 Checking Show View...\n";
$showViewPath = 'resources/views/works/show.blade.php';

if (file_exists($showViewPath)) {
    echo "✅ Show view exists\n";
    
    $showContent = file_get_contents($showViewPath);
    
    if (strpos($showContent, '@if($work->description)') !== false) {
        echo "✅ Show view has conditional description display\n";
    } else {
        echo "❌ Show view missing conditional description display\n";
        $issues++;
    }
    
    if (strpos($showContent, 'nl2br(e($work->description))') !== false) {
        echo "✅ Show view has proper XSS protection and line break formatting\n";
    } else {
        echo "❌ Show view missing proper XSS protection or line break formatting\n";
        $issues++;
    }
} else {
    echo "❌ Show view not found\n";
    $issues++;
}

// Check index view (should NOT have description)
echo "\n📁 Checking Index View (should NOT show description)...\n";
$indexViewPath = 'resources/views/works/index.blade.php';

if (file_exists($indexViewPath)) {
    echo "✅ Index view exists\n";
    
    $indexContent = file_get_contents($indexViewPath);
    
    // Check that description is NOT displayed in the table
    if (strpos($indexContent, '$work->description') === false && 
        strpos($indexContent, 'Descrizione') === false) {
        echo "✅ Index view correctly does NOT display description\n";
    } else {
        echo "❌ Index view incorrectly displays description (should be hidden)\n";
        $issues++;
    }
} else {
    echo "❌ Index view not found\n";
    $issues++;
}

// Check WorkSeeder
echo "\n📁 Checking WorkSeeder...\n";
$seederPath = 'database/seeders/WorkSeeder.php';

if (file_exists($seederPath)) {
    echo "✅ WorkSeeder exists\n";
    
    $seederContent = file_get_contents($seederPath);
    
    if (strpos($seederContent, "'description' =>") !== false) {
        echo "✅ WorkSeeder includes sample descriptions\n";
    } else {
        echo "⚠️  WorkSeeder missing sample descriptions (optional)\n";
    }
} else {
    echo "⚠️  WorkSeeder not found (optional)\n";
}

// Check SQL update file
echo "\n📁 Checking SQL Update File...\n";
$sqlPath = 'database_works_update.sql';

if (file_exists($sqlPath)) {
    echo "✅ SQL update file exists\n";
    
    $sqlContent = file_get_contents($sqlPath);
    
    if (strpos($sqlContent, '`description` text DEFAULT NULL') !== false) {
        echo "✅ SQL file includes description column\n";
    } else {
        echo "❌ SQL file missing description column\n";
        $issues++;
    }
    
    if (strpos($sqlContent, 'ALTER TABLE `works` ADD COLUMN `description`') !== false) {
        echo "✅ SQL file includes ALTER TABLE statement for existing installations\n";
    } else {
        echo "❌ SQL file missing ALTER TABLE statement\n";
        $issues++;
    }
} else {
    echo "⚠️  SQL update file not found (optional)\n";
}

// Summary
echo "\n📊 Summary:\n";
echo "----------\n";

if ($issues === 0) {
    echo "✅ All checks passed! Description field implementation is complete.\n";
    echo "\n🎯 Next Steps:\n";
    echo "1. Run migration: php artisan migrate\n";
    echo "2. Test creating a work with description\n";
    echo "3. Test editing a work to add/modify description\n";
    echo "4. Verify description shows in details view\n";
    echo "5. Verify description is hidden in works list\n";
} else {
    echo "⚠️  {$issues} issue(s) found. Please review the errors above.\n";
    echo "\n🔧 Common fixes:\n";
    echo "1. Ensure all files have been properly updated\n";
    echo "2. Check for typos in field names and validation rules\n";
    echo "3. Verify proper escaping in view files\n";
    echo "4. Confirm migration file has correct column definition\n";
}

echo "\n✅ Verification complete!\n";
?>
