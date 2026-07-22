<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new #[Layout('layouts.app')] class extends Component {
    public $user;
    public $profile;
    public string $language = 'en';
    public string $theme = 'light';

    /**
     * Daftar bahasa yang tersedia.
     * 'cc' = ISO 3166-1 alpha-2 country code untuk flagcdn.com
     */
    public static function availableLanguages(): array
    {
        return [
            ['code' => 'en', 'name' => 'English',           'cc' => 'us'],
            ['code' => 'id', 'name' => 'Bahasa Indonesia',   'cc' => 'id'],
            ['code' => 'ja', 'name' => '日本語',              'cc' => 'jp'],
            ['code' => 'zh', 'name' => '中文 (简体)',          'cc' => 'cn'],
            ['code' => 'ko', 'name' => '한국어',              'cc' => 'kr'],
        ];
    }

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->user     = Auth::user()->fresh();
        $this->profile  = $this->user->profile;
        $this->language = $this->profile->language ?? 'en';
        $this->theme    = $this->profile->theme ?? 'light';
    }

    public function updateTheme(string $newTheme): void
    {
        if (! in_array($newTheme, ['light', 'dark'], true)) return;

        if ($this->profile) {
            $this->profile->update(['theme' => $newTheme]);
        }

        $this->theme = $newTheme;
    }

    public function saveLanguage(string $lang)
    {
        $validCodes = array_column(self::availableLanguages(), 'code');
        if (! in_array($lang, $validCodes, true)) return;

        return redirect()->route('lang.switch', $lang);
    }

    #[On('profile-updated')]
    public function refreshSettings(): void
    {
        $this->loadData();
    }

}; ?>

<div class="p-10 max-w-7xl mx-auto" x-data="{
    init() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('edit_profile') === '1') {
            setTimeout(() => {
                $dispatch('open-edit-profile');
                // Clean up URL without reloading
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }, 300);
        }
    }
}">

    {{-- Header --}}
    <header class="flex justify-between items-center mb-8">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 text-[18px] text-subtext-100 hover:text-secondary-200 transition-colors">
            <x-icons.chevron rotate="180" size="w-3.5 h-3.5" color="currentColor"/>
            <span class="text-text-70 text-app-subtitle-1 font-semibold">{{ __('Settings') }}</span>
        </a>
    </header>

    {{-- Profile Section --}}
    <section class="mb-8 w-full max-w-4xl mx-auto">
        <div class="mb-4">
            <h2 class="text-web-body-small text-[13px] text-subtext-100 whitespace-nowrap mb-1.5">{{ __('Your Profile') }}</h2>
            <hr class="w-full border-subtext-80">
        </div>

        <div class="flex items-center gap-12 pl-6">
            {{-- Avatar --}}
            <x-avatar
                size="w-24 h-24"
                :imageUrl="auth()->user()->profile->avatar_url ? Storage::url(auth()->user()->profile->avatar_url) : null"
            />

            {{-- Profile Data Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-4 gap-x-12 w-full pt-1">
                <x-settings-items variant="info" label="{{ __('Username') }}"   :value="$profile->username ?? __('Sailor Shift')"></x->
                <x-settings-items variant="info" label="{{ __('Occupation') }}" :value="$profile->occupation ?? __('None')"></x->
                <x-settings-items variant="info" label="{{ __('Gender') }}"     :value="$profile->gender ? __(ucfirst($profile->gender)) : __('None')"></x->
                <x-settings-items variant="info" label="{{ __('Email') }}"      :value="$user->email ?? __('None')"></x->
                <x-settings-items variant="info" label="{{ __('Birth Date') }}" :value="$profile->birthdate ? \Carbon\Carbon::parse($profile->birthdate)->translatedFormat('d F Y') : __('None')"></x->
            </div>
        </div>
    </section>

    {{-- Account & Preference Section --}}
    <section class="w-full max-w-4xl mx-auto">
        <div class="mb-2">
            <h2 class="text-web-body-small text-[13px] text-subtext-100 whitespace-nowrap mb-1.5">{{ __('Account & Preference') }}</h2>
            <hr class="w-full border-subtext-80">
        </div>

        <div class="flex flex-col pl-6 max-w-4xl">

            {{-- Language --}}
            <x-settings-items
                variant="dropdown"
                label="{{ __('Language') }}"
                :options="$this::availableLanguages()"
                :current="$language"
                wire:change="saveLanguage"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                </x-slot:icon>
            </x-settings-items>

            {{-- Dark Mode --}}
            <x-settings-items
                variant="toggle"
                label="{{ __('Dark Mode') }}"
                :isOn="$theme === 'dark'"
                x-data="{
                    isOn: false,
                    init() {
                        this.isOn = ($store.theme ? $store.theme.isDark : document.documentElement.classList.contains('dark'));
                    }
                }"
                @click="
                    () => {
                        try {
                            const nextState = $store.theme ? $store.theme.toggle() : !isOn;
                            isOn = nextState;
                            $wire.updateTheme(nextState ? 'dark' : 'light');
                        } catch (e) {}
                    }
                "
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </x-slot:icon>
            </x-settings-items>

            {{-- Edit Profile --}}
            <x-settings-items variant="menu" label="{{ __('Edit Profile') }}" @click="$dispatch('open-edit-profile')">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </x-slot:icon>
            </x-settings-items>

            {{-- Change Password --}}
            @php
                $isGoogleAccount = !empty($user->google_id) || empty($user->password);
            @endphp
            <x-settings-items
                variant="menu"
                label="{{ __('Change Password') }}"
                :disabled="$isGoogleAccount"
                title="{{ $isGoogleAccount ? __('Not available for accounts signed in with Google') : '' }}"
                @click="if (!{{ $isGoogleAccount ? 'true' : 'false' }}) $dispatch('open-change-password')"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </x-slot:icon>
            </x-settings-items>

            {{-- Logout --}}
            <x-settings-items variant="menu" label="{{ __('Logout') }}" @click="$dispatch('open-logout-dialog')">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </x-slot:icon>
            </x-settings-items>

            {{-- Delete Account --}}
            <x-settings-items variant="menu" label="{{ __('Delete Account') }}" danger="true" @click="$dispatch('open-delete-dialog')">
                <x-slot:icon>
                    <x-icons.delete class="w-5 h-5" />
                </x-slot:icon>
            </x-settings-items>

        </div>
    </section>

    {{-- Dialogs & Modals --}}
    <livewire:settings.logout-dialog />
    <livewire:settings.delete-dialog />
    <livewire:settings.change-password />
    <livewire:auth.forgot-password />
    <livewire:settings.edit-profile wire:key="modal-edit-{{ auth()->user()->profile->avatar_url }}"/>

</div>
