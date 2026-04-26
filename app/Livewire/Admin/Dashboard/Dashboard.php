<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard.dashboard')->layout('layouts.admin.admin');
    }

    public function getWidgetsProperty()
    {
        $user = auth()->user();
        
        // If no user, return empty collection
        if (!$user) {
            return collect([]);
        }

        $userRoles = $user->getRoleNames()->toArray();
        
        // Admin always sees all widgets
        if (in_array('admin', $userRoles)) {
            return collect(config('dashboard_widgets'))->values();
        }

        return collect(config('dashboard_widgets'))
            ->filter(function ($widget) use ($user, $userRoles) {
                
                // If no roles specified, show to everyone
                if (!isset($widget['roles']) || empty($widget['roles'])) {
                    return true;
                }

                // Check if user has at least one required role
                $hasRequiredRole = false;
                foreach ($widget['roles'] as $requiredRole) {
                    if (in_array($requiredRole, $userRoles)) {
                        $hasRequiredRole = true;
                        break;
                    }
                }
                
                return $hasRequiredRole;
            })
            ->values();
    }
}

