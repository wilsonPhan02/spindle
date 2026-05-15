<?php

use Livewire\Volt\Component;

new class extends Component {
    public function logout() {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        
        return redirect()->route('login'); 
    }
}; ?>

<div>
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