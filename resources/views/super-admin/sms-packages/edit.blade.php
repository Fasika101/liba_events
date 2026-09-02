<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Edit SMS package</h1></div>
        </div>
    </x-slot>

    <div class="row"><div class="col-lg-8">
        <div class="card card-outline card-primary">
            <form method="POST" action="{{ route('super-admin.sms-packages.update', $package) }}">
                @csrf @method('PUT')
                @include('super-admin.sms-packages._form', ['package' => $package])
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('super-admin.sms-packages.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div></div>
</x-app-layout>
