<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // ── Departments ──────────────────────────────────────────────────────
        $departments = [
            ['id' => 1, 'name' => 'Administration',  'code' => 'ADMIN',  'status' => true],
            ['id' => 2, 'name' => 'Accounts',         'code' => 'ACCT',   'status' => true],
            ['id' => 3, 'name' => 'Procurement',      'code' => 'PROC',   'status' => true],
            ['id' => 4, 'name' => 'Warehouse',        'code' => 'WH',     'status' => true],
            ['id' => 5, 'name' => 'Engineering',      'code' => 'ENG',    'status' => true],
            ['id' => 6, 'name' => 'Human Resources',  'code' => 'HR',     'status' => true],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->updateOrInsert(['id' => $dept['id']], $dept);
        }

        // ── Designations ─────────────────────────────────────────────────────
        $designations = [
            ['id' =>  1, 'department_id' => 1, 'name' => 'General Manager',         'code' => 'GM',    'status' => true],
            ['id' =>  2, 'department_id' => 1, 'name' => 'Office Manager',           'code' => 'OM',    'status' => true],
            ['id' =>  3, 'department_id' => 2, 'name' => 'Chief Accountant',         'code' => 'CA',    'status' => true],
            ['id' =>  4, 'department_id' => 2, 'name' => 'Accountant',               'code' => 'ACCT',  'status' => true],
            ['id' =>  5, 'department_id' => 2, 'name' => 'Accounts Assistant',       'code' => 'AA',    'status' => true],
            ['id' =>  6, 'department_id' => 3, 'name' => 'Procurement Manager',      'code' => 'PM',    'status' => true],
            ['id' =>  7, 'department_id' => 3, 'name' => 'Purchase Officer',         'code' => 'PO',    'status' => true],
            ['id' =>  8, 'department_id' => 4, 'name' => 'Warehouse Manager',        'code' => 'WM',    'status' => true],
            ['id' =>  9, 'department_id' => 4, 'name' => 'Store Keeper',             'code' => 'SK',    'status' => true],
            ['id' => 10, 'department_id' => 5, 'name' => 'Senior Engineer',          'code' => 'SE',    'status' => true],
            ['id' => 11, 'department_id' => 5, 'name' => 'Engineer',                 'code' => 'ENG',   'status' => true],
            ['id' => 12, 'department_id' => 6, 'name' => 'HR Manager',               'code' => 'HRM',   'status' => true],
            ['id' => 13, 'department_id' => null, 'name' => 'Director',              'code' => 'DIR',   'status' => true],
        ];

        foreach ($designations as $desig) {
            DB::table('designations')->updateOrInsert(['id' => $desig['id']], $desig);
        }

        // ── Employees ────────────────────────────────────────────────────────
        $employees = [
            [
                'id'              => 1,
                'employee_id'     => 'EMP-001',
                'name'            => 'Mohammad Rafiqul Islam',
                'department_id'   => 1,
                'designation_id'  => 1,
                'phone'           => '01711-000001',
                'email'           => 'rafiqul@company.com',
                'gender'          => 'male',
                'joining_date'    => '2020-01-01',
                'employment_type' => 'permanent',
                'basic_salary'    => 80000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 2,
                'employee_id'     => 'EMP-002',
                'name'            => 'Fatema Begum',
                'department_id'   => 2,
                'designation_id'  => 3,
                'phone'           => '01711-000002',
                'email'           => 'fatema@company.com',
                'gender'          => 'female',
                'joining_date'    => '2020-03-15',
                'employment_type' => 'permanent',
                'basic_salary'    => 65000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 3,
                'employee_id'     => 'EMP-003',
                'name'            => 'Md. Karim Uddin',
                'department_id'   => 3,
                'designation_id'  => 6,
                'phone'           => '01711-000003',
                'email'           => 'karim@company.com',
                'gender'          => 'male',
                'joining_date'    => '2021-01-10',
                'employment_type' => 'permanent',
                'basic_salary'    => 55000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 4,
                'employee_id'     => 'EMP-004',
                'name'            => 'Shapna Akter',
                'department_id'   => 2,
                'designation_id'  => 4,
                'phone'           => '01711-000004',
                'email'           => 'shapna@company.com',
                'gender'          => 'female',
                'joining_date'    => '2021-06-01',
                'employment_type' => 'permanent',
                'basic_salary'    => 40000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 5,
                'employee_id'     => 'EMP-005',
                'name'            => 'Md. Jahirul Haque',
                'department_id'   => 3,
                'designation_id'  => 7,
                'phone'           => '01711-000005',
                'email'           => 'jahirul@company.com',
                'gender'          => 'male',
                'joining_date'    => '2021-09-01',
                'employment_type' => 'permanent',
                'basic_salary'    => 38000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 6,
                'employee_id'     => 'EMP-006',
                'name'            => 'Nazma Khatun',
                'department_id'   => 1,
                'designation_id'  => 2,
                'phone'           => '01711-000006',
                'email'           => 'nazma@company.com',
                'gender'          => 'female',
                'joining_date'    => '2022-01-01',
                'employment_type' => 'permanent',
                'basic_salary'    => 35000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 7,
                'employee_id'     => 'EMP-007',
                'name'            => 'Md. Rubel Hossain',
                'department_id'   => 4,
                'designation_id'  => 8,
                'phone'           => '01711-000007',
                'email'           => 'rubel@company.com',
                'gender'          => 'male',
                'joining_date'    => '2022-03-01',
                'employment_type' => 'permanent',
                'basic_salary'    => 32000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 8,
                'employee_id'     => 'EMP-008',
                'name'            => 'Sumaiya Islam',
                'department_id'   => 2,
                'designation_id'  => 5,
                'phone'           => '01711-000008',
                'email'           => 'sumaiya@company.com',
                'gender'          => 'female',
                'joining_date'    => '2022-07-01',
                'employment_type' => 'permanent',
                'basic_salary'    => 28000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 9,
                'employee_id'     => 'EMP-009',
                'name'            => 'Md. Shariful Islam',
                'department_id'   => 5,
                'designation_id'  => 10,
                'phone'           => '01711-000009',
                'email'           => 'shariful@company.com',
                'gender'          => 'male',
                'joining_date'    => '2022-09-15',
                'employment_type' => 'permanent',
                'basic_salary'    => 50000.00,
                'status'          => 'active',
            ],
            [
                'id'              => 10,
                'employee_id'     => 'EMP-010',
                'name'            => 'Tasnim Akter',
                'department_id'   => 4,
                'designation_id'  => 9,
                'phone'           => '01711-000010',
                'email'           => 'tasnim@company.com',
                'gender'          => 'female',
                'joining_date'    => '2023-01-01',
                'employment_type' => 'permanent',
                'basic_salary'    => 25000.00,
                'status'          => 'active',
            ],
        ];

        foreach ($employees as $emp) {
            Employee::updateOrCreate(['id' => $emp['id']], $emp);
        }
    }
}
