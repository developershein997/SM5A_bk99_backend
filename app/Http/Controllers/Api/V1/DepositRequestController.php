<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use App\Models\DepositRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\DepositLogResource;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PlayerDepositNotification;

class DepositRequestController extends Controller
{
    use HttpResponses;

    public function FinicialDeposit(Request $request)
    {
        $request->validate([
            'agent_payment_type_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min: 1000'],
            'refrence_no' => ['required', 'digits:6'],
        ]);
        $player = Auth::user();
        $image = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = uniqid('deposit').'.'.$image->getClientOriginalExtension();
            $image->move(public_path('assets/img/deposit/'), $filename);
        }

        $depositData = [
            'agent_payment_type_id' => $request->agent_payment_type_id,
            'user_id' => $player->id,
            'agent_id' => $player->agent_id,
            'amount' => $request->amount,
            'refrence_no' => $request->refrence_no,
        ];

        if ($image) {
            $depositData['image'] = $filename;
        }

        $deposit = DepositRequest::create($depositData);

        $agent = User::find($player->agent_id);

        $depositMessage = "Deposit";

        Http::post('https://panda666.pro/send-notification', [
            'message' => "New form submitted: " . $depositMessage ,
            'agent_id'  => $player->agent_id
        ]);


        if ($agent) {
            Log::info('Triggering PlayerDepositNotification for agent:', [
                'agent_id' => $player->agent_id,
                'deposit_id' => $deposit->id,
            ]);
            $agent->notify(new PlayerDepositNotification($deposit));
        }

        return $this->success($deposit, 'Deposit Request Success');

    }

    public function log()
    {
        $deposit = DepositRequest::with('bank')->where('user_id', Auth::id())->get();

        return $this->success(DepositLogResource::collection($deposit));
    }
}
