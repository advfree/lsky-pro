<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('optimized_pathname')->nullable()->after('name')->comment('Optimized share image path');
            $table->string('optimized_mimetype', 32)->nullable()->after('mimetype')->comment('Optimized share image mime');
            $table->decimal('optimized_size')->nullable()->after('size')->comment('Optimized share image size(kb)');
        });
    }

    public function down()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['optimized_pathname', 'optimized_mimetype', 'optimized_size']);
        });
    }
};
