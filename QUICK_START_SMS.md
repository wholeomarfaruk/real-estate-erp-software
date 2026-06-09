# SMS System - Quick Start Guide

## ⚡ 30-Second Setup

1. **View Seeded Gateways**
   - Go to `/admin/settings/sms-gateway`
   - See 3 test gateways: Twilio (active), SSL Wireless, Vonage

2. **Send Test SMS**
   - Go to `/admin/marketing/messages`
   - Click "+ Send Message"
   - Type: SMS
   - Select a lead
   - Send

3. **Check Status**
   - View messages table
   - Status: queued → sent (via background job)
   - With real provider webhook: sent → delivered

---

## 📋 What's Included

### ✅ Production-Ready Components
- 3 SMS provider implementations (Twilio, SSL Wireless, Vonage)
- Admin configuration panel for managing gateways
- Role-based access control (4 permissions)
- Webhook endpoint for delivery tracking
- Timeline events for message history
- Queue-based asynchronous sending
- 3 test gateways pre-configured

### ✅ Fully Tested
- Gateway creation & switching
- SMS sending via job queue
- Webhook payload processing
- Permission assignments

---

## 🎯 Key URLs

| Feature | URL |
|---------|-----|
| SMS Gateway Config | `/admin/settings/sms-gateway` |
| Send Message | `/admin/marketing/messages` |
| Webhook Endpoint | `POST /api/webhooks/sms?provider=twilio` |

---

## 🔐 Default Access

| User | Permissions |
|------|-------------|
| Admin (admin@gmail.com) | Full access to SMS Gateway settings |
| SalesMarketing | View-only SMS Gateway settings |
| SuperAdmin | Full access (all permissions) |

---

## 📊 Message Status Flow

```
New SMS
  ↓
Status: queued
  ↓ (SendMessageJob runs)
Status: sent (external_id from provider)
  ↓ (Provider webhook arrives)
Status: delivered (with delivered_at timestamp)
```

---

## 🔧 Configuration (Optional)

To use real credentials instead of test data, edit `.env`:

```env
# Twilio
TWILIO_ACCOUNT_SID=AC...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM_NUMBER=+1...

# SSL Wireless
SSL_WIRELESS_API_URL=https://...
SSL_WIRELESS_USER=...
SSL_WIRELESS_PASSWORD=...
SSL_WIRELESS_SID=...

# Vonage
VONAGE_API_KEY=...
VONAGE_API_SECRET=...
VONAGE_FROM=...
```

Then restart seeders or manually update credentials in admin panel.

---

## 🚀 Run Queue Worker

To actually send messages:

```bash
# Terminal 1: Start queue worker
php artisan queue:work

# Terminal 2: Your app continues
php artisan serve
```

Without queue worker, messages stay in `queued` status.

---

## 📞 Test Webhook

With ngrok (for real provider testing):

```bash
# Terminal 1: Install & run ngrok
ngrok http 8000

# Terminal 2: Get URL from ngrok output
# https://abc123.ngrok.io

# Configure in Twilio dashboard:
# Webhook URL: https://abc123.ngrok.io/api/webhooks/sms?provider=twilio
# Method: HTTP POST

# Then send a test SMS from Messages page
```

---

## 📁 Key Files

```
app/Services/Sms/
├── SmsService.php             ← Dispatcher
└── Providers/
    ├── TwilioProvider.php
    ├── SslWirelessProvider.php
    └── VonageProvider.php

app/Http/Controllers/Api/WebhookController.php   ← Webhook receiver

app/Livewire/Admin/Settings/SmsGatewayList.php   ← Admin UI

database/seeders/SmsGatewaySeeder.php            ← Test data
database/seeders/PermissionSeeder.php            ← Permissions

routes/api.php                                    ← /api/webhooks/sms
```

---

## ✅ Verification

Check everything is wired up:

```bash
# View seeded gateways
php artisan tinker
>>> App\Models\SmsGateway::all()

# Check active gateway
>>> App\Models\SmsGateway::where('is_active', true)->first()

# Check admin permissions
>>> Spatie\Permission\Models\Role::findByName('admin')->permissions->pluck('name')
```

---

## 🎁 What You Get

✅ Multi-provider SMS (Twilio, SSL Wireless, Vonage)  
✅ Webhook delivery tracking (sent → delivered)  
✅ Admin configuration panel  
✅ Queue-based asynchronous sending  
✅ Timeline event tracking  
✅ Role-based access control  
✅ 3 test gateways pre-configured  
✅ Full error logging  
✅ Production-ready code  

---

## 📚 Full Documentation

- **SMS_IMPLEMENTATION_SUMMARY.md** - Complete system overview
- **SMS_GATEWAY_IMPLEMENTATION.md** - Architecture & providers
- **SMS_WEBHOOK_SETUP.md** - Webhook configuration per provider
- **SMS_SYSTEM_COMPLETE.md** - Full workflows & examples

---

## 🆘 Need Help?

### Message not sending?
1. Check queue worker: `php artisan queue:work`
2. Check active gateway: `/admin/settings/sms-gateway`
3. Check logs: `tail -f storage/logs/laravel.log`

### Webhook not updating?
1. Test webhook: `curl -X POST "http://127.0.0.1:8000/api/webhooks/sms?provider=twilio" ...`
2. Check webhook_data field in database
3. Check timeline events for message

### Permission denied?
1. Check user role: Admin or SuperAdmin for full access
2. Check permission assigned: `settings.sms_gateway.view`

---

**Status**: ✅ Ready to Use  
**Test Data**: ✅ Seeded (3 gateways)  
**Permissions**: ✅ Assigned (Admin & SalesMarketing)  
**Documentation**: ✅ Complete (4 guides)
