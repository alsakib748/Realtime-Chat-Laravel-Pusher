<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bangladesh Chat') }}
        </h2>
    </x-slot>

    <div class="h-screen flex flex-col">
        <div class="flex-shrink-0"></div>

        <div class="flex-1 overflow-hidden">
            <div class="h-full bg-white">
                <x-chat-layout :user="$user" :generalRoom="$generalRoom" :allUsers="$allUsers" />
            </div>
        </div>
    </div>
</x-app-layout>
