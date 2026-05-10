<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Strategy;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApiController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $user->loadMissing('albums', 'group.strategies');

        $apiTokens = $user->tokens()
            ->latest('id')
            ->get(['id', 'name', 'last_used_at', 'created_at', 'expires_at']);

        $albums = $user->albums()
            ->orderBy('id')
            ->get(['id', 'name', 'intro', 'image_num']);

        $strategies = $user->group
            ? $user->group->strategies()->orderBy('id')->get(['strategies.id', 'strategies.key', 'strategies.name', 'strategies.intro'])
            : collect();

        $currentUserNote = $user->is_adminer ? '超级管理员' : '普通用户';

        return view('common.api', compact('apiTokens', 'albums', 'strategies', 'currentUserNote', 'user'));
    }
}
