<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function deleteAccount(){
        $user = Auth::user();
        Auth::logout();
        if ($user) {
            $user->delete();
        }
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('register');
    }
}; ?>

<div>
    <x-confirm-dialog
        EventName="open-delete-dialog"
        :title="__('Delete Account')"
        :description="__('Are you sure want to delete your account?')"
        :cancelText="__('No, Keep it')"
        :confirmText="__('Yes, Delete!')"
        submitAction="deleteAccount"
    >
        <x-slot:icon>
            <x-icons.alert/>
        </x-slot:icon>
    </x-confirm-dialog>
</div>
