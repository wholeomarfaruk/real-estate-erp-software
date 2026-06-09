# Database Seeding Guide - CRM & Marketing Demo Data

This document describes the demo data that has been seeded for testing the CRM and marketing features.

## Overview

Three new seeders have been created to populate the database with realistic demo data:

1. **LeadSeeder** - Creates 50 leads with various statuses, budgets, and attributes
2. **MarketingSeeder** - Creates communication templates, marketing audiences, and campaigns
3. **DemoTestDataSeeder** - Creates CRM tasks and automation rules

## Seeded Data Summary

### Leads (204 Total)
- **New**: 32 leads
- **Contacted**: 32 leads
- **Qualified**: 28 leads (ready for conversion)
- **Site Visit**: 28 leads
- **Negotiation**: 28 leads
- **Won**: 28 leads (converted)
- **Lost**: 28 leads

**Lead Details Include:**
- Name, phone, email, address
- Budget range (3.5M - 11M BDT)
- Lead source (Facebook, Google Ads, Referral, Walk-in, etc.)
- Occupations and income ranges
- Social profiles (Facebook, WhatsApp)
- Assigned to various team members

### Lead Sources (8 Total)
1. Facebook (#3B82F6)
2. Google Ads (#EA4335)
3. Referral (#10B981)
4. Walk-in (#F59E0B)
5. Website (#8B5CF6)
6. Phone Call (#06B6D4)
7. Email (#EC4899)
8. Event (#14B8A6)

### Communication Templates (6 Total)

1. **New Lead Welcome Email** (Email)
   - Welcome template for new leads
   - Includes project highlights and next steps

2. **Site Visit Invitation** (SMS)
   - SMS invitation to visit the property
   - Short and mobile-friendly

3. **Project Update Newsletter** (Email)
   - Monthly project progress updates
   - Showcases new offers and discounts

4. **Follow-up: Budget Discussion** (SMS)
   - Budget-focused follow-up SMS
   - Includes personalized unit matching

5. **Payment Plan Options** (Email)
   - Detailed payment plan options
   - Shows flexible financing terms

### Marketing Audiences (5 Total)

1. **Qualified Leads - Ready to Convert** (28 members)
   - Dynamic audience of leads with 'qualified' status
   - Target for conversion campaigns

2. **High Budget Customers** (36 members)
   - Dynamic audience with budget >= 60 lakhs
   - Target for premium unit campaigns

3. **New Leads - Initial Outreach** (64 members)
   - Dynamic audience of 'new' and 'contacted' leads
   - Target for welcome campaigns

4. **Site Visit Scheduled** (56 members)
   - Dynamic audience with 'site_visit' or 'negotiation' status
   - Follow-up and engagement tracking

5. **All Active Leads** (204 members)
   - All leads across all statuses
   - Broad audience for general announcements

### Campaigns (4 Total)

1. **Welcome Campaign - New Leads** (Completed)
   - Status: Completed
   - 15 sent, 8 opened
   - Uses: New Lead Welcome Email template

2. **Monthly Project Update - June 2026** (Running)
   - Status: Running
   - 35 sent, 18 opened, 2 failed
   - Uses: Project Update Newsletter template

3. **Payment Plans Campaign - High Budget** (Draft)
   - Status: Draft (ready to launch)
   - Target: High Budget Customers audience
   - Uses: Payment Plan Options template

4. **Final Push - Qualification Campaign** (Queued)
   - Status: Queued (scheduled to run in 3 days)
   - Target: Qualified Leads audience
   - Uses: Site Visit Invitation template

### CRM Tasks (60 Total)

- 20 leads with primary follow-up tasks
- Secondary tasks for proposal sending
- Task types: follow_up, call, meeting, email, document, other
- Task statuses: todo, in_progress, done
- Task priorities: low, medium, high, urgent
- Due dates: 1-14 days in the future

### Automations (3 Total)

1. **Auto: Welcome Email for New Leads**
   - Trigger: lead.created
   - Action: Send email (1 hour delay)
   - Template: New Lead Welcome Email

2. **Auto: Email on Lead Qualified**
   - Trigger: lead.status_changed
   - Action: Send email (immediate)
   - Condition: status = 'qualified'

3. **Auto: SMS on Followup Scheduled**
   - Trigger: followup.scheduled
   - Action: Send SMS (immediate)

### Additional Data

- **Lead Activities**: 400+ activities per lead (calls, emails, meetings, status changes)
- **Lead Follow-ups**: 95+ scheduled follow-ups across leads
- **Project**: Star Unity Residencia (existing project from PropertySeeder)

## How to Use This Data

### For Testing CRM Features:
1. Navigate to the Leads module
2. Filter by status, source, budget range
3. Click on any lead to view activities and follow-ups
4. Test assignment and workflow transitions

### For Testing Marketing Campaigns:
1. Go to Marketing > Campaigns
2. View campaign statistics (sent, opened, failed)
3. Create new campaigns using existing templates
4. Test audience filtering and targeting

### For Testing Automations:
1. Go to Settings > Automations
2. View configured automation rules
3. Test trigger conditions and actions
4. Monitor automation logs

## Re-seeding Data

To refresh the demo data and start fresh:

```bash
php artisan migrate:refresh --seed
```

This will:
1. Drop all tables
2. Re-run all migrations
3. Execute all seeders in order

## Customizing Seeders

All seeders are located in `database/seeders/`:
- `LeadSeeder.php` - Modify lead data, sources, and relationships
- `MarketingSeeder.php` - Modify templates, audiences, campaigns
- `DemoTestDataSeeder.php` - Modify tasks and automations

Edit the arrays in these files to customize the demo data before running `php artisan db:seed`.

## Notes

- Lead numbers are generated automatically using the `LEAD-XXXXXX` format
- Lead scores are calculated based on profile completeness
- All data is linked to the existing "Star Unity Residencia" project
- Created by user ID 1 (system admin)
- Dates are relative to current server time
