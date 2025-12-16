<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLockedElementIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // One "locked" element per user (used to filter the list to a single subtree)
            $table->unsignedBigInteger('locked_element_id')->nullable()->after('show_mode');
            $table->index('locked_element_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['locked_element_id']);
            $table->dropColumn('locked_element_id');
        });
    }
}


