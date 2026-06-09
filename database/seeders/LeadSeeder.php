<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowup;
use App\Models\LeadSource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create lead sources
        $sources = [
            'Facebook' => '#3B82F6',
            'Google Ads' => '#EA4335',
            'Referral' => '#10B981',
            'Walk-in' => '#F59E0B',
            'Website' => '#8B5CF6',
            'Phone Call' => '#06B6D4',
            'Email' => '#EC4899',
            'Event' => '#14B8A6',
        ];

        foreach ($sources as $name => $color) {
            LeadSource::updateOrCreate(
                ['name' => $name],
                ['color' => $color, 'is_active' => true, 'created_by' => 1]
            );
        }

        // Get project and users
        $project = Project::first() ?? Project::create([
            'name' => 'Star Unity Residencia',
            'code' => 'SUR-2024',
            'project_type' => ['residential'],
            'location' => 'Bashundhara R/A, Dhaka',
            'status' => 'ongoing',
            'created_by' => 1,
        ]);

        $users = User::pluck('id')->toArray();
        $defaultUser = $users[0] ?? 1;

        $leadSources = LeadSource::pluck('id')->toArray();
        $sourceCount = count($leadSources);

        $statuses = ['new', 'contacted', 'qualified', 'site_visit', 'negotiation', 'won', 'lost'];
        $leadData = [
            ['name' => 'Md. Rafiqul Islam', 'phone' => '01711-234567', 'email' => 'rafiqul.islam@gmail.com', 'budget_min' => 4000000, 'budget_max' => 7000000],
            ['name' => 'Fatema Akter', 'phone' => '01912-345678', 'email' => 'fatema.akter@yahoo.com', 'budget_min' => 5000000, 'budget_max' => 8000000],
            ['name' => 'Md. Jahangir Alam', 'phone' => '01615-456789', 'email' => 'jahangir.alam@hotmail.com', 'budget_min' => 6000000, 'budget_max' => 10000000],
            ['name' => 'Nasrin Sultana', 'phone' => '01511-678901', 'email' => 'nasrin.sultana@outlook.com', 'budget_min' => 4500000, 'budget_max' => 7500000],
            ['name' => 'Md. Khorshed Alam', 'phone' => '01911-789012', 'email' => 'khorshed.alam@gmail.com', 'budget_min' => 3500000, 'budget_max' => 6500000],
            ['name' => 'Sharmin Akter Riya', 'phone' => '01712-890123', 'email' => 'sharmin.riya@gmail.com', 'budget_min' => 5500000, 'budget_max' => 9000000],
            ['name' => 'Md. Mizanur Rahman', 'phone' => '01612-901234', 'email' => 'mizan.rahman@gmail.com', 'budget_min' => 4000000, 'budget_max' => 7000000],
            ['name' => 'Aisha Khan', 'phone' => '01813-012345', 'email' => 'aisha.khan@gmail.com', 'budget_min' => 5500000, 'budget_max' => 8500000],
            ['name' => 'Md. Samir Hossain', 'phone' => '01614-123456', 'email' => 'samir.hossain@hotmail.com', 'budget_min' => 6500000, 'budget_max' => 11000000],
            ['name' => 'Runa Chowdhury', 'phone' => '01915-234567', 'email' => 'runa.chowdhury@gmail.com', 'budget_min' => 4000000, 'budget_max' => 6500000],
            ['name' => 'Md. Karim Uddin', 'phone' => '01711-345678', 'email' => 'karim.uddin@gmail.com', 'budget_min' => 5000000, 'budget_max' => 8500000],
            ['name' => 'Nadia Islam', 'phone' => '01912-456789', 'email' => 'nadia.islam@yahoo.com', 'budget_min' => 4500000, 'budget_max' => 7500000],
            ['name' => 'Md. Habib Khan', 'phone' => '01615-567890', 'email' => 'habib.khan@gmail.com', 'budget_min' => 6000000, 'budget_max' => 9500000],
            ['name' => 'Priya Sen', 'phone' => '01813-678901', 'email' => 'priya.sen@gmail.com', 'budget_min' => 5500000, 'budget_max' => 8000000],
            ['name' => 'Md. Ariful Islam', 'phone' => '01711-789012', 'email' => 'ariful.islam@hotmail.com', 'budget_min' => 4000000, 'budget_max' => 6500000],
            ['name' => 'Salma Begum', 'phone' => '01914-890123', 'email' => 'salma.begum@gmail.com', 'budget_min' => 5000000, 'budget_max' => 7500000],
            ['name' => 'Md. Farooq Ahmed', 'phone' => '01612-901234', 'email' => 'farooq.ahmed@gmail.com', 'budget_min' => 6500000, 'budget_max' => 10000000],
            ['name' => 'Rina Das', 'phone' => '01815-012345', 'email' => 'rina.das@yahoo.com', 'budget_min' => 4500000, 'budget_max' => 7000000],
            ['name' => 'Md. Nasiruddin', 'phone' => '01713-123456', 'email' => 'nasir.uddin@gmail.com', 'budget_min' => 5500000, 'budget_max' => 8500000],
            ['name' => 'Kavya Rao', 'phone' => '01916-234567', 'email' => 'kavya.rao@gmail.com', 'budget_min' => 4000000, 'budget_max' => 6500000],
            ['name' => 'Md. Jalal Uddin', 'phone' => '01614-345678', 'email' => 'jalal.uddin@hotmail.com', 'budget_min' => 5500000, 'budget_max' => 9000000],
            ['name' => 'Meena Roy', 'phone' => '01812-456789', 'email' => 'meena.roy@gmail.com', 'budget_min' => 4500000, 'budget_max' => 7500000],
            ['name' => 'Md. Babar Ali', 'phone' => '01716-567890', 'email' => 'babar.ali@gmail.com', 'budget_min' => 6000000, 'budget_max' => 9500000],
            ['name' => 'Taniya Sarkar', 'phone' => '01914-678901', 'email' => 'taniya.sarkar@yahoo.com', 'budget_min' => 5000000, 'budget_max' => 8000000],
            ['name' => 'Md. Hanif Khan', 'phone' => '01613-789012', 'email' => 'hanif.khan@gmail.com', 'budget_min' => 4500000, 'budget_max' => 7000000],
            ['name' => 'Pooja Sharma', 'phone' => '01813-890123', 'email' => 'pooja.sharma@gmail.com', 'budget_min' => 5500000, 'budget_max' => 8500000],
            ['name' => 'Md. Zaman Khan', 'phone' => '01712-901234', 'email' => 'zaman.khan@hotmail.com', 'budget_min' => 6000000, 'budget_max' => 10000000],
            ['name' => 'Sonia Gupta', 'phone' => '01915-012345', 'email' => 'sonia.gupta@gmail.com', 'budget_min' => 4000000, 'budget_max' => 6500000],
            ['name' => 'Md. Imran Hossain', 'phone' => '01611-123456', 'email' => 'imran.hossain@gmail.com', 'budget_min' => 5500000, 'budget_max' => 8500000],
            ['name' => 'Divya Singh', 'phone' => '01814-234567', 'email' => 'divya.singh@yahoo.com', 'budget_min' => 4500000, 'budget_max' => 7500000],
            ['name' => 'Md. Rafiq Ahmed', 'phone' => '01714-345678', 'email' => 'rafiq.ahmed@gmail.com', 'budget_min' => 5000000, 'budget_max' => 8000000],
            ['name' => 'Anjali Nair', 'phone' => '01916-456789', 'email' => 'anjali.nair@gmail.com', 'budget_min' => 4500000, 'budget_max' => 7000000],
            ['name' => 'Md. Wasim Khan', 'phone' => '01612-567890', 'email' => 'wasim.khan@hotmail.com', 'budget_min' => 6000000, 'budget_max' => 9500000],
            ['name' => 'Ritika Patel', 'phone' => '01815-678901', 'email' => 'ritika.patel@gmail.com', 'budget_min' => 5500000, 'budget_max' => 8500000],
            ['name' => 'Md. Nasib Khan', 'phone' => '01713-789012', 'email' => 'nasib.khan@gmail.com', 'budget_min' => 4000000, 'budget_max' => 6500000],
            ['name' => 'Smita Das', 'phone' => '01914-890123', 'email' => 'smita.das@yahoo.com', 'budget_min' => 5000000, 'budget_max' => 8000000],
            ['name' => 'Md. Fardin Islam', 'phone' => '01614-901234', 'email' => 'fardin.islam@gmail.com', 'budget_min' => 4500000, 'budget_max' => 7500000],
            ['name' => 'Nisha Kumari', 'phone' => '01815-012345', 'email' => 'nisha.kumari@gmail.com', 'budget_min' => 5500000, 'budget_max' => 9000000],
            ['name' => 'Md. Saleh Khan', 'phone' => '01711-123456', 'email' => 'saleh.khan@hotmail.com', 'budget_min' => 6000000, 'budget_max' => 10000000],
            ['name' => 'Priya Verma', 'phone' => '01916-234567', 'email' => 'priya.verma@gmail.com', 'budget_min' => 4000000, 'budget_max' => 6500000],
            ['name' => 'Md. Rashid Ahmed', 'phone' => '01613-345678', 'email' => 'rashid.ahmed@gmail.com', 'budget_min' => 5000000, 'budget_max' => 8500000],
            ['name' => 'Ankita Singh', 'phone' => '01814-456789', 'email' => 'ankita.singh@yahoo.com', 'budget_min' => 4500000, 'budget_max' => 7500000],
            ['name' => 'Md. Siddique Khan', 'phone' => '01712-567890', 'email' => 'siddique.khan@gmail.com', 'budget_min' => 5500000, 'budget_max' => 8500000],
            ['name' => 'Deepa Nair', 'phone' => '01915-678901', 'email' => 'deepa.nair@gmail.com', 'budget_min' => 4500000, 'budget_max' => 7000000],
            ['name' => 'Md. Kamal Uddin', 'phone' => '01611-789012', 'email' => 'kamal.uddin@hotmail.com', 'budget_min' => 6000000, 'budget_max' => 9500000],
            ['name' => 'Lavanya Sharma', 'phone' => '01815-890123', 'email' => 'lavanya.sharma@gmail.com', 'budget_min' => 5000000, 'budget_max' => 8000000],
            ['name' => 'Md. Hasan Khan', 'phone' => '01714-901234', 'email' => 'hasan.khan@gmail.com', 'budget_min' => 4500000, 'budget_max' => 7500000],
            ['name' => 'Varsha Patel', 'phone' => '01916-012345', 'email' => 'varsha.patel@yahoo.com', 'budget_min' => 5500000, 'budget_max' => 9000000],
            ['name' => 'Md. Sumon Khan', 'phone' => '01612-123456', 'email' => 'sumon.khan@gmail.com', 'budget_min' => 4000000, 'budget_max' => 6500000],
            ['name' => 'Seema Gupta', 'phone' => '01813-234567', 'email' => 'seema.gupta@gmail.com', 'budget_min' => 5500000, 'budget_max' => 8500000],
            ['name' => 'Md. Iqbal Khan', 'phone' => '01713-345678', 'email' => 'iqbal.khan@hotmail.com', 'budget_min' => 4500000, 'budget_max' => 7500000],
        ];

        $activityTypes = ['call', 'email', 'whatsapp', 'sms', 'site_visit', 'meeting', 'status_change'];

        foreach ($leadData as $index => $data) {
            $lead = Lead::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => 'Dhaka, Bangladesh',
                'lead_source_id' => $leadSources[$index % $sourceCount],
                'project_id' => $project->id,
                'assigned_to' => $users[$index % count($users)] ?? $defaultUser,
                'budget_min' => $data['budget_min'],
                'budget_max' => $data['budget_max'],
                'status' => $statuses[$index % count($statuses)],
                'score' => rand(20, 100),
                'social_profiles' => [
                    'facebook' => rand(0, 1) ? 'facebook.com/user' . $index : null,
                    'whatsapp' => rand(0, 1) ? $data['phone'] : null,
                ],
                'extra_data' => [
                    'occupation' => ['Engineer', 'Doctor', 'Businessman', 'Student', 'Housewife'][$index % 5],
                    'income_range' => ['5-10L', '10-20L', '20-50L', '50L+'][$index % 4],
                    'family_size' => rand(1, 5),
                ],
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Add 1-3 activities per lead
            $activityCount = rand(1, 3);
            for ($i = 0; $i < $activityCount; $i++) {
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => $activityTypes[array_rand($activityTypes)],
                    'description' => $this->getActivityDescription($lead),
                    'created_by' => 1,
                ]);
            }

            // Add 0-2 follow-ups per lead
            if (rand(0, 1)) {
                LeadFollowup::create([
                    'lead_id' => $lead->id,
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'type' => ['call', 'email', 'meeting'][$index % 3],
                    'notes' => 'Follow up to discuss budget and requirements',
                    'created_by' => 1,
                ]);
            }
        }
    }

    private function getActivityDescription($lead): string
    {
        $descriptions = [
            'Initial contact made',
            'Discussed project features and pricing',
            'Sent detailed information about available units',
            'Scheduled site visit for next week',
            'Customer interested in residential units',
            'Budget confirmed within range',
            'Waiting for customer decision',
            'Follow-up call made',
            'Email inquiry received',
            'Customer visiting showroom',
        ];

        return $descriptions[array_rand($descriptions)];
    }
}
