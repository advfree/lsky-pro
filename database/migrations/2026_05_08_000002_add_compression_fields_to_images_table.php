<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompressionFieldsToImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->float('compress_before_size', 20)->default(0)->after('size');
            $table->float('compress_after_size', 20)->default(0)->after('compress_before_size');
            $table->float('compress_ratio', 5)->default(0)->after('compress_after_size');
            $table->string('compress_mode', 20)->nullable()->after('compress_ratio');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn([
                'compress_before_size',
                'compress_after_size',
                'compress_ratio',
                'compress_mode',
            ]);
        });
    }
}
