<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\User;
use App\Enums\UserType;
use App\Models\TransferLog;
use Illuminate\Http\Request;
use App\Enums\TransactionName;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\TransferLogRequest;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class MasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private const Senior_ROLE = 2;

    public function index()
    {
        abort_if(
            Gate::denies('owner_access'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        // $users = User::with(['roles', 'children.children.poneWinePlayer', 'children.children.results', 'children.children.betNResults'])
        //     ->whereHas('roles', function ($query) {
        //         $query->where('role_id', self::Senior_ROLE );
        //     })
        //     ->where('agent_id', auth()->id())
        //     ->orderBy('id', 'desc')
        //     ->get();

        $users = User::with(['roles', 'children.children.poneWinePlayer'])->whereHas('roles', fn ($q) => $q->where('role_id', self::Senior_ROLE ))
            ->select('id', 'name', 'user_name', 'phone', 'status')
            ->where('agent_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // $reportData = DB::table('users as m')
        //     ->join('users as a', 'a.agent_id', '=', 'm.id')          // agent
        //     ->join('users as p', 'p.agent_id', '=', 'a.id')          // player
        //     ->join('reports', 'reports.member_name', '=', 'p.user_name')
        //     ->groupBy('m.id')
        //     ->selectRaw('m.id as master_id,SUM(reports.bet_amount) as total_bet_amount,SUM(reports.payout_amount) as total_payout_amount')
        //     ->get()
        //     ->keyBy('master_id');

        // dd($reportData);
        // $users = $masters->map(function ($master) use ($reportData) {
        //     $report = $reportData->get($master->id);
        //     $poneWineTotalAmt = $master->children->flatMap->children->flatMap->poneWinePlayer->sum('win_lose_amt');

        //     return (object) [
        //         'id' => $master->id,
        //         'name' => $master->name,
        //         'user_name' => $master->user_name,
        //         'phone' => $master->phone,
        //         'balanceFloat' => $master->balanceFloat,
        //         'status' => $master->status,
        //         'win_lose' => (($report->total_payout_amount ?? 0) - ($report->total_bet_amount ?? 0)) + $poneWineTotalAmt,
        //     ];
        // });

        return view('admin.master.index', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MasterRequest $request)
    {
        // dd($request->all());
        abort_if(
            Gate::denies('owner_access'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $admin = Auth::user();

        $user_name = session()->get('user_name');
        // dd($user_name);

        $inputs = $request->validated();

        $userPrepare = array_merge(
            $inputs,
            [
                'user_name' => $request->user_name,
                'password' => Hash::make($inputs['password']),
                'agent_id' => Auth()->user()->id,
                'type' => UserType::Senior,
            ]
        );

        // dd($userPrepare);

        if (isset($inputs['amount']) && $inputs['amount'] > $admin->balanceFloat) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient balance for transfer.',
            ]);
        }

        $user = User::create($userPrepare);
        $user->roles()->sync(self::Senior_ROLE);

        if (isset($inputs['amount'])) {
            app(WalletService::class)->transfer(
                $admin,
                $user,
                $inputs['amount'],
                TransactionName::CreditTransfer,
                [
                    'old_balance' => $user->balanceFloat,
                    'new_balance' => $user->balanceFloat + $request->amount,
                ]
            );
        }
        session()->forget('user_name');

        return redirect()->route('admin.senior.index')
            ->with('successMessage', 'Senior created successfully')
            ->with('password', $request->password)
            ->with('username', $user->user_name)
            ->with('amount', $request->amount);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(
            Gate::denies('owner_access'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $user_name = $this->generateRandomString();

        session()->put('user_name', $user_name);

        return view('admin.master.create', compact('user_name', 'user_name'));
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'BK'.$randomNumber;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort_if(
            Gate::denies('owner_access'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $master = User::find($id);

        return view('admin.master.show', compact('master'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        abort_if(
            Gate::denies('owner_access'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $senior = User::find($id);

        return view('admin.master.edit', compact('senior'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function getCashIn(string $id)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $senior = User::find($id);

        return view('admin.master.cash_in', compact('senior'));
    }

    public function getCashOut(string $id)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $senior = User::findOrFail($id);

        return view('admin.master.cash_out', compact('senior'));
    }

    public function makeCashIn(TransferLogRequest $request, $id)
    {

        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent($request->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {

            $inputs = $request->validated();
            $master = User::findOrFail($id);
            $admin = Auth::user();
            $cashIn = $inputs['amount'];
            if ($cashIn > $admin->balanceFloat) {
                throw new \Exception('You do not have enough balance to transfer!');
            }

            // Transfer money
            app(WalletService::class)->transfer(
                $admin,
                $master,
                $request->validated('amount'),
                TransactionName::CreditTransfer,
                [
                    'note' => $request->note,
                    'old_balance' => $master->balanceFloat,
                    'new_balance' => $master->balanceFloat + $request->amount,
                ]
            );

                    TransferLog::create([
                'from_user_id' => $admin->id,
                'to_user_id' =>  $master->id,
                'amount' => $request->amount,
                'type' => 'top_up',
                'description' => $request->note ?? 'TopUp from owner to '.$master->user_name,
                'meta' => [
                    'transaction_type' => TransactionName::CreditTransfer->value,
                    'old_balance' => $master->balanceFloat,
                    'new_balance' => $master->balanceFloat + $request->amount,
                ],
            ]);



            return redirect()->back()->with('success', 'Money fill request submitted successfully!');
        } catch (Exception $e) {

            session()->flash('error', $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function makeCashOut(TransferLogRequest $request, string $id)
    {

        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent($request->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            $inputs = $request->validated();

            $master = User::findOrFail($id);
            $admin = Auth::user();
            $cashOut = $inputs['amount'];

            if ($cashOut > $master->balanceFloat) {

                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            // Transfer money
            app(WalletService::class)->transfer(
                $master,
                $admin,
                $request->validated('amount'),
                TransactionName::DebitTransfer,
                [
                    'note' => $request->note,
                    'old_balance' => $master->balanceFloat,
                    'new_balance' => $master->balanceFloat - $request->amount,
                ]
            );

                     TransferLog::create([
                'from_user_id' => $admin->id,
                'to_user_id' => $master->id,
                'amount' => $request->amount,
                'type' => 'withdraw',
                'description' => $request->note ?? 'Withdraw from '.$admin->user_name.' to ' . $master->user_name,
                'meta' => [
                    'transaction_type' => TransactionName::DebitTransfer->value,
                    'old_balance' => $master->balanceFloat,
                    'new_balance' => $master->balanceFloat - $request->amount,
                ],
            ]);

            return redirect()->back()->with('success', 'Money fill request submitted successfully!');
        } catch (Exception $e) {

            session()->flash('error', $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Money fill request submitted successfully!');
    }

    public function getTransferDetail($id)
    {
        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $transfer_detail = TransferLog::where('from_user_id', $id)
            ->orWhere('to_user_id', $id)
            ->get();

        return view('admin.master.transfer_detail', compact('transfer_detail'));
    }

    public function banmaster($id)
    {
        abort_if(
            ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user = User::find($id);
        $user->update(['status' => $user->status == 1 ? 0 : 1]);

        return redirect()->back()->with(
            'success',
            'User '.($user->status == 1 ? 'activate' : 'inactive').' successfully'
        );
    }

    public function update(Request $request, string $id)
    {
        abort_if(
            Gate::denies('owner_access') || ! $this->ifChildOfParent($request->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden | You cannot access this page because you do not have permission'
        );

        $user = User::findOrFail($id);

        $request->validate([
            'user_name' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|numeric|digits_between:10,15|unique:users,phone,'.$id,
        ]);

        $user->update([
            'user_name' => $request->user_name ?? $user->user_name,
            'name' => $request->name,
            'phone' => $request->phone,
            'agent_logo' => $user->agent_logo, // Updated logo
            'site_link' => $request->site_link,
        ]);

        return redirect()->back()
            ->with('success', 'Senior updated successfully!');
    }

    public function getChangePassword($id)
    {
        $senior = User::find($id);

        return view('admin.master.change_password', compact('senior'));
    }

    public function makeChangePassword($id, Request $request)
    {
        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $master = User::find($id);
        $master->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()
            ->with('success', 'Senior Change Password successfully')
            ->with('password', $request->password)
            ->with('username', $master->user_name);
    }

    // KS Upgrade RPIndex
    public function masterReportIndex($id)
    {
        $master = User::with([
            'roles',
            'children.children.poneWinePlayer',
        ])->find($id);

        $poneWineTotalAmt = $master->children->flatMap->children->flatMap->poneWinePlayer->sum('win_lose_amt');

        $reportData = DB::table('users as m')
            ->join('users as a', 'a.agent_id', '=', 'm.id')          // agent
            ->join('users as p', 'p.agent_id', '=', 'a.id')          // player
            ->join('reports', 'reports.member_name', '=', 'p.user_name')
            ->groupBy('m.id')
            ->selectRaw('
    m.id as master_id,
    SUM(reports.bet_amount) as total_bet_amount,
    SUM(reports.payout_amount) as total_payout_amount
')
            ->get()
            ->keyBy('master_id');

        $report = $reportData->get($master->id);
        $report = (object) [
            'win_lose' => (($report->total_payout_amount ?? 0) - ($report->total_bet_amount ?? 0)),
            'total_win_lose_pone_wine' => $poneWineTotalAmt ?? 0,
        ];

        return view('admin.master.report_index', compact('report'));
    }
}
