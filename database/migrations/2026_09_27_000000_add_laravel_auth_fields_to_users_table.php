<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->after('id');
            } else {
                $table->string('name')->nullable()->change();
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('pelajar')->after('password');
            }

            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'remember_token')) {
                $table->dropRememberToken();
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
