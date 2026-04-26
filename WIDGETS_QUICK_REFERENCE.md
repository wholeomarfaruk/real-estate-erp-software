# Quick Reference: Dashboard Widgets System

## 🎯 What Was Built

A dynamic dashboard that shows/hides widgets based on user roles and permissions. No hardcoded logic in templates.

## 📁 Key Files

### Components
- `app/Livewire/Admin/Dashboard/Dashboard.php` - Main dashboard with filtering
- `app/Livewire/Admin/Dashboard/TotalSalesWidget.php`
- `app/Livewire/Admin/Dashboard/TotalExpenseWidget.php`
- `app/Livewire/Admin/Dashboard/StockSummaryWidget.php`
- `app/Livewire/Admin/Dashboard/ProjectStatusWidget.php`

### Views
- `resources/views/livewire/admin/dashboard/dashboard.blade.php`
- `resources/views/livewire/admin/dashboard/total-sales.blade.php`
- `resources/views/livewire/admin/dashboard/total-expense.blade.php`
- `resources/views/livewire/admin/dashboard/stock-summary.blade.php`
- `resources/views/livewire/admin/dashboard/project-status.blade.php`

### Configuration
- `config/dashboard_widgets.php` - Widget definitions with roles and permissions

## 🔄 How It Works

```
User visits /dashboard
         ↓
Dashboard component renders
         ↓
getWidgetsProperty() filters widgets
    ├─ Check permission using $user->can()
    └─ Check roles using $user->getRoleNames()
         ↓
Blade loops through filtered widgets
         ↓
Only authorized widgets display
```

## 📝 Adding a New Widget

```bash
# 1. Create component in app/Livewire/Admin/Dashboard/
class MyNewWidget extends Component {
    public function render() {
        return view('livewire.admin.dashboard.my-new');
    }
}

# 2. Create view in resources/views/livewire/admin/dashboard/my-new.blade.php

# 3. Add to config/dashboard_widgets.php:
'my_widget' => [
    'label' => 'My Widget',
    'component' => \App\Livewire\Admin\Dashboard\MyNewWidget::class,
    'permission' => 'dashboard.my.view',
    'roles' => ['admin', 'role_name'],
],
```

## 🔐 Widget Config Structure

```php
'widget_key' => [
    'label' => 'Display Name',                              // Required
    'component' => \App\Livewire\Admin\Dashboard\Widget::class,  // Required
    'permission' => 'permission.name',                      // Optional
    'roles' => ['admin', 'accounts'],                       // Optional
]
```

## ✅ Filtering Rules

Widget shows if:
- ✅ User has permission (if permission is set)
- AND ✅ User has at least one required role (if roles are set)
- OR ✅ No permission/role restrictions are set

## 👥 Supported Roles

- admin
- employee
- accounts
- storemanager
- chiefengineer
- chairman
- md

## 📊 Widget Permissions

- `dashboard.sales.view` - Total Sales widget
- `dashboard.expense.view` - Total Expense widget
- `dashboard.stock.view` - Stock Summary widget
- `dashboard.project.view` - Project Status widget

## 🧪 Testing a Widget

```bash
# 1. Create a test user
php artisan tinker
> User::factory()->create(['name' => 'Test User'])

# 2. Assign role to user
> $user = User::find(1)
> $user->assignRole('admin')

# 3. Assign permission to role
> $role = Role::findByName('admin')
> $role->givePermissionTo('dashboard.sales.view')

# 4. Log in and visit /dashboard
```

## 🛠️ Customization

### Change Widget Visibility
Edit `config/dashboard_widgets.php`:
- Add/remove roles in `'roles'` array
- Add/remove permissions in `'permission'` field

### Change Widget Order
Reorder array items in `config/dashboard_widgets.php`

### Change Widget UI
Edit corresponding `.blade.php` file:
- `resources/views/livewire/admin/dashboard/`

### Modify Widget Data
Edit widget component:
- `app/Livewire/Admin/Dashboard/WidgetName.php`

## 🐛 Troubleshooting

**Widget not showing?**
1. Check user has role in users_roles table
2. Check role has permission in role_has_permissions table
3. Clear cache: `php artisan cache:clear`

**Component not found?**
1. Verify namespace is correct
2. Verify file exists
3. Check component name matches config reference

**Permission not working?**
1. Verify permission exists in permissions table
2. Verify role_has_permissions records exist
3. Clear cache: `php artisan cache:clear`

## 📚 Documentation

- `DASHBOARD_WIDGETS.md` - Full documentation
- `C:\Users\SARK\.copilot\session-state\...\implementation-summary.md` - Implementation details

## 🎯 Summary

The system is:
- **Scalable**: Add widgets without changing blade templates
- **Secure**: Uses Laravel's permission system
- **Maintainable**: Clear separation of concerns
- **Flexible**: Supports both role and permission filtering
- **Simple**: Easy to understand and modify

---

**Everything is ready to use! 🚀**
