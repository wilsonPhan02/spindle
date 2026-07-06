<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

new class extends Component {
    use WithFileUploads;

    public $username;
    public $occupation;
    public $birth_date;
    public $gender;
    public $new_photo;
    public $is_photo_removed = false;

    public function mount(){
        $this->loadData();
    }

    public function loadData() {
        $user = auth()->user();
        $profile = $user->profile;
        if ($profile) {
            $this->username = $profile->username;
            $this->occupation = $profile->occupation;
            $this->birth_date = $profile->birthdate;
            $this->gender = $profile->gender;
        }
    }

    #[On('open-edit-profile')]
    public function resetState() {
        $this->loadData();
        $this->new_photo = null;
        $this->is_photo_removed = false;
        $this->resetValidation();
    }

    public function updateProfile(){
        $this->validate([
            'username' => 'required|string|max:60',
            'occupation' => 'nullable|string|max:60',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'new_photo' => 'nullable|image|max:5120',
        ], [
            'new_photo.max' => 'The selected image is too large. The maximum allowed file size is 5MB.',
            'new_photo.image' => 'The selected file type is not supported. Please upload an image.',
        ]);

        $user = auth()->user();
        $profile = $user->profile;

        $updateData = [
            'username' => trim($this->username), 
            'occupation' => trim($this->occupation) === '' ? null : trim($this->occupation),
            'birthdate' => empty($this->birth_date) ? null : $this->birth_date, 
            'gender' => empty($this->gender) ? null : $this->gender,
        ];

        if ($this->is_photo_removed && !$this->new_photo) {
            if ($profile->avatar_url) Storage::disk('public')->delete($profile->avatar_url);
            $updateData['avatar_url'] = null;
        }

        if ($this->new_photo) {
            if ($profile->avatar_url) Storage::disk('public')->delete($profile->avatar_url);
            $path = $this->new_photo->store('avatars', 'public');
            $updateData['avatar_url'] = $path; 
        }

        $profile->update($updateData);
        // $this->dispatch('profile-updated');
        // $this->dispatch('close-modal');

        $newAvatarUrl = $profile->avatar_url ? Storage::url($profile->avatar_url) : null;

        // Dispatch dengan parameter bernama 'avatarUrl'
        $this->dispatch('profile-updated', 
            avatarUrl: $newAvatarUrl,
            newName: trim($this->username)
        );
        
        $this->dispatch('close-modal');
    }
}; ?>

<div 
    x-data="{ show: false }" x-show="show" style="display: none;"
    @open-edit-profile.window="show = true; $wire.resetState()" 
    @close-modal.window="show = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-text-80/75 backdrop-blur-[1.5px]"
>
    <div @click.away="show = false" 
        class="bg-brand-10 rounded-2xl border-2 border-brand-150 shadow-2xl w-full max-w-5xl p-12 relative overflow-visible flex flex-col gap-10">
        
        <h2 class="text-app-heading-1 text-text-100 text-center">Edit Profile</h2>

        <form wire:submit="updateProfile" class="flex flex-col justify-between gap-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-stretch relative">
                
                {{-- KOLOM KIRI --}}
                <div class="flex flex-col items-center gap-8 text-center h-full">
                    <x-avatar :editable="true" model="new_photo" size="w-48 h-48"
                        :imageUrl="optional(auth()->user()->profile)->avatar_url ? Storage::url(auth()->user()->profile->avatar_url) : null" />

                    <x-form-input label="What is your new preferred name?" 
                        placeholder="Enter your preferred name" 
                        model="username" maxlength="60" />
                </div>

                <div class="hidden md:block absolute left-1/2 top-0 bottom-0 w-[0.5px] bg-brand-200"></div>

                {{-- KOLOM KANAN --}}
                <div class="flex flex-col justify-between h-full gap-4">
                    <x-form-input label="Occupation" 
                        placeholder="Enter your occupation" 
                        model="occupation" maxlength="60" />
                    
                    <x-form-input label="Birth Date" type="date" model="birth_date" placeholder="Select your birth date">
                        <x-slot:icon>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        </x-slot:icon>
                    </x-form-input>

                    <x-form-input label="Gender" type="select" placeholder="Select your gender" model="gender" 
                        :options="['male' => 'Male', 'female' => 'Female']" />
                </div>
            </div>

            <x-dialog-footer message="About to revise your manuscript of self. Continue?" confirmText="Yes, save change!" />
        </form>
    </div>
</div>