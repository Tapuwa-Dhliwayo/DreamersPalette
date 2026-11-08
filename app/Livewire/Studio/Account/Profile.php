<?php

namespace App\Livewire\Studio\Account;

use App\Livewire\Forms\Account\ProfileForm;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.studio')]
#[Title('Account')]
class Profile extends Component
{
    use WithFileUploads;

    public ProfileForm $form;

    public string $notice = '';

    public function mount(): void
    {
        $this->authorize('updateProfile', auth()->user());
        $profile = auth()->user()->profile;
        $this->form->display_name = $profile?->display_name ?? auth()->user()->name;
        $this->form->bio = $profile?->bio ?? '';
    }

    public function updatedFormAvatar(): void
    {
        $this->authorize('updateProfile', auth()->user());
        $this->notice = '';
        $this->validateOnly('form.avatar');
    }

    public function save(): void
    {
        $this->authorize('updateProfile', auth()->user());
        $fields = $this->form->fields();
        $uploadedPath = null;
        try {
            if ($fields['avatar']) {
                $uploadedPath = $fields['avatar']->store('avatars/'.auth()->id(), 'public');
                abort_unless($uploadedPath, 500, 'The profile image could not be saved.');
            }
            auth()->user()->getConnection()->transaction(function () use ($fields, $uploadedPath): void {
                $user = User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                $this->authorize('updateProfile', $user);
                $profile = $user->profile()->firstOrNew();
                $profile->fill(['display_name' => $fields['display_name'], 'bio' => $fields['bio'] ?: null]);
                if ($fields['remove_avatar']) {
                    $profile->avatar_url = null;
                }
                if ($uploadedPath) {
                    $profile->avatar_url = Storage::disk('public')->url($uploadedPath);
                }
                $profile->save();
            });
        } catch (\Throwable $exception) {
            if ($uploadedPath) {
                Storage::disk('public')->delete($uploadedPath);
            }
            throw $exception;
        }
        auth()->user()->unsetRelation('profile');
        $this->form->reset('avatar', 'remove_avatar');
        $this->notice = 'Profile saved.';
    }

    public function render()
    {
        $this->authorize('updateProfile', auth()->user());

        return view('livewire.studio.account.profile', ['profile' => auth()->user()->profile]);
    }
}
