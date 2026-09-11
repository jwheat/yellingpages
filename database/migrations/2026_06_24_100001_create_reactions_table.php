<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rant_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->timestamps();

            // One reaction per user per rant (they can switch which emoji).
            $table->unique(['user_id', 'rant_id']);
            $table->index(['rant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
