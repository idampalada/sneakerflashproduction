@extends('layouts.app')

@section('title', 'Edit Profile - SneakerFlash')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Edit Profile</h1>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="/profile">
            @csrf
            @method('PATCH')
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" value="{{ $user->name ?? '' }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="{{ $user->email ?? '' }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" 
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Update Profile
                    </button>
                    <a href="/profile" 
                       class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection