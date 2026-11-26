<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin polish:
 * - roles.description: the short line shown in the roles list and under the user form's role select.
 * - users.admin_locale defaults to Arabic for new accounts (existing users keep their choice).
 */
return new class extends Migration
{
    public function up(): void
    {
        $roles = config('permission.table_names.roles', 'roles');

        if (! Schema::hasColumn($roles, 'description')) {
            Schema::table($roles, function (Blueprint $table): void {
                $table->string('description', 255)->nullable()->after('name');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('admin_locale', 2)->default('ar')->change();
        });
    }

    public function down(): void
    {
        $roles = config('permission.table_names.roles', 'roles');

        if (Schema::hasColumn($roles, 'description')) {
            Schema::table($roles, function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('admin_locale', 2)->default('en')->change();
        });
    }
};
