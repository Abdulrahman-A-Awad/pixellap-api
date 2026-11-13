<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique(); // English only, shared by both locales
            $table->json('title');
            $table->json('excerpt');
            $table->json('description');
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->unsignedInteger('sort')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
