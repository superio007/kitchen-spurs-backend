<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // Seed restaurants
        $this->seedRestaurants();

        // Seed orders
        $this->seedOrders();

        $this->command->info('Database seeding completed successfully!');
    }

    /**
     * Seed restaurants from JSON file.
     */
    private function seedRestaurants(): void
    {
        $filePath = database_path('seeders/data/restaurants.json');

        if (!File::exists($filePath)) {
            throw new \RuntimeException(
                "Restaurant data file not found. Expected location: {$filePath}\n" .
                    "Please create the file with an array of restaurant objects containing: restaurant_id, name, location, cuisine"
            );
        }

        $jsonContent = File::get($filePath);
        $restaurants = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "Failed to parse restaurants.json: " . json_last_error_msg()
            );
        }

        if (!is_array($restaurants)) {
            throw new \RuntimeException(
                "Invalid restaurants.json format. Expected an array of restaurant objects."
            );
        }

        $this->command->info("Seeding " . count($restaurants) . " restaurants...");

        // Use bulk upsert to handle duplicates without errors
        foreach ($restaurants as $restaurantData) {
            Restaurant::updateOrCreate(
                ['restaurant_id' => $restaurantData['restaurant_id']],
                $restaurantData
            );
        }

        $this->command->info('Restaurants seeded successfully.');
    }

    /**
     * Seed orders from JSON file.
     */
    private function seedOrders(): void
    {
        $filePath = database_path('seeders/data/orders.json');

        if (!File::exists($filePath)) {
            throw new \RuntimeException(
                "Order data file not found. Expected location: {$filePath}\n" .
                    "Please create the file with an array of order objects containing: id, restaurant_id, order_amount, order_time"
            );
        }

        $jsonContent = File::get($filePath);
        $orders = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "Failed to parse orders.json: " . json_last_error_msg()
            );
        }

        if (!is_array($orders)) {
            throw new \RuntimeException(
                "Invalid orders.json format. Expected an array of order objects."
            );
        }

        $this->command->info("Seeding " . count($orders) . " orders...");

        // Use bulk upsert to handle duplicates without errors
        // Process in chunks for better performance with large datasets
        $chunks = array_chunk($orders, 1000);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $orderData) {
                Order::updateOrCreate(
                    ['id' => $orderData['id']],
                    $orderData
                );
            }
        }

        $this->command->info('Orders seeded successfully.');
    }
}
