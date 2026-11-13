<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique(); // App\Enums\HomeSectionKey
            $table->json('eyebrow')->nullable();
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->json('cta2_label')->nullable();
            $table->string('cta2_url')->nullable();
            $table->unsignedInteger('sort')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
