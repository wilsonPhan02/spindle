<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new #[Layout('layouts.app')] class extends Component {
    public $user;
    public $profile;

    public function mount(){
        $this->loadData();
    }

    public function loadData()
    {
        $this->user = Auth::user()->fresh();
        $this->profile = $this->user->profile;
    }

    #[On('profile-updated')]
    public function refreshSettings()
    {
        $this->loadData();
    }

}; ?>

<div class="p-10 pb-7 w-full max-w-8xl mx-auto">
    <!-- Header -->
    <header class="flex justify-between items-center mb-12">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 text-[18px] text-[#7A7A7A] hover:text-[#8C7558] transition-colors">
            <x-icons.chevron rotate="180" size="w-3.5 h-3.5" color="currentColor"/>
            <span class="text-[#2C2C2C] font-semibold">Settings</span>
        </a>
    </header>
    <!-- Profile Section-->
    <section class="mb-10 w-full max-w-4xl mx-auto">
        <div class="items-center gap-4 mb-6">
            <h2 class="text-web-body-small text-[14px] text-subtext-100 whitespace-nowrap mb-2">Your Profile</h2>
            <hr class="w-full border-subtext-80">
        </div>

        <div class="flex items-center gap-15 pl-8">
            <!-- Avatar -->
            <x-avatar
                size="w-35 h-35"
                :imageUrl="auth()->user()->profile->avatar_url ? Storage::url(auth()->user()->profile->avatar_url) : null"
            />

            <!-- Profile Data Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-5 gap-x-12 w-full pt-1 justify-between">
                <x-settings-items variant="info" label="Username" :value="$profile->username ?? 'Sailor Shift'" ></x->
                <x-settings-items variant="info" label="Occupation" :value="$profile->occupation ?? 'None'" ></x->
                <x-settings-items variant="info" label="Gender" :value="$profile->gender ? ucfirst($profile->gender) : 'None'" ></x->
                <x-settings-items variant="info" label="Email" :value="$user->email ?? 'None'" ></x->
                <x-settings-items variant="info" label="Birth Date" :value="$profile->birthdate ? \Carbon\Carbon::parse($profile->birthdate)->format('d F Y') : 'None'" ></x->
            </div>
        </div>
    </section>

    <!-- Account & Preference Section -->
    <section class="mb-1 w-full max-w-4xl mx-auto">
        <div class="items-center gap-4 mb-2">
            <h2 class="text-web-body-small text-[14px] text-subtext-100 whitespace-nowrap mb-2">Account & Preference</h2>
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
            <x-settings-items variant="menu" label="Edit Profile" @click="$dispatch('open-edit-profile')">
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
                    <x-icons.delete class="w-5 h-5" />
                </x-slot:icon>
            </x-settings-items>
        </div>
    </section>

    {{-- import popup / dialog --}}
    <livewire:settings.logout-dialog />
    <livewire:settings.delete-dialog />
    <livewire:settings.change-password />
    <livewire:auth.forgot-password />
    <livewire:settings.edit-profile wire:key="modal-edit-{{ auth()->user()->profile->avatar_url }}"/>

</div>



