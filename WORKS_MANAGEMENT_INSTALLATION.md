# 🛠️ Gestione Lavori - Installation Guide

## 📋 Overview

This guide provides complete instructions for installing the "Gestione Lavori" (Work Management) feature in the ProgrammArti Gestionale system.

## 🚀 Quick Installation

### Method 1: Laravel Artisan Commands (Recommended)

```bash
# 1. Run the migration
php artisan migrate

# 2. Seed the database with permissions and sample data
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=WorkSeeder

# 3. Clear cache
php artisan cache:clear
php artisan config:clear
```

### Method 2: Direct MySQL Import

```bash
# Import the database update file
mysql -u your_username -p your_database_name < database_works_update.sql
```

## 📊 Database Schema

### New Table: `works`

```sql
CREATE TABLE `works` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('Bug','Miglioramenti','Da fare') NOT NULL,
  `assigned_user_id` bigint(20) unsigned NOT NULL,
  `status` enum('In Sospeso','Completato') NOT NULL DEFAULT 'In Sospeso',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

### New Permission

- **Permission Name**: `manage works`
- **Assigned to Roles**: Admin, Manager, Employee

## 🎯 Features Implemented

### ✅ Complete CRUD Interface
- **Create**: Add new work items with project selection, work type, and employee assignment
- **Read**: View work details with project and employee information
- **Update**: Edit work items and update status
- **Delete**: Remove work items with confirmation

### ✅ Advanced Filtering System
- **Project-based filtering**: Filter works by specific projects
- **Status filtering**: Filter by "In Sospeso" or "Completato"
- **Type filtering**: Filter by "Bug", "Miglioramenti", or "Da fare"
- **Search functionality**: Search across work names, projects, and employees

### ✅ Status Management
- **Auto-status**: New works automatically set to "In Sospeso"
- **Quick completion**: One-click button to mark works as "Completato"
- **Status tracking**: Visual indicators for work status

### ✅ Form Requirements Met
- **Project dropdown**: Populated from existing projects with client names
- **Work name**: Text input field with validation
- **Work type dropdown**: Exactly "Bug", "Miglioramenti", "Da fare"
- **Employee dropdown**: Populated from registered users with roles
- **Auto-creation date**: Automatically set when saving
- **Auto-status**: Defaults to "In Sospeso"

### ✅ Security & Authorization
- **Permission-based access**: Only users with "manage works" permission can access
- **CSRF protection**: All forms include CSRF tokens
- **Input validation**: Server-side validation for all fields
- **Relationship integrity**: Foreign key constraints ensure data consistency

## 🎨 UI/UX Features

### ✅ Responsive Design
- **Mobile-friendly**: Responsive tables and forms
- **Consistent styling**: Matches existing ProgrammArti design system
- **Color-coded status**: Visual indicators for work types and statuses

### ✅ User Experience
- **Intuitive navigation**: Clear breadcrumbs and navigation
- **Quick actions**: One-click status updates
- **Confirmation dialogs**: Prevent accidental deletions
- **Success messages**: User feedback for all actions

## 📱 Navigation Integration

The "Gestione Lavori" menu item has been added to the sidebar with:
- **Icon**: `fas fa-tasks`
- **Permission check**: Only visible to users with "manage works" permission
- **Active state**: Highlights when on works pages

## 🔧 Technical Implementation

### Files Added/Modified

#### New Files:
- `app/Models/Work.php` - Work model with relationships
- `app/Http/Controllers/WorkController.php` - Complete CRUD controller
- `database/migrations/2025_01_27_000000_create_works_table.php` - Database migration
- `database/seeders/WorkSeeder.php` - Sample data seeder
- `resources/views/works/index.blade.php` - Works listing page
- `resources/views/works/create.blade.php` - Create work form
- `resources/views/works/edit.blade.php` - Edit work form
- `resources/views/works/show.blade.php` - Work details page

#### Modified Files:
- `routes/web.php` - Added work management routes
- `resources/views/layouts/sidebar.blade.php` - Added menu item
- `database/seeders/RoleSeeder.php` - Added "manage works" permission
- `database/seeders/DatabaseSeeder.php` - Added WorkSeeder
- `app/Models/Project.php` - Added works relationship
- `app/Models/User.php` - Added works relationship

## 🧪 Testing

### Manual Testing Checklist

- [ ] Access works management from sidebar menu
- [ ] Create new work item with all required fields
- [ ] Filter works by project, status, and type
- [ ] Search works by name, project, or employee
- [ ] Edit existing work items
- [ ] Mark work as completed using quick action
- [ ] View work details page
- [ ] Delete work items with confirmation
- [ ] Verify permission restrictions work correctly

### Sample Data

The installation includes sample work items:
- Bug fixes (completed and pending)
- Improvements (pending)
- Tasks to do (pending)

## 🔒 Security Considerations

- **Authorization**: All routes protected by "manage works" permission
- **Validation**: Server-side validation for all inputs
- **CSRF Protection**: All forms include CSRF tokens
- **SQL Injection Prevention**: Using Eloquent ORM and prepared statements
- **XSS Prevention**: All output properly escaped in Blade templates

## 📞 Support

For issues or questions:
- **Email**: info@programmarti.com
- **Website**: www.programmarti.com

## 🎉 Completion

After installation, administrators can:
1. Track all work tasks for each project
2. Assign work to specific employees
3. Monitor work progress and completion
4. Filter and search through work items efficiently
5. Maintain project execution oversight

The system now provides complete work management capabilities integrated seamlessly with the existing project and user management features.
