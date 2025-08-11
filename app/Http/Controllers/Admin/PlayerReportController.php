<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\PlaceBet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlayerReportController extends Controller
{
    

    public function summary(Request $request)
{
    $auth = Auth::user();
    $playerIds = $auth->getAllDescendantPlayers()->pluck('id')->toArray();

    $start = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
    $end = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

    // Step 1: Subquery for latest SETTLED per (player_id, round_id)
    $latestSettledIds = PlaceBet::select(DB::raw('MAX(id) as id'))
        ->whereIn('player_id', $playerIds)
        ->where('wager_status', 'SETTLED')
        ->when($start, fn($q) => $q->where('created_at', '>=', $start.' 00:00:00'))
        ->when($end, fn($q) => $q->where('created_at', '<=', $end.' 23:59:59'))
        ->when($request->filled('member_account'), fn($q) => $q->where('member_account', $request->member_account))
        ->groupBy('player_id', 'round_id')
        ->pluck('id');

    // Step 2: Now only aggregate those latest SETTLED bets
    $placeBets = PlaceBet::whereIn('id', $latestSettledIds);

    $report = $placeBets
        ->selectRaw('
            player_id,
            COUNT(id) as total_spins,
            SUM(CASE
                WHEN currency = \'MMK2\' THEN COALESCE(bet_amount, 0) * 1000
                ELSE COALESCE(bet_amount, 0)
            END) as total_bet,
            SUM(CASE
                WHEN currency = \'MMK2\' THEN COALESCE(prize_amount, 0) * 1000
                ELSE COALESCE(prize_amount, 0)
            END) as total_payout,
            SUM(
                (CASE
                    WHEN currency = \'MMK2\' THEN COALESCE(prize_amount, 0) * 1000
                    ELSE COALESCE(prize_amount, 0)
                END)
                -
                (CASE
                    WHEN currency = \'MMK2\' THEN COALESCE(bet_amount, 0) * 1000
                    ELSE COALESCE(bet_amount, 0)
                END)
            ) as win_lose
        ')
        ->groupBy('player_id')
        ->get();

    // Attach player and agent info
    $report = $report->map(function ($row) {
        $player = User::find($row->player_id);
        $row->player_user_name = $player?->user_name;
        $row->agent_user_name = $player?->agent?->user_name;

        return $row;
    });

    $totals = [
        'total_bet' => $report->sum('total_bet'),
        'total_payout' => $report->sum('total_payout'),
        'win_lose' => $report->sum('win_lose'),
    ];

    return view('admin.report.player_report_index', [
        'report' => $report,
        'totals' => $totals,
    ]);
}


    // public function summary(Request $request)
    // {
    //     $auth = Auth::user();

    //     // subagent belong to parent agent_id and player belong to parent agent_id
    //     $playerIds = $auth->getAllDescendantPlayers()->pluck('id')->toArray();

    //     $start = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
    //     $end = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

    //     $placeBets = PlaceBet::query()
    //         ->whereIn('player_id', $playerIds)
    //         ->where('wager_status', 'SETTLED');

    //     if ($start) {
    //         $placeBets->where('created_at', '>=', $start.' 00:00:00');
    //     }
    //     if ($end) {
    //         $placeBets->where('created_at', '<=', $end.' 23:59:59');
    //     }

    //     if ($request->filled('member_account')) {
    //         $placeBets->where('member_account', $request->member_account);
    //     }

    //     // Group by player and apply currency conversion
    //     $report = $placeBets
    //         ->selectRaw('
    //             player_id,
    //             COUNT(id) as total_spins,
    //             SUM(CASE
    //                 WHEN currency = \'MMK2\' THEN COALESCE(bet_amount, 0) * 1000
    //                 ELSE COALESCE(bet_amount, 0)
    //             END) as total_bet,
    //             SUM(CASE
    //                 WHEN currency = \'MMK2\' THEN COALESCE(prize_amount, 0) * 1000
    //                 ELSE COALESCE(prize_amount, 0)
    //             END) as total_payout,
    //             SUM(
    //                 (CASE
    //                     WHEN currency = \'MMK2\' THEN COALESCE(prize_amount, 0) * 1000
    //                     ELSE COALESCE(prize_amount, 0)
    //                 END)
    //                 -
    //                 (CASE
    //                     WHEN currency = \'MMK2\' THEN COALESCE(bet_amount, 0) * 1000
    //                     ELSE COALESCE(bet_amount, 0)
    //                 END)
    //             ) as win_lose
    //         ')
    //         ->groupBy('player_id')
    //         ->get();

    //     // Attach player and agent info
    //     $report = $report->map(function ($row) {
    //         $player = User::find($row->player_id);
    //         $row->player_user_name = $player?->user_name;
    //         $row->agent_user_name = $player?->agent?->user_name;

    //         return $row;
    //     });

    //     // Totals
    //     $totals = [
    //         'total_bet' => $report->sum('total_bet'),
    //         'total_payout' => $report->sum('total_payout'),
    //         'win_lose' => $report->sum('win_lose'),
    //     ];

    //     return view('admin.report.player_report_index', [
    //         'report' => $report,
    //         'totals' => $totals,
    //     ]);
    // }

    

}
