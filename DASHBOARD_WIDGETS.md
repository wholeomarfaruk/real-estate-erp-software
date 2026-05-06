# Role-Based Dashboard Widgets System

## Overview

This is a dynamic dashboard widget system that controls widget visibility based on user roles and permissions. The system is fully configurable and scalable without hardcoding role/permission logic in blade templates.

## Architecture

### 1. Configuration (`config/dashboard_widgets.php`)

The dashboard widgets are defined in a configuration file with the following structure:

```php
'widget_key' => [
    'label' => 'Widget Display Name',
    'component' => \App\Livewire\Admin\Dashboard\WidgetComponent::class,
    'permission' => 'dashboard.section.view',  // Optional
    'roles' => ['admin', 'role_name'],          // Optional
]
```

**Parameters:**
- `key`: Unique identifier for the widget
- `label`: Display name for the widget
- `component`: Full namespace to the Livewire component class
- `permission`: (Optional) Permission required to view the widget. Uses Laravel's permission system.
- `roles`: (Optional) Array of role names allowed to view the widget

### 2. Widget Components

Each widget is a separate Livewire component that:
- Loads its own data
- Has its own view
- Is self-contained

**Available Widgets:**
- `TotalSalesWidget` - Shows total sales amount (permission: `dashboard.sales.view`)
- `TotalExpenseWidget` - Shows total expense amount (permission: `dashboard.expense.view`)
- `StockSummaryWidget` - Shows stock statistics (permission: `dashboard.stock.view`)
- `ProjectStatusWidget` - Shows project statistics (permission: `dashboard.project.view`)

### 3. Dashboard Component (`App\Livewire\Admin\Dashboard\Dashboard`)

The main Dashboard component contains the `getWidgetsProperty()` method which:

1. Retrieves the authenticated user
2. Gets all configured widgets from `config('dashboard_widgets')`
3. Filters widgets based on:
   - **Permission Check**: Uses `$user->can()` for permission validation
   - **Role Check**: Checks if user has ANY of the allowed roles
4. Returns only widgets the user is authorized to view

**Filtering Logic:**
```
Widget is shown if:
- No permission/role restrictions are set, OR
- User has the required permission AND at least one required role
```

### 4. Dashboard Blade View

The blade template loops through filtered widgets and renders them:

```blade
@foreach($this->widgets as $widget)
    @livewire($widget['component'], key($widget['component']))
@endforeach
```

## File Structure

```
app/
├── Livewire/Admin/Dashboard/
│   ├── Dashboard.php                    # Main dashboard component
│   ├── TotalSalesWidget.php            # Total sales widget
│   ├── TotalExpenseWidget.php          # Total expense widget
│   ├── StockSummaryWidget.php          # Stock summary widget
│   └── ProjectStatusWidget.php         # Project status widget
│
resources/views/livewire/admin/dashboard/
├── dashboard.blade.php                 # Main dashboard view
├── total-sales.blade.php              # Total sales widget view
├── total-expense.blade.php            # Total expense widget view
├── stock-summary.blade.php            # Stock summary widget view
└── project-status.blade.php           # Project status widget view

config/
└── dashboard_widgets.php               # Widget configuration
```

## Supported Roles

The system supports the following roles:
- `admin` - Full access to all widgets
- `employee` - Limited access
- `accounts` - Access to financial widgets
- `storemanager` - Access to stock widgets
- `chiefengineer` - Access to project widgets
- `chairman` - Access to executive dashboard
- `md` - Managing director level access

## How It Works

### Step 1: Configuration
Define widgets in `config/dashboard_widgets.php` with roles and permissions.

### Step 2: Permission Check
The `Dashboard` component checks if the user has the required permission using Laravel's permission system.

### Step 3: Role Check
The component verifies if the user has at least one of the allowed roles.

### Step 4: Rendering
Only authorized widgets are rendered in the dashboard blade view.

## Adding New Widgets

To add a new widget:

1. **Create a Livewire Component:**
   ```bash
   php artisan make:livewire Admin/Dashboard/YourWidgetWidget
   ```

2. **Update the component** to fetch and display your data:
   ```php
   public function mount() { /* load data */ }
   public function render() { return view('livewire.admin.dashboard.your-widget'); }
   ```

3. **Create the blade view** for the widget in `resources/views/livewire/admin/dashboard/`

4. **Add to config** in `config/dashboard_widgets.php`:
   ```php
   'your_widget' => [
       'label' => 'Your Widget',
       'component' => \App\Livewire\Admin\Dashboard\YourWidgetWidget::class,
       'permission' => 'dashboard.section.view',
       'roles' => ['admin', 'role_name'],
   ]
   ```

## Key Features

✅ **No Hardcoded Logic** - All role/permission logic is in config and component
✅ **Scalable** - Easy to add new widgets without modifying blade templates
✅ **Secure** - Uses Laravel's permission system (Spatie)
✅ **Flexible** - Supports both role-based and permission-based filtering
✅ **Maintainable** - Clear separation of concerns
✅ **Reusable** - Widgets are self-contained components

## Widget Filtering Algorithm

```
For each widget in config:
    1. Check if user has permission (if permission is set)
       → If permission check fails, hide widget
    2. Check if user has required role (if roles are set)
       → If user lacks all required roles, hide widget
    3. Show widget if all checks pass
```

## Roles Configuration

Each widget specifies allowed roles as an array. Users are displayed the widget only if they have at least one of the specified roles:

```php
'roles' => ['admin', 'accounts', 'md', 'chairman']  // User needs at least one of these
```

## Example: Filter by Admin Only

```php
'admin_only' => [
    'label' => 'Admin Only Widget',
    'component' => \App\Livewire\Admin\Dashboard\AdminOnlyWidget::class,
    'permission' => 'admin.view',
    'roles' => ['admin'],  // Only admins see this
]
```

## Example: Multiple Roles Allowed

```php
'finance_widget' => [
    'label' => 'Finance Dashboard',
    'component' => \App\Livewire\Admin\Dashboard\FinanceWidget::class,
    'permission' => 'finance.view',
    'roles' => ['admin', 'accounts', 'md', 'chairman'],  // Any of these roles
]
```

## Permissions Required

The following permissions should be created in your system:
- `dashboard.sales.view`
- `dashboard.expense.view`
- `dashboard.stock.view`
- `dashboard.project.view`

These are assigned to users through Laravel's permission system (typically using role_has_permissions table).

## Testing

To test the widget filtering:

1. Create test users with different roles
2. Assign different permissions to roles
3. Log in as each user and verify only authorized widgets appear
4. Check both role-based and permission-based filtering

## Troubleshooting

**Widget not showing?**
- Check user has the required role in `users_roles` table
- Check user/role has the required permission in `role_has_permissions` table
- Verify widget is defined in `config/dashboard_widgets.php`

**Permission errors?**
- Ensure Spatie Permission package is properly configured
- Run `php artisan cache:clear` to refresh permissions
- Check database migrations are complete

**Component not found?**
- Verify component namespace matches config reference
- Check component file exists and namespace is correct
