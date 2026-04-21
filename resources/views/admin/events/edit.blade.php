<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit Event</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Events</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-10 col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Edit: {{ $event->title }}</h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('admin.events.update', $event) }}" method="POST" enctype="multipart/form-data">
                        @method('PUT')
                        @include('admin.events.form', ['event' => $event])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
