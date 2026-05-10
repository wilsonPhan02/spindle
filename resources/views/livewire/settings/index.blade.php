<?php

use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $user;
    public $profile;

    public function mount(){
        $this->user = Auth::user();
        $this->profile = $this->user->profile;
    }

}; ?>

<div class="p-10 w-full max-w-8xl mx-auto">
    <!-- Header -->
    <header class="flex justify-between items-center mb-12">
        <a href="{{ route('dashboard') ?? '#' }}" class="flex items-center gap-3 text-web-subheading-1 text-text-80 hover:text-secondary-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Settings
        </a>
        <x-logo class="h-10 w-auto text-text-80" />
    </header>

    <!-- Profile Section-->
    <section class="mb-14 w-full max-w-4xl mx-auto">
        <div class="items-center gap-4 mb-8">
            <h2 class="text-web-body-small text-subtext-100 whitespace-nowrap mb-2">Your Profile</h2>
            <hr class="w-full border-subtext-80">
        </div>

        <div class="flex items-center gap-15 pl-8">
            <!-- Avatar -->
            <div class="w-32 h-32 rounded-full bg-secondary-5 border border-secondary-20 flex items-center justify-center overflow-hidden flex-shrink-0 relative shadow-sm">
                @if($profile && $profile->avatar_url)
                    <img src="{{ $profile->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                @else
                    <x-icons.default-profile class="w-18 h-18 text-secondary-250" />
                @endif
            </div>

            <!-- Profile Data Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-8 gap-x-12 w-full pt-2 justify-between">
                <x-settings-items variant="info" label="Your Preferred Name" :value="$profile->username ?? 'Sailor Shift'" ></x->
                <x-settings-items variant="info" label="Occupation" :value="$profile->occupation ?? 'None'" ></x->
                <x-settings-items variant="info" label="Gender" :value="$profile->gender ?? 'None'" ></x->
                <x-settings-items variant="info" label="Email" :value="$user->email ?? 'None'" ></x->
                <x-settings-items variant="info" label="Birth Date" :value="$profile->birthdate ?? 'dd-mm-yyyy'" ></x->
            </div>
        </div>
    </section>

    <!-- Account & Preference Section -->
    <section class="mb-14 w-full max-w-4xl mx-auto">
        <div class="items-center gap-4 mb-6">
            <h2 class="text-web-body-small text-subtext-100 whitespace-nowrap mb-2">Account & Preference</h2>
            <hr class="w-full border-subtext-80">
        </div>

        <div class="flex flex-col pl-8 max-w-4xl justify-center">
            <!-- Dark Mode -->
            <x-settings-items variant="toggle" label="Dark Mode">
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </x-slot:icon>
            </x-settings-items>

            <!-- Edit Profile -->
            <x-settings-items variant="menu" label="Edit Profile">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </x-slot:icon>
            </x-settings-items>

            <!-- Change Password -->
            <x-settings-items variant="menu" label="Change Password" @click="$dispatch('open-change-password')">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </x-slot:icon>
            </x-settings-items>

            <!-- Logout -->
            <x-settings-items variant="menu" label="Logout" @click="$dispatch('open-logout-dialog')">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </x-slot:icon>
            </x-settings-items>

            <!-- Delete Account -->
            <x-settings-items variant="menu" label="Delete Account" danger="true" @click="$dispatch('open-delete-dialog')">
                <x-slot:icon>
                    <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </x-slot:icon>
            </x-settings-items>
        </div>
    </section>

    {{-- import popup / dialog --}}
    <livewire:settings.logout-dialog />
    <livewire:settings.delete-dialog />
    <livewire:settings.change-password />

</div>


