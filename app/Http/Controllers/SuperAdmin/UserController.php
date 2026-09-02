<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function destroy(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super admin accounts cannot be deleted from here.');
        }

        if (! in_array($user->role, ['admin', 'agent'], true)) {
            return back()->with('error', 'This account cannot be deleted.');
        }

        if ($user->role === 'agent' && $user->tickets()->exists()) {
            return back()->with(
                'error',
                'Cannot delete an agent who has recorded ticket sales. Suspend the account or delete the organization instead.'
            );
        }

        $name = $user->name;
        $companyId = $user->company_id;

        $user->delete();

        if ($request->boolean('redirect_company') && $companyId) {
            return redirect()->route('super-admin.companies.show', $companyId)
                ->with('status', "User \"{$name}\" has been deleted.");
        }

        return redirect()->route('super-admin.company-admins.index')
            ->with('status', "User \"{$name}\" has been deleted.");
    }
}
