<?php

namespace App\Livewire;

use App\Models\ManagementGroup;
use App\Models\PartnerGroup;
use App\Models\WebsiteSetting;
use Livewire\Component;

class HomePage extends Component
{
    public function render()
    {
        $setting = WebsiteSetting::current();
        $content = WebsiteSetting::contentWithDefaults($setting->content);

        return view('livewire.home-page', [
            'content' => $content,
            'managementGroups' => ManagementGroup::query()
                ->active()
                ->with('activeMembers')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'partnerGroups' => PartnerGroup::query()
                ->active()
                ->with('activePartners')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ])->layout('components.layouts.public', [
            'title' => $content['site']['title'] ?? 'ISOC Jakarta',
        ]);
    }
}
