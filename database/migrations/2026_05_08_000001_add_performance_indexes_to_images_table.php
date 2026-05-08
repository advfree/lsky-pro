<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexesToImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('images', function (Blueprint $table) {
            $table->index(['key', 'extension'], 'key_extension');
            $table->index(['strategy_id', 'md5', 'sha1'], 'strategy_md5_sha1');
            $table->index(['user_id', 'created_at'], 'user_list');
            $table->index(['uploaded_ip'], 'uploaded_ip');
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
            $table->dropIndex('key_extension');
            $table->dropIndex('strategy_md5_sha1');
            $table->dropIndex('user_list');
            $table->dropIndex('uploaded_ip');
        });
    }
}
