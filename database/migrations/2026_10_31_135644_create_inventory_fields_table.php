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
        if (Schema::hasTable('inventory_fields')) {
            return;
        }

        Schema::create('inventory_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('inventory_field_sections')
                ->nullOnDelete();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('type')->default('text');
            $table->string('help')->nullable();
            /**
             * 'column' => el valor vive en una columna real de tblEquipo.
             * 'custom' => el valor vive en el JSON tblEquipo.attributes.
             */
            $table->string('source')->default('column');
            $table->json('validation_rules')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_fields');
    }
};
