# SMS Webhook Configuration Guide

## Overview
The webhook system allows SMS providers to update message status in real-time as SMS are delivered or fail.

## Architecture

### Database Fields (on `messages` table)
- `external_id` — Provider's message ID (used to match webhook callbacks)
- `webhook_data` — JSON payload received from provider webhook
- `timeline` — Array of status events with timestamps:
  ```json
  [
    {
      "event": "sent",
      "timestamp": "2026-06-09T12:30:45Z",
      "data": {"via": "sms", "provider": "twilio"}
    },
    {
      "event": "webhook_received",
      "timestamp": "2026-06-09T12:35:22Z",
      "data": {"provider": "twilio", "new_status": "delivered"}
    }
  ]
  ```
- `delivered_at` — Timestamp when message status changed to 'delivered'

### Webhook Endpoint
**POST** `/api/webhooks/sms?provider={provider_name}`

Parameters:
- `provider` (query param): One of `twilio`, `ssl_wireless`, `vonage`
- Request body: Provider's webhook payload (varies by provider)

Returns:
- `200 OK` — Webhook processed successfully
- `404 Not Found` — Message not found (wrong external_id)
- `500 Error` — Error processing webhook

### Timeline Events
Each status change creates an event in the timeline:
- **sent** — Message sent via provider (fired after successful API call)
- **webhook_received** — Webhook received from provider (status updated)
- **failed** — Message send failed (fired if job throws exception)

## Provider Configuration

### Twilio
1. Go to [Twilio Console](https://www.twilio.com/console)
2. Navigate to **Phone Numbers → Manage Numbers → Your Number**
3. Under **Messaging**, set **Webhook URL** to:
   ```
   https://yourdomain.com/api/webhooks/sms?provider=twilio
   ```
4. Method: **HTTP POST**
5. Webhook events to enable:
   - ✓ Message Sent
   - ✓ Message Delivered
   - ✓ Message Failed

**Twilio Webhook Payload:**
```json
{
  "MessageSid": "SM123456789...",
  "MessageStatus": "delivered",
  "To": "+8801711223344",
  "From": "+1234567890"
}
```

**Status Mapping:**
- `sending/sent` → `sent`
- `delivered` → `delivered`
- `failed/undelivered` → `failed`
- `queued` → `queued`

---

### SSL Wireless
1. Contact SSL Wireless support
2. Provide webhook URL:
   ```
   https://yourdomain.com/api/webhooks/sms?provider=ssl_wireless
   ```
3. They will configure delivery reports to your endpoint

**Expected Webhook Payload:**
```json
{
  "MessageID": "MSG123456",
  "Status": "Successful",
  "MSISDN": "+8801711223344",
  "CampaignID": "123"
}
```

**Status Mapping:**
- `Sent` → `sent`
- `Successful` → `delivered`
- `Failed` → `failed`
- `Pending` → `queued`

---

### Vonage (Nexmo)
1. Go to [Vonage Dashboard](https://dashboard.nexmo.com)
2. Navigate to **Settings → SMS API**
3. Under **Inbound Webhook URL**, set:
   ```
   https://yourdomain.com/api/webhooks/sms?provider=vonage
   ```
4. Save settings

**Vonage Webhook Payload:**
```json
{
  "messageId": "MG12345678",
  "to": "8801711223344",
  "from": "COMPANY",
  "status": "0",
  "timestamp": "2026-06-09T12:35:22Z"
}
```

**Status Mapping:**
- `0` → `delivered`
- `1/2` → `failed`
- `8` → `queued`

---

## Message Status Flow

### Without Webhook
```
queued → (SendMessageJob) → sent → (no further updates)
       → (if error) → failed
```

### With Webhook
```
queued → (SendMessageJob) → sent → (Webhook from provider) → delivered
                                                            ↓ failed
                                                            ↓ opened
```

## Timeline Example

Message lifecycle in `timeline` field:
```json
[
  {
    "event": "sent",
    "timestamp": "2026-06-09T12:30:45Z",
    "data": {
      "via": "sms",
      "provider": "twilio"
    }
  },
  {
    "event": "webhook_received",
    "timestamp": "2026-06-09T12:31:12Z",
    "data": {
      "provider": "twilio",
      "new_status": "delivered"
    }
  }
]
```

## Testing Webhooks Locally

### 1. Using Postman
```
POST http://127.0.0.1:8000/api/webhooks/sms?provider=twilio

Body (JSON):
{
  "MessageSid": "SM123456789",
  "MessageStatus": "delivered",
  "To": "+8801711223344"
}
```

### 2. Using curl
```bash
curl -X POST "http://127.0.0.1:8000/api/webhooks/sms?provider=twilio" \
  -H "Content-Type: application/json" \
  -d '{
    "MessageSid": "SM123456789",
    "MessageStatus": "delivered",
    "To": "+8801711223344"
  }'
```

### 3. Using ngrok for External Testing
If you want to test with real provider webhooks locally:

```bash
# Install ngrok: https://ngrok.com/download
# In one terminal:
ngrok http 8000

# This gives you a URL like: https://abc123.ngrok.io
# Then configure your provider with webhook:
# https://abc123.ngrok.io/api/webhooks/sms?provider=twilio
```

## Webhook Logging

All webhook activity is logged in `storage/logs/laravel.log`:

```
[2026-06-09 12:35:22] local.INFO: SMS Webhook received {"provider":"twilio","payload":{...}}
```

To view logs in real-time:
```bash
tail -f storage/logs/laravel.log | grep "SMS Webhook"
```

## Troubleshooting

### Webhook Not Being Called
1. Check firewall/port forwarding allows inbound traffic to your webhook endpoint
2. Verify webhook URL is publicly accessible
3. Check provider's webhook logs (Twilio Console → Message Logs)
4. Ensure HTTP POST method is configured (not GET)

### Message Not Found (404)
- Provider is sending wrong message ID format
- Message record doesn't exist in database
- Check external_id field matches provider's message ID field

### Status Not Updating
1. Check database for `webhook_data` field (confirms webhook received)
2. Check `timeline` field for webhook_received event
3. Verify status mapping in WebhookController matches provider's status values

### Testing Webhook Processing
```php
// In Tinker:
$msg = Message::find(5);
echo json_encode($msg->timeline);
echo json_encode($msg->webhook_data);
```

## Security Notes

⚠️ **IMPORTANT**: The webhook endpoint has **no authentication** because:
- Providers need to send from public IP
- Webhook is public, not protected data
- Message lookup uses provider's external ID (not guessable)

For production, consider:
1. Verifying webhook signature (most providers support this)
2. IP whitelisting (restrict to provider's IP range)
3. Rate limiting on webhook endpoint

Example IP whitelist:
```php
// In WebhookController
if (!in_array($request->ip(), config('sms.webhook_ips'))) {
    return response('Unauthorized', 401);
}
```

## API Response

All responses use HTTP status codes:
- **200 OK** — Successfully processed
- **404 Not Found** — Message ID not found
- **500 Error** — Server error processing webhook

No response body is returned (providers ignore it). Ensure status code is correct.
