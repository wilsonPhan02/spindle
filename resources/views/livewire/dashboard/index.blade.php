<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    // Lu bisa ambil data user di sini kalau mau
    public function with()
    {
        return [
            'userName' => auth()->user()->name ?? 'Sailor Shift',
        ];
    }
}; ?>

<div class="p-10">
    <header class="mb-10">
        <h1 class="text-app-title-2 text-text-100">Welcome Back, {{ $userName }}!</h1>
        <p class="text-app-body-large text-subtext-100">Start weaving your story today.</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-card-bg border border-card-border p-6 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-app-heading-2 text-text-90 mb-2">Create New Project</h3>
            <p class="text-app-body-medium text-subtext-90 mb-6">Create a fresh yarn for your new literary masterpiece.</p>
            <button class="bg-secondary-200 text-bg-main text-app-feature px-5 py-2.5 rounded-lg hover:bg-opacity-90 transition-colors">
                + New Project
            </button>
        </div>
    </div>
</div>