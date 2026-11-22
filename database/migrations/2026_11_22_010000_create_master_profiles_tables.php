<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-manageable profile types (Customer, Supplier, Lender, ...)
        Schema::create('profile_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->string('icon', 40)->default('user');
            $table->string('tone', 20)->default('slate');
            $table->string('module', 30)->nullable();       // customer | supplier | employee | null
            $table->string('login_role', 50)->nullable();   // spatie role given to a login created for this type
            $table->json('fields')->nullable();             // [{key,label,type,required}]
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // One master identity per person / company
        Schema::create('master_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();           // MP-000001
            $table->string('kind', 20)->default('individual'); // individual | company
            $table->string('name', 150);
            $table->string('organization', 150)->nullable();
            $table->string('designation', 100)->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('phone_alt', 30)->nullable();
            $table->string('email', 150)->nullable()->index();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('doc_type', 30)->nullable();     // nid | passport | trade_license | ...
            $table->string('doc_no', 60)->nullable();
            $table->string('tin_no', 60)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('district', 80)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active'); // active | inactive
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Roles a master profile holds, optionally linked to a module record
        Schema::create('master_profile_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_type_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('linkable');             // Customer / Supplier / Employee
            $table->string('reference', 60)->nullable();
            $table->date('since')->nullable();
            $table->string('status', 20)->default('active');
            $table->json('data')->nullable();               // custom field values
            $table->timestamps();

            $table->unique(['master_profile_id', 'profile_type_id']);
        });

        $now = now();
        $types = [
            ['name' => 'Customer', 'slug' => 'customer', 'icon' => 'user', 'tone' => 'emerald', 'module' => 'customer', 'login_role' => 'client', 'is_system' => true,
                'description' => 'Buyers of units and properties.', 'fields' => []],
            ['name' => 'Supplier', 'slug' => 'supplier', 'icon' => 'truck', 'tone' => 'amber', 'module' => 'supplier', 'login_role' => 'supplier', 'is_system' => true,
                'description' => 'Material and service vendors.', 'fields' => []],
            ['name' => 'Employee', 'slug' => 'employee', 'icon' => 'badge', 'tone' => 'sky', 'module' => 'employee', 'login_role' => 'employee', 'is_system' => true,
                'description' => 'Staff on payroll.', 'fields' => []],
            ['name' => 'Lender', 'slug' => 'lender', 'icon' => 'bank', 'tone' => 'violet', 'module' => null, 'login_role' => null, 'is_system' => false,
                'description' => 'Banks or persons who lend money to the company.', 'fields' => [
                    ['key' => 'lender_type', 'label' => 'Lender Type', 'type' => 'select', 'options' => 'Bank, Individual, NBFI', 'required' => false],
                    ['key' => 'loan_amount', 'label' => 'Loan Amount', 'type' => 'number', 'required' => false],
                    ['key' => 'interest_rate', 'label' => 'Interest Rate (%)', 'type' => 'number', 'required' => false],
                    ['key' => 'maturity_date', 'label' => 'Maturity Date', 'type' => 'date', 'required' => false],
                ]],
            ['name' => 'Director', 'slug' => 'director', 'icon' => 'crown', 'tone' => 'rose', 'module' => null, 'login_role' => 'md', 'is_system' => false,
                'description' => 'Board members and shareholders.', 'fields' => [
                    ['key' => 'position', 'label' => 'Board Position', 'type' => 'text', 'required' => false],
                    ['key' => 'share_percent', 'label' => 'Share (%)', 'type' => 'number', 'required' => false],
                    ['key' => 'appointed_on', 'label' => 'Appointed On', 'type' => 'date', 'required' => false],
                ]],
            ['name' => 'Landowner', 'slug' => 'landowner', 'icon' => 'map', 'tone' => 'teal', 'module' => null, 'login_role' => null, 'is_system' => false,
                'description' => 'Land owners in joint-venture projects.', 'fields' => [
                    ['key' => 'land_area', 'label' => 'Land Area (katha)', 'type' => 'number', 'required' => false],
                    ['key' => 'owner_share', 'label' => 'Owner Share (%)', 'type' => 'number', 'required' => false],
                    ['key' => 'agreement_date', 'label' => 'Agreement Date', 'type' => 'date', 'required' => false],
                ]],
        ];

        foreach ($types as $i => $type) {
            DB::table('profile_types')->insert(array_merge($type, [
                'fields'     => json_encode($type['fields']),
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // Permissions (spatie) — granted to superadmin & admin
        if (Schema::hasTable('permissions')) {
            $names = ['profile.view', 'profile.create', 'profile.edit', 'profile.delete', 'profile.type.manage', 'profile.login.manage'];
            foreach ($names as $name) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }

            $permissionIds = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            $roleIds = DB::table('roles')->whereIn('name', ['superadmin', 'admin'])->pluck('id');

            foreach ($roleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
                }
            }

            app()['cache']->forget('spatie.permission.cache');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_profile_roles');
        Schema::dropIfExists('master_profiles');
        Schema::dropIfExists('profile_types');

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'like', 'profile.%')->delete();
            app()['cache']->forget('spatie.permission.cache');
        }
    }
};
