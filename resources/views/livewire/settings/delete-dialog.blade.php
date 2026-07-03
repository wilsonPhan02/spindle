<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function deleteAccount(){
        $user = Auth::user();
        if ($user) {
            $user->delete();
        }
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('register');
    }
}; ?>

<div>
    <x-confirm-dialog
        EventName="open-delete-dialog"
        title="Delete Account"
        description="Are you sure want to delete your account?"
        cancelText="No, Keep it"
        confirmText="Yes, Delete!"
        submitAction="deleteAccount"
    >
        <x-slot:icon>
            <x-icons.alert/>
        </x-slot:icon>
    </x-confirm-dialog>
</div>
