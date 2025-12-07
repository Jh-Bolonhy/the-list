<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddOrderToElementsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists
        if (!Schema::hasColumn('elements', 'order')) {
            Schema::table('elements', function (Blueprint $table) {
                $table->unsignedInteger('order')->default(0)->after('parent_element_id');
            });
        }

        // Set initial order values for existing elements
        // Group by user_id and parent_element_id, assign order based on created_at
        // SQLite-compatible version
        $users = DB::table('elements')->distinct()->pluck('user_id');
        foreach ($users as $userId) {
            // Process root elements (parent_element_id IS NULL)
            $rootElements = DB::table('elements')
                ->where('user_id', $userId)
                ->whereNull('parent_element_id')
                ->orderBy('created_at', 'asc')
                ->get();
            
            foreach ($rootElements as $index => $element) {
                DB::table('elements')
                    ->where('id', $element->id)
                    ->update(['order' => $index + 1]);
            }

            // Process elements with parents
            $parentIds = DB::table('elements')
                ->where('user_id', $userId)
                ->whereNotNull('parent_element_id')
                ->distinct()
                ->pluck('parent_element_id');
            
            foreach ($parentIds as $parentId) {
                $childElements = DB::table('elements')
                    ->where('user_id', $userId)
                    ->where('parent_element_id', $parentId)
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                foreach ($childElements as $index => $element) {
                    DB::table('elements')
                        ->where('id', $element->id)
                        ->update(['order' => $index + 1]);
                }
            }
        }

        // Create unique index for (parent_element_id, order) within same user_id
        // Note: We can't create a unique index directly because parent_element_id can be NULL
        // Instead, we'll create a regular index and handle uniqueness in application logic
        Schema::table('elements', function (Blueprint $table) {
            $table->index(['user_id', 'parent_element_id', 'order'], 'elements_user_parent_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elements', function (Blueprint $table) {
            $table->dropIndex('elements_user_parent_order_index');
            $table->dropColumn('order');
        });
    }
}
