<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            // 👤 Add user ownership (dashboard system)
            $table->foreignId('user_id')
                ->after('id')
                ->constrained()
                ->onDelete('cascade');

            // 📝 Improve status system (pending → draft/published)
            $table->enum('status', ['draft', 'published'])
                ->default('draft')
                ->change();

            // 📅 Track when project is published
            $table->timestamp('published_at')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('published_at');

            // revert status back if needed
            $table->string('status')->default('pending')->change();
        });
    }
};