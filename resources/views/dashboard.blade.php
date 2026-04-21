<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h1 class="h4 mb-0">Dashboard</h1>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>Welcome</h3>
                    <p>{{ auth()->user()->name }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>Profile</h3>
                    <p>Manage account settings</p>
                </div>
                <div class="icon">
                    <i class="fas fa-cog"></i>
                </div>
                <a href="{{ route('profile') }}" class="small-box-footer" wire:navigate>Go to profile <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>Start</h3>
                    <p>Use the sidebar to navigate</p>
                </div>
                <div class="icon">
                    <i class="fas fa-compass"></i>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
