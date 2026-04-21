<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <p class="text-muted mb-3">
        Once your account is deleted, all of its resources and data will be permanently deleted.
        Before deleting your account, please download any data or information that you wish to retain.
    </p>

    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#confirmDeleteModal">
        <i class="fas fa-trash-alt mr-1"></i> Delete Account
    </button>

    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Are you sure?</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form wire:submit="deleteUser">
                    <div class="modal-body">
                        <p>Once your account is deleted, all of its resources and data will be permanently deleted.
                        Please enter your password to confirm.</p>
                        <div class="form-group mb-0">
                            <label for="delete_password">Password</label>
                            <input wire:model="password" id="delete_password" name="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Enter your password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <span wire:loading.remove>Delete Account</span>
                            <span wire:loading><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
