<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">New ad</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.marketplace-ads.index') }}">Ad placements</a></li>
                    <li class="breadcrumb-item active">New</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('super-admin.marketplace-ads.store') }}" enctype="multipart/form-data">
            @csrf
            @include('super-admin.marketplace-ads._form')
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create ad</button>
                <a href="{{ route('super-admin.marketplace-ads.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
