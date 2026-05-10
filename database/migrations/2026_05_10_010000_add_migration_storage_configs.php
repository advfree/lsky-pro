<?php

use App\Enums\ConfigKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::table('configs')->count() === 0) {
            return;
        }

        $now = Carbon::now();
        $defaults = [
            ConfigKey::StorageBasePath => '',
            ConfigKey::IsEnableMysqlBackup => 0,
            ConfigKey::MysqlBackupIntervalDays => 1,
            ConfigKey::MysqlBackupRetentionCount => 5,
            ConfigKey::MysqlBackupLastRanAt => '',
        ];

        foreach ($defaults as $name => $value) {
            DB::table('configs')->updateOrInsert(
                ['name' => $name],
                [
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down()
    {
        DB::table('configs')->whereIn('name', [
            ConfigKey::StorageBasePath,
            ConfigKey::IsEnableMysqlBackup,
            ConfigKey::MysqlBackupIntervalDays,
            ConfigKey::MysqlBackupRetentionCount,
            ConfigKey::MysqlBackupLastRanAt,
        ])->delete();
    }
};
