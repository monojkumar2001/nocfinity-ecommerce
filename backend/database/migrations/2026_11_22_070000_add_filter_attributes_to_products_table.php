<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('color', 50)->nullable()->after('stock')->index();
            $table->string('size', 50)->nullable()->after('color')->index();
            $table->string('dress_style', 50)->nullable()->after('size')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['color', 'size', 'dress_style']);
        });
    }
};
