<?php

namespace Database\Seeders;

use App\Models\Automation;
use App\Models\CommunicationTemplate;
use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->toArray();
        $defaultUser = $users[0] ?? 1;

        // Create CRM Tasks for leads
        $leads = Lead::take(20)->get();
        $taskTypes = ['follow_up', 'call', 'meeting', 'email', 'document', 'other'];
        $taskPriorities = ['low', 'medium', 'high', 'urgent'];
        $taskStatuses = ['todo', 'in_progress', 'done'];

        foreach ($leads as $index => $lead) {
            CrmTask::create([
                'title' => 'Follow up with ' . $lead->name,
                'description' => 'Discuss their budget and project preferences',
                'type' => $taskTypes[$index % \count($taskTypes)],
                'status' => $taskStatuses[$index % \count($taskStatuses)],
                'priority' => $taskPriorities[$index % \count($taskPriorities)],
                'related_type' => 'lead',
                'related_id' => $lead->id,
                'assigned_to' => $users[$index % \count($users)] ?? $defaultUser,
                'due_at' => now()->addDays(rand(1, 14)),
                'created_by' => 1,
            ]);

            // Add second task for some leads
            if ($index % 2 === 0) {
                CrmTask::create([
                    'title' => 'Send proposal to ' . $lead->name,
                    'description' => 'Send unit options matching their budget',
                    'type' => 'email',
                    'status' => 'todo',
                    'priority' => 'high',
                    'related_type' => 'lead',
                    'related_id' => $lead->id,
                    'assigned_to' => $users[$index % \count($users)] ?? $defaultUser,
                    'due_at' => now()->addDays(rand(3, 10)),
                    'created_by' => 1,
                ]);
            }
        }

        // Create sample automations
        $template = CommunicationTemplate::where('name', 'New Lead Welcome Email')->first();

        if ($template) {
            $automations = [
                [
                    'name' => 'Auto: Welcome Email for New Leads',
                    'description' => 'Automatically send welcome email 1 hour after lead creation',
                    'trigger_event' => 'lead.created',
                    'action_type' => 'send_email',
                    'template_id' => $template->id,
                    'delay_minutes' => 60,
                    'status' => 'active',
                ],
                [
                    'name' => 'Auto: Email on Lead Qualified',
                    'description' => 'Send special offer email when lead becomes qualified',
                    'trigger_event' => 'lead.status_changed',
                    'action_type' => 'send_email',
                    'template_id' => $template->id,
                    'delay_minutes' => 0,
                    'conditions' => ['status' => 'qualified'],
                    'status' => 'active',
                ],
                [
                    'name' => 'Auto: SMS on Followup Scheduled',
                    'description' => 'Send SMS confirmation when followup is scheduled',
                    'trigger_event' => 'followup.scheduled',
                    'action_type' => 'send_sms',
                    'delay_minutes' => 0,
                    'status' => 'active',
                ],
            ];

            foreach ($automations as $automation) {
                Automation::updateOrCreate(
                    ['name' => $automation['name']],
                    [
                        'description' => $automation['description'],
                        'trigger_event' => $automation['trigger_event'],
                        'action_type' => $automation['action_type'],
                        'template_id' => $automation['template_id'] ?? null,
                        'delay_minutes' => $automation['delay_minutes'] ?? 0,
                        'conditions' => $automation['conditions'] ?? null,
                        'status' => $automation['status'],
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]
                );
            }
        }
    }
}
