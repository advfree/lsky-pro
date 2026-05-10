<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'group_id', 'configs'])
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $configs = json_decode($user->configs ?: '{}', true) ?: [];

                    if (! empty($configs['default_strategy'])) {
                        continue;
                    }

                    $strategyId = DB::table('group_strategy')
                        ->where('group_id', $user->group_id)
                        ->orderBy('strategy_id')
                        ->value('strategy_id');

                    if (! $strategyId) {
                        continue;
                    }

                    $configs['default_strategy'] = (int) $strategyId;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['configs' => json_encode($configs, JSON_UNESCAPED_UNICODE)]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
