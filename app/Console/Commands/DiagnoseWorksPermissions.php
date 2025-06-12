<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DiagnoseWorksPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'works:diagnose-permissions {--user-email= : Check permissions for specific user email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose Works Management permissions and display detailed information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Works Management Permissions Diagnostic');
        $this->info('==========================================');

        // Check if 'manage works' permission exists
        $this->checkPermissionExists();

        // Check roles and their permissions
        $this->checkRolePermissions();

        // Check specific user if provided
        if ($this->option('user-email')) {
            $this->checkUserPermissions($this->option('user-email'));
        } else {
            // Check all users
            $this->checkAllUsers();
        }

        // Check database tables
        $this->checkDatabaseTables();

        $this->info('');
        $this->info('✅ Diagnostic complete!');
    }

    private function checkPermissionExists()
    {
        $this->info('');
        $this->info('📋 Checking Permissions:');
        $this->line('------------------------');

        $permission = Permission::where('name', 'manage works')->first();
        
        if ($permission) {
            $this->info("✅ 'manage works' permission exists (ID: {$permission->id})");
        } else {
            $this->error("❌ 'manage works' permission does NOT exist");
            $this->warn("   Run: php artisan db:seed --class=RoleSeeder to create it");
        }

        // List all permissions
        $permissions = Permission::all();
        $this->info("📊 Total permissions in system: " . $permissions->count());
        
        foreach ($permissions as $perm) {
            $this->line("   - {$perm->name}");
        }
    }

    private function checkRolePermissions()
    {
        $this->info('');
        $this->info('👥 Checking Roles and Permissions:');
        $this->line('----------------------------------');

        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            $this->info("🔑 Role: {$role->name}");
            
            $hasWorksPermission = $role->permissions->contains('name', 'manage works');
            
            if ($hasWorksPermission) {
                $this->info("   ✅ Has 'manage works' permission");
            } else {
                $this->warn("   ❌ Does NOT have 'manage works' permission");
            }

            $this->line("   📋 All permissions for this role:");
            foreach ($role->permissions as $permission) {
                $this->line("      - {$permission->name}");
            }
            $this->line('');
        }
    }

    private function checkUserPermissions($email)
    {
        $this->info('');
        $this->info("👤 Checking User: {$email}");
        $this->line('---------------------------');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ User with email '{$email}' not found");
            return;
        }

        $this->info("✅ User found: {$user->full_name}");

        // Check roles
        $roles = $user->getRoleNames();
        $this->info("🔑 User roles: " . $roles->implode(', '));

        // Check direct permissions
        $directPermissions = $user->getDirectPermissions();
        $this->info("📋 Direct permissions: " . $directPermissions->pluck('name')->implode(', '));

        // Check all permissions (including via roles)
        $allPermissions = $user->getAllPermissions();
        $this->info("📊 All permissions (via roles): " . $allPermissions->pluck('name')->implode(', '));

        // Check specific 'manage works' permission
        if ($user->can('manage works')) {
            $this->info("✅ User CAN access 'manage works'");
        } else {
            $this->warn("❌ User CANNOT access 'manage works'");
        }
    }

    private function checkAllUsers()
    {
        $this->info('');
        $this->info('👥 Checking All Users:');
        $this->line('---------------------');

        $users = User::with('roles')->get();

        foreach ($users as $user) {
            $roles = $user->getRoleNames()->implode(', ');
            $canManageWorks = $user->can('manage works') ? '✅' : '❌';
            
            $this->line("{$canManageWorks} {$user->full_name} ({$user->email}) - Roles: {$roles}");
        }
    }

    private function checkDatabaseTables()
    {
        $this->info('');
        $this->info('🗄️ Checking Database Tables:');
        $this->line('----------------------------');

        try {
            // Check works table
            $worksCount = \DB::table('works')->count();
            $this->info("✅ Works table exists with {$worksCount} records");

            // Check permissions table
            $permissionsCount = \DB::table('permissions')->count();
            $this->info("✅ Permissions table exists with {$permissionsCount} records");

            // Check roles table
            $rolesCount = \DB::table('roles')->count();
            $this->info("✅ Roles table exists with {$rolesCount} records");

            // Check role_has_permissions table
            $rolePermissionsCount = \DB::table('role_has_permissions')->count();
            $this->info("✅ Role_has_permissions table exists with {$rolePermissionsCount} records");

            // Check model_has_roles table
            $modelRolesCount = \DB::table('model_has_roles')->count();
            $this->info("✅ Model_has_roles table exists with {$modelRolesCount} records");

        } catch (\Exception $e) {
            $this->error("❌ Database error: " . $e->getMessage());
        }
    }
}
