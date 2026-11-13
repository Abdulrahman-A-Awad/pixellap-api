<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_strings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique(); // e.g. "nav.services"
            $table->string('group')->index();
            $table->json('value'); // translatable {en, ar}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_strings');
    }
};
