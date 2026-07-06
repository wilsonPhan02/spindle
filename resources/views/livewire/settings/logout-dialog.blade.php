<?php

use Livewire\Volt\Component;

new class extends Component {
    public function logout() {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        
        $this->dispatch('clear-auth-session');
    }
}; ?>

<div x-data="{
    clearAuth() {
        sessionStorage.removeItem('auth_email');
        sessionStorage.removeItem('auth_password');
        window.location.href = '{{ route('login') }}';
    }
}" @clear-auth-session.window="clearAuth()">
    <x-confirm-dialog
        eventName="open-logout-dialog"
        title="Log out"
        description="Leaving the writer's desk? Your draft will wait here."
        confirmText="Yes, Log out"
        submitAction="logout"
    >
        <x-slot:icon>
            <x-icons.logout class="w-15 h-15" />
        </x-slot:icon>
    </x-confirm-dialog>
</div>