<?php

namespace Database\Seeders;

use App\Models\AudienceMember;
use App\Models\Campaign;
use App\Models\CommunicationTemplate;
use App\Models\Lead;
use App\Models\MarketingAudience;
use Illuminate\Database\Seeder;

class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        // Create communication templates
        $templates = [
            [
                'name' => 'New Lead Welcome Email',
                'type' => 'email',
                'subject' => 'Welcome to Star Unity Residencia - Explore Your Dream Home',
                'body' => 'Dear {name},

Thank you for your interest in Star Unity Residencia. We are excited to show you our premium residential apartments located in the heart of Bashundhara R/A.

Key highlights of our project:
- Premium location in Bashundhara R/A, Dhaka
- Modern architecture and world-class amenities
- Budget range: {budget_min} - {budget_max} BDT
- Flexible payment options available

Your next steps:
1. Schedule a site visit
2. Review available units
3. Discuss payment plans with our sales team

We look forward to welcoming you soon!

Best regards,
Star Unity Residencia Sales Team',
                'variables' => ['name', 'budget_min', 'budget_max'],
                'is_active' => true,
            ],
            [
                'name' => 'Site Visit Invitation',
                'type' => 'sms',
                'subject' => 'Site Visit Invitation',
                'body' => 'Hi {name}, You are invited to visit Star Unity Residencia. Explore our premium apartments at your convenience. Book your slot now!',
                'variables' => ['name'],
                'is_active' => true,
            ],
            [
                'name' => 'Project Update Newsletter',
                'type' => 'email',
                'subject' => 'Star Unity Residencia - Project Update {month}',
                'body' => 'Dear {name},

We are pleased to share our latest project updates:

Progress Update:
- Construction Progress: 75%
- Expected Handover: December 2026
- New units available with special discount

Current Offers:
- Early bird discount: 5% on selected units
- Flexible payment plans
- Zero down payment options

Contact us to learn more about these exciting offers!

Best regards,
Sales Team',
                'variables' => ['name', 'month'],
                'is_active' => true,
            ],
            [
                'name' => 'Follow-up: Budget Discussion',
                'type' => 'sms',
                'subject' => 'Budget Discussion Follow-up',
                'body' => 'Hi {name}, We have units perfectly matching your budget of {budget}. Click here to schedule your consultation: [link]',
                'variables' => ['name', 'budget'],
                'is_active' => true,
            ],
            [
                'name' => 'Payment Plan Options',
                'type' => 'email',
                'subject' => 'Flexible Payment Plans for {property}',
                'body' => 'Dear {name},

Great news! We have flexible payment options for {property}:

Payment Plans Available:
1. 20% Down + 80% Over 5 Years
2. 30% Down + 70% Over 7 Years
3. Special: 0% Down + 100% Financing (Tenure Required)

Your allocated budget: {budget_min} - {budget_max} BDT

Our finance team is ready to customize a plan that suits your needs.

Schedule a consultation today!

Best regards,
Star Unity Residencia',
                'variables' => ['name', 'property', 'budget_min', 'budget_max'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            CommunicationTemplate::updateOrCreate(
                ['name' => $template['name']],
                [
                    'type' => $template['type'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'variables' => $template['variables'],
                    'is_active' => $template['is_active'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );
        }

        // Create marketing audiences
        $qualifiedLeads = Lead::where('status', 'qualified')->pluck('id');
        $highBudgetLeads = Lead::whereBetween('budget_min', [6000000, 12000000])->pluck('id');
        $newLeads = Lead::where('status', 'new')->pluck('id');
        $siteVisitReadyLeads = Lead::where('status', 'site_visit')->pluck('id');

        $audiences = [
            [
                'name' => 'Qualified Leads - Ready to Convert',
                'type' => 'dynamic',
                'description' => 'Leads with qualified status ready for conversion',
                'filters' => ['lead_status' => ['qualified']],
            ],
            [
                'name' => 'High Budget Customers',
                'type' => 'dynamic',
                'description' => 'Customers with budget above 60 lakhs interested in premium units',
                'filters' => ['budget_min' => 6000000],
            ],
            [
                'name' => 'New Leads - Initial Outreach',
                'type' => 'dynamic',
                'description' => 'All new leads for initial welcome campaign',
                'filters' => ['lead_status' => ['new', 'contacted']],
            ],
            [
                'name' => 'Site Visit Scheduled',
                'type' => 'dynamic',
                'description' => 'Leads scheduled for site visit',
                'filters' => ['lead_status' => ['site_visit', 'negotiation']],
            ],
            [
                'name' => 'All Active Leads',
                'type' => 'dynamic',
                'description' => 'All active leads across all statuses',
                'filters' => [],
            ],
        ];

        $audienceMap = [];
        foreach ($audiences as $audience) {
            $aud = MarketingAudience::updateOrCreate(
                ['name' => $audience['name']],
                [
                    'type' => $audience['type'],
                    'description' => $audience['description'],
                    'filters' => $audience['filters'],
                    'is_active' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            $audienceMap[$audience['name']] = $aud;
            $aud->syncMemberCount();
        }

        // Create sample campaigns
        $templateWelcome = CommunicationTemplate::where('name', 'New Lead Welcome Email')->first();
        $templateUpdate = CommunicationTemplate::where('name', 'Project Update Newsletter')->first();
        $templatePayment = CommunicationTemplate::where('name', 'Payment Plan Options')->first();

        $campaigns = [
            [
                'name' => 'Welcome Campaign - New Leads',
                'description' => 'Welcome email campaign for all new leads',
                'type' => 'email',
                'audience_name' => 'New Leads - Initial Outreach',
                'template_name' => 'New Lead Welcome Email',
                'status' => 'completed',
                'stats' => ['sent' => 15, 'opened' => 8, 'failed' => 0],
                'started_at' => now()->subDays(30),
                'completed_at' => now()->subDays(25),
            ],
            [
                'name' => 'Monthly Project Update - June 2026',
                'description' => 'Monthly update on project progress and new offers',
                'type' => 'email',
                'audience_name' => 'All Active Leads',
                'template_name' => 'Project Update Newsletter',
                'status' => 'running',
                'stats' => ['sent' => 35, 'opened' => 18, 'failed' => 2],
                'started_at' => now()->subDays(5),
                'completed_at' => null,
            ],
            [
                'name' => 'Payment Plans Campaign - High Budget',
                'description' => 'Showcase flexible payment options to high-budget customers',
                'type' => 'email',
                'audience_name' => 'High Budget Customers',
                'template_name' => 'Payment Plan Options',
                'status' => 'draft',
                'stats' => ['sent' => 0, 'opened' => 0, 'failed' => 0],
                'started_at' => null,
                'completed_at' => null,
            ],
            [
                'name' => 'Final Push - Qualification Campaign',
                'description' => 'Conversion push for qualified leads',
                'type' => 'sms',
                'audience_name' => 'Qualified Leads - Ready to Convert',
                'template_name' => 'Site Visit Invitation',
                'status' => 'queued',
                'stats' => ['sent' => 0, 'opened' => 0, 'failed' => 0],
                'started_at' => null,
                'completed_at' => null,
                'scheduled_at' => now()->addDays(3),
            ],
        ];

        foreach ($campaigns as $campaign) {
            $audience = $audienceMap[$campaign['audience_name']] ?? null;
            $template = CommunicationTemplate::where('name', $campaign['template_name'])->first();

            if ($audience && $template) {
                Campaign::updateOrCreate(
                    [
                        'name' => $campaign['name'],
                        'audience_id' => $audience->id,
                    ],
                    [
                        'description' => $campaign['description'],
                        'type' => $campaign['type'],
                        'template_id' => $template->id,
                        'status' => $campaign['status'],
                        'stats' => $campaign['stats'],
                        'started_at' => $campaign['started_at'],
                        'completed_at' => $campaign['completed_at'],
                        'scheduled_at' => $campaign['scheduled_at'] ?? null,
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]
                );
            }
        }
    }
}
