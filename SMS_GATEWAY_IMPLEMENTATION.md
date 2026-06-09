# SMS Gateway Multi-Provider Implementation

## Overview
Complete SMS gateway system with support for multiple providers (SSL Wireless, Twilio, Vonage) with admin panel configuration.

## Architecture

### 1. Database Schema
**Table: `sms_gateways`**
- `id` - Primary key
- `name` - Gateway friendly name
- `provider` - Provider type (string, can be extended)
- `credentials` - JSON object storing provider-specific API keys
- `is_active` - Boolean, only one provider can be active at a time
- `created_by`, `updated_by` - User tracking
- `created_at`, `updated_at` - Timestamps

### 2. Service Layer

**`app/Services/Sms/SmsProviderInterface.php`**
- Defines contract for all SMS providers
- Method: `send(string $to, string $message): array`
- Returns: `['success' => bool, 'response' => mixed]` or `['success' => false, 'error' => string]`

**Providers Implemented:**
1. **SSL Wireless** (`app/Services/Sms/Providers/SslWirelessProvider.php`)
   - Credentials: `api_url`, `api_user`, `api_password`, `sid` (sender ID)
   - Uses HTTP POST to SSL Wireless API endpoint

2. **Twilio** (`app/Services/Sms/Providers/TwilioProvider.php`)
   - Credentials: `account_sid`, `auth_token`, `from_number`
   - Uses Twilio REST API with Basic Auth

3. **Vonage** (`app/Services/Sms/Providers/VonageProvider.php`)
   - Credentials: `api_key`, `api_secret`, `from`
   - Uses Vonage SMS API (formerly Nexmo)

**Main Service:**
`app/Services/Sms/SmsService.php`
- Fetches active gateway from database
- Instantiates appropriate provider based on `provider` column
- Handles case where no provider is configured (returns error)
- All providers use Laravel's `Http::` facade (no external packages required)

### 3. Message Queue Integration

**Modified: `app/Jobs/SendMessageJob.php`**
- For `type = 'email'`: Uses Mail facade (existing)
- For `type = 'sms'`: Calls `SmsService::send()`
  - If send fails (returns `['success' => false]`), throws exception
  - Exception caught, message status set to `failed` with error JSON
  - If send succeeds, message status set to `sent`
- Implements `ShouldQueue` interface - jobs run via database queue driver

### 4. Admin Panel

**Livewire Component: `app/Livewire/Admin/Settings/SmsGatewayList.php`**
- Properties:
  - `$drawerOpen` - Drawer visibility state
  - `$editingId` - ID of gateway being edited (null for create)
  - Form fields: `$fName`, `$fProvider`, `$fIsActive`
  - Provider-specific credential fields (shown/hidden via Alpine `x-show`)

- Methods:
  - `openCreate()` - Open drawer for new gateway
  - `openEdit(int $id)` - Load existing gateway data into form
  - `save()` - Validate and persist gateway
    - When `$fIsActive = true`, deactivates all other gateways first
  - `delete(int $id)` - Remove gateway
  - `setActive(int $id)` - One-click activation (deactivates others)
  - `closeDrawer()` - Close drawer and reset form

**Blade View: `resources/views/livewire/admin/settings/sms-gateway-list.blade.php`**
- Table view of all gateways showing:
  - Name
  - Provider badge (color-coded: SSL=blue, Twilio=red, Vonage=green)
  - Status (Active/Inactive)
  - Actions: Set Active (if not active), Edit, Delete

- Right-side drawer with:
  - Gateway name input
  - Provider radio buttons (SSL Wireless / Twilio / Vonage)
  - Conditional credential fields using `x-show="$wire.fProvider === 'provider-name'"`
  - Active checkbox with label
  - Save/Cancel buttons

### 5. Routing & Navigation

**Route:** `routes/admin.php`
```php
Route::get('/settings/sms-gateway', App\Livewire\Admin\Settings\SmsGatewayList::class)
    ->name('settings.sms-gateway');
```

**Sidebar Navigation:** `resources/views/layouts/admin/partials/sidebar.blade.php`
- Added SMS Gateway link under Settings section
- Placed after Account Settings link
- Active state styling when on SMS Gateway page

## Usage Flow

### 1. Admin Setup
1. Navigate to **Admin → Settings → SMS Gateway**
2. Click **+ New Provider**
3. Select provider type (SSL Wireless, Twilio, or Vonage)
4. Fill in credentials for selected provider
5. Check "Set as active provider" to make it the default
6. Click Save

### 2. Sending SMS via Messages
1. Go to **Admin → Marketing → Messages**
2. Click **+ Send Message**
3. Select Message Type: **SMS**
4. Choose recipient (Lead/Customer)
5. Enter message body (can use template variables: {name}, {phone}, {email})
6. Click Send → Message created with status `queued`
7. Background job processes message:
   - Fetches active SMS gateway
   - Calls appropriate provider's send method
   - Updates message status to `sent` or `failed`

### 3. Switching Providers
1. Go to SMS Gateway settings
2. Add new provider
3. For that provider row, click **Set Active**
4. Previous provider automatically becomes inactive
5. All future SMS messages use new provider

## Provider Extensibility

To add a new SMS provider:

1. **Create Provider Class**
   ```php
   namespace App\Services\Sms\Providers;
   
   use App\Services\Sms\SmsProviderInterface;
   use Illuminate\Support\Facades\Http;
   
   class YourProviderName implements SmsProviderInterface {
       public function __construct(private array $credentials) {}
       
       public function send(string $to, string $message): array {
           // Implementation using Http:: facade
       }
   }
   ```

2. **Update SmsService.php**
   - Add case to match statement for new provider name

3. **Update Livewire Component**
   - Add radio button option in `$fProvider` validation
   - Add credential fields to form (both Livewire property and Blade template)

4. **No database migration needed** - provider is stored as string, not enum

## Error Handling

- **No active gateway configured**: Message marked as `failed` with error "No active SMS gateway configured"
- **API authentication fails** (401/403): Captured and stored in `provider_response` JSON
- **API connection timeout**: Caught as exception, message marked failed
- **Invalid recipient number**: Provider returns error, stored in `provider_response`

All errors are logged in `provider_response` column as JSON for debugging.

## Testing

### Quick Test Commands
```bash
# Test gateway creation and switching
php test-sms-flow.php

# Test SMS message sending with job
php test-sms-send.php
```

### Expected Results
1. ✓ Can create gateways with different providers
2. ✓ Can activate/deactivate gateways
3. ✓ Only one gateway active at a time
4. ✓ SMS jobs attempt to send via active provider
5. ✓ Failed attempts logged in `provider_response`
6. ✓ Admin panel accessible at `/admin/settings/sms-gateway`

## Security Notes

- Credentials stored as JSON in database (not encrypted in current implementation)
- For production, consider encrypting credentials column
- API keys/secrets should never be logged or exposed in error messages
- Queue jobs run as background processes - ensure adequate server resources
- All SMS operations audit-logged via `created_by`, `updated_by` fields

## Database

Migration file: `database/migrations/2026_06_09_082144_create_sms_gateways_table.php`

Run migrations:
```bash
php artisan migrate
```

Rollback if needed:
```bash
php artisan migrate:rollback
```
