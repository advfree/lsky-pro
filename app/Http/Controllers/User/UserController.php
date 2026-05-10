<?php

namespace App\Http\Controllers\User;

use App\Enums\UserConfigKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserSettingRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function dashboard(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $configs = $user->group->configs;
        $strategies = $user->group->strategies()->get();
        return view('user.dashboard', compact('strategies', 'configs', 'user'));
    }

    public function settings(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $apiTokens = $user->tokens()
            ->latest('id')
            ->get(['id', 'name', 'last_used_at', 'created_at', 'expires_at']);

        return view('user.settings', compact('apiTokens'));
    }

    public function update(UserSettingRequest $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $user->name = $request->validated('name');
        $user->url = $request->validated('url') ?: '';
        $user->configs = $user->configs->merge(collect($request->validated('configs'))->transform(function ($value) {
            return (int)$value;
        }));
        if ($password = $request->validated('password')) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ]);

            event(new PasswordReset($user));
        }
        $user->save();
        return $this->success('保存成功');
    }

    public function setStrategy(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $strategy = $user->group->strategies()->find($request->id)) {
            return $this->fail('没有找到该策略');
        }
        $user->update(['configs->'.UserConfigKey::DefaultStrategy => $strategy->id]);
        return $this->success('设置成功');
    }

    public function createApiToken(Request $request): Response
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $tokenName = trim((string) $request->input('name', 'Third-party client'));
            $tokenName = $tokenName !== '' ? $tokenName : 'Third-party client';
            $expiresAt = $this->resolveTokenExpiry($request);

            $newToken = $user->createToken($tokenName);
            $newToken->accessToken->forceFill([
                'expires_at' => $expiresAt,
            ])->save();
            $plainTextToken = $newToken->plainTextToken;

            return $this->success('API Token 生成成功', [
                'token' => $plainTextToken,
                'name' => $tokenName,
                'api_url' => url('/api/v1'),
                'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function clearApiTokens(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $user->tokens()->delete();

        return $this->success('API Token 已清空');
    }

    private function resolveTokenExpiry(Request $request): ?Carbon
    {
        $expiresType = (string) $request->input('expires_type', 'never');

        $expiresAt = match ($expiresType) {
            '1_month' => now()->addMonth(),
            '6_months' => now()->addMonths(6),
            '1_year' => now()->addYear(),
            '3_years' => now()->addYears(3),
            '5_years' => now()->addYears(5),
            'custom_date' => $this->parseCustomExpiryDate((string) $request->input('expires_at_date', '')),
            'never' => null,
            default => throw new \RuntimeException('请选择正确的 Token 有效期'),
        };

        if ($expiresAt && $expiresAt->isPast()) {
            throw new \RuntimeException('Token 有效期必须晚于当前时间');
        }

        return $expiresAt;
    }

    private function parseCustomExpiryDate(string $date): Carbon
    {
        if ($date === '') {
            throw new \RuntimeException('请选择具体到期日期');
        }

        try {
            return Carbon::parse($date)->endOfDay();
        } catch (\Throwable) {
            throw new \RuntimeException('具体到期日期格式不正确');
        }
    }
}
