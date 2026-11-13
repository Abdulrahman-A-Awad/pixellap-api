<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stats', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('value');
            $table->string('prefix', 10)->nullable();
            $table->string('suffix', 10)->nullable();
            $table->json('label');
            $table->unsignedInteger('sort')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats');
    }
};
