<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ChatRoom;

class GeneralChatRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $adminUser = User::where('email', 'admin@bangladeshchat.com')->first();

        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'Bangladesh Chat Admin',
                'email' => 'admin@bangladeshchat.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        $generalRoom = ChatRoom::firstOrCreate(
            ['type' => 'general', 'name' => 'General Chat'],
            [
                'description' => 'Welcome to Bangladesh Chat! This is where everyone can chat together.',
                'is_active' => true,
                'created_by' => $adminUser->id,
                'last_message_at' => now()
            ]
        );

        $allUsers = User::all();

        foreach ($allUsers as $user) {
            if (!$generalRoom->users()->where('user_id', $user->id)->exists()) {
                $generalRoom->users()->attach($user->id, [
                    'joined_at' => now(),
                    'is_admin' => $user->id === $adminUser->id,
                    'is_active' => true
                ]);
            }
        }

        $generalRoom->messages()->create([
            'user_id' => $adminUser->id,
            'content' => 'Welcome to Bangladesh Chat! Feel free to introduce yourself and start chatting.',
            'type' => 'system'
        ]);
    }

}
