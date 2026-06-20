<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            // Link project to logged-in user
            $table->foreignId('user_id')
                ->after('id')
                ->constrained()
                ->onDelete('cascade');


            // URL friendly identifier
            $table->string('slug')
                ->unique()
                ->after('title');


            // Track publishing
            $table->timestamp('published_at')
                ->nullable()
                ->after('status');

        });
    }


    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            $table->dropForeign(['user_id']);

            $table->dropColumn([
                'user_id',
                'slug',
                'published_at'
            ]);

        });
    }
};