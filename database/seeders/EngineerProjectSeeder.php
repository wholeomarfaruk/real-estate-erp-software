<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EngineerProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = \App\Models\Project::all();
        $engineers = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'engineer');
        })->get();
        foreach ($engineers as $engineer) {
            $assignedProjects = $projects->random(rand(1, 3))->pluck('id')->toArray();
            $engineer->projects()->attach($assignedProjects);
        }
    }
}
