<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#5D69F7');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->time('start_time')->default('06:00');
            $table->time('end_time')->default('19:00');
            $table->unsignedSmallInteger('slot_duration')->default(30);
            $table->string('time_format', 2)->default('12');
            $table->string('timezone', 50)->default('America/Bogota');
            $table->json('business_days')->default('[1,2,3,4,5,6]');
            $table->string('visibility', 20)->default('todos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
