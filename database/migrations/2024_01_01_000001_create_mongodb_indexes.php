<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createRestaurantIndexes();
        $this->createOrderIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropRestaurantIndexes();
        $this->dropOrderIndexes();
    }

    /**
     * Create indexes for the restaurants collection.
     */
    private function createRestaurantIndexes(): void
    {
        $collection = DB::connection('mongodb')
            ->getCollection('restaurants');

        // Create unique index on restaurant_id (idempotent - checks existence)
        $indexes = iterator_to_array($collection->listIndexes());
        $indexExists = false;

        foreach ($indexes as $index) {
            if (isset($index['key']['restaurant_id'])) {
                $indexExists = true;
                break;
            }
        }

        if (!$indexExists) {
            $collection->createIndex(
                ['restaurant_id' => 1],
                ['unique' => true, 'name' => 'restaurant_id_unique']
            );
        }
    }

    /**
     * Create indexes for the orders collection.
     */
    private function createOrderIndexes(): void
    {
        $collection = DB::connection('mongodb')
            ->getCollection('orders');

        $existingIndexes = [];
        $indexes = iterator_to_array($collection->listIndexes());

        foreach ($indexes as $index) {
            $existingIndexes[] = $index['name'];
        }

        // Create index on restaurant_id (idempotent)
        if (!in_array('restaurant_id_index', $existingIndexes)) {
            $collection->createIndex(
                ['restaurant_id' => 1],
                ['name' => 'restaurant_id_index']
            );
        }

        // Create index on order_time (idempotent)
        if (!in_array('order_time_index', $existingIndexes)) {
            $collection->createIndex(
                ['order_time' => 1],
                ['name' => 'order_time_index']
            );
        }

        // Create compound index on (restaurant_id, order_time) (idempotent)
        if (!in_array('restaurant_id_order_time_compound', $existingIndexes)) {
            $collection->createIndex(
                ['restaurant_id' => 1, 'order_time' => 1],
                ['name' => 'restaurant_id_order_time_compound']
            );
        }
    }

    /**
     * Drop indexes for the restaurants collection.
     */
    private function dropRestaurantIndexes(): void
    {
        $collection = DB::connection('mongodb')
            ->getCollection('restaurants');

        try {
            $collection->dropIndex('restaurant_id_unique');
        } catch (\Exception $e) {
            // Index might not exist, ignore
        }
    }

    /**
     * Drop indexes for the orders collection.
     */
    private function dropOrderIndexes(): void
    {
        $collection = DB::connection('mongodb')
            ->getCollection('orders');

        try {
            $collection->dropIndex('restaurant_id_index');
            $collection->dropIndex('order_time_index');
            $collection->dropIndex('restaurant_id_order_time_compound');
        } catch (\Exception $e) {
            // Indexes might not exist, ignore
        }
    }
};
