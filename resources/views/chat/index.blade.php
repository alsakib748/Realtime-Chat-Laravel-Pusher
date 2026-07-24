<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bangladesh Chat') }}
        </h2>
    </x-slot>

    {{-- Full height chat container --}}
    <div class="h-screen flex flex-col">
        {{-- Header space (if needed) --}}
        <div class="flex-shrink-0">
            {{-- This div accounts for the header height --}}
        </div>

        {{-- Chat takes remaining height --}}
        <div class="flex-1 overflow-hidden">
            <div class="h-full bg-white">
                {{-- Use the chat layout component --}}
                <x-chat-layout :user="$user" :generalRoom="$generalRoom" :allUsers="$allUsers" />
            </div>
        </div>
    </div>
</x-app-layout>
