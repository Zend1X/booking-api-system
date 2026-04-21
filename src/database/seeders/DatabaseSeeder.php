<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем админа
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@booking.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Создаем обычных пользователей
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@booking.com",
                'password' => Hash::make('password123'),
                'role' => 'user'
            ]);
        }

        // Создаем комнаты
        $rooms = [
            [
                'name' => 'Переговорная А',
                'capacity' => 4,
                'location' => '1 этаж',
                'equipment' => ['TV', 'Доска', 'WiFi']
            ],
            [
                'name' => 'Конференц-зал',
                'capacity' => 20,
                'location' => '3 этаж',
                'equipment' => ['Проектор', 'Звуковая система', 'WiFi', 'Видеоконференцсвязь']
            ],
            [
                'name' => 'Переговорная B',
                'capacity' => 6,
                'location' => '2 этаж',
                'equipment' => ['TV', 'Доска', 'WiFi']
            ],
            [
                'name' => 'Скайп-рум',
                'capacity' => 2,
                'location' => '1 этаж',
                'equipment' => ['Телефон', 'WiFi']
            ],
            [
                'name' => 'Большой зал',
                'capacity' => 50,
                'location' => '5 этаж',
                'equipment' => ['Проектор', 'Звук', 'Микрофоны', 'WiFi', 'Трибуна']
            ]
        ];

        foreach ($rooms as $roomData) {
            Room::create([
                'name' => $roomData['name'],
                'capacity' => $roomData['capacity'],
                'location' => $roomData['location'],
                'equipment' => $roomData['equipment'],
                'created_by' => $admin->id
            ]);
        }
    }
}