<?php

use App\Livewire\Actions\Logout as LogoutAction;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(LogoutAction $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

{{-- Navigation is now handled directly in layouts/app.blade.php --}}
<div></div>
