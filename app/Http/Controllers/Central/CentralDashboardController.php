<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\Central\CentralPlatformStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\View\View;

final class CentralDashboardController extends Controller
{

    public function index(Request $request): View
    {
        return view('central.welcome');
    }

    public function dashboard(Request $request, CentralPlatformStatsService $stats): View
    {
        $payload = $stats->collect($request->boolean('refresh'));

        return view('central.dashboard', [
            'stats' => $payload,
            'formatBytes' => static function (int $bytes): string {
                return Number::fileSize($bytes, 1);
            },
        ]);
    }
}
