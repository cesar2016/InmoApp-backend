<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'banned_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'banned_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('banned_at');
            });
        }
    }
};
