<?php

use App\Http\Controllers\Api\Player\GameLogController;
use App\Http\Controllers\Api\Player\TransactionController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
//use App\Http\Controllers\Api\V1\Bank\BankController as BankControllerAlias;
use App\Http\Controllers\Api\V1\Bank\BankController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\Dashboard\AdminLoginController;
use App\Http\Controllers\Api\V1\DepositRequestController;
use App\Http\Controllers\Api\V1\DigitGame\DigitBetController;
use App\Http\Controllers\Api\V1\DigitGame\DigitSlotController;
use App\Http\Controllers\Api\V1\Game\GameController;
use App\Http\Controllers\Api\V1\Game\GSCPlusProviderController;
use App\Http\Controllers\Api\V1\Game\LaunchGameController;
use App\Http\Controllers\Api\V1\Game\ProviderTransactionCallbackController;
use App\Http\Controllers\Api\V1\Game\ShanLaunchGameController;
//use App\Http\Controllers\Api\V1\Game\ShanPlayerHistoryController;
use App\Http\Controllers\Api\V1\gplus\Webhook\DepositController;
use App\Http\Controllers\Api\V1\gplus\Webhook\GameListController;
use App\Http\Controllers\Api\V1\gplus\Webhook\GetBalanceController;
use App\Http\Controllers\Api\V1\gplus\Webhook\ProductListController;
use App\Http\Controllers\Api\V1\gplus\Webhook\PushBetDataController;
use App\Http\Controllers\Api\V1\gplus\Webhook\WithdrawController;
use App\Http\Controllers\Api\V1\Promotion\PromotionController as PromotionControllerAlias;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\Shan\ShanGetBalanceController;
use App\Http\Controllers\Api\V1\Shan\ShanTransactionController;
use App\Http\Controllers\Api\V1\Wallet\WalletController;
use App\Http\Controllers\Api\V1\WithDrawRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TwoDigit\TwoDigitBetController;
use App\Http\Controllers\Api\V2\Shan\ShankomeeGetBalanceController;
use App\Http\Controllers\Api\V1\DirectLaunchGameController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// admin login
//Route::post('/admin/login', [AdminLoginController::class, 'login']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/player-change-password', [AuthController::class, 'playerChangePassword']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('product-list', [ProductListController::class, 'index']);
Route::get('operators/provider-games', [GameListController::class, 'index']);

Route::prefix('v1/api/seamless')->group(function () {
    Route::post('balance', [GetBalanceController::class, 'getBalance']);
    Route::post('withdraw', [WithdrawController::class, 'withdraw']);
    Route::post('deposit', [DepositController::class, 'deposit']);
    Route::post('pushbetdata', [PushBetDataController::class, 'pushBetData']);
});

Route::post('/transactions', [ShanTransactionController::class, 'ShanTransactionCreate'])->middleware('transaction');

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/seamless/launch-game', [LaunchGameController::class, 'launchGame']);
    Route::post('/seamless/direct-launch-game', [DirectLaunchGameController::class, 'launchGame']);

    // main balance
    Route::post('exchange-main-to-game', [TransactionController::class, 'MainToGame']);
    Route::post('exchange-game-to-main', [TransactionController::class, 'GameToMain']);
    Route::get('exchange-transactions-log', [TransactionController::class, 'exchangeTransactionLog']);

    // user api
    Route::get('user', [AuthController::class, 'getUser']);
    Route::get('/banks', [GSCPlusProviderController::class, 'banks']);

    // fanicial api
    Route::get('agentfinicialPaymentType', [BankController::class, 'all']);
    Route::post('depositfinicial', [DepositRequestController::class, 'FinicialDeposit']);
    Route::get('depositlogfinicial', [DepositRequestController::class, 'log']);
    Route::get('paymentTypefinicial', [GSCPlusProviderController::class, 'paymentType']);
    Route::post('withdrawfinicial', [WithDrawRequestController::class, 'FinicalWithdraw']);
    Route::get('withdrawlogfinicial', [WithDrawRequestController::class, 'log']);



    // Player game logs
    Route::get('/player/game-logs', [GameLogController::class, 'index']);
    Route::get('user', [AuthController::class, 'getUser']);
    // 2d route
    Route::post('/twod-bet', [TwoDigitBetController::class, 'store']);
    Route::get('/twod-bet-slips', [TwoDigitBetController::class, 'myBetSlips']);
    // evening-twod-bet-slips
    Route::get('/evening-twod-bet-slips', [TwoDigitBetController::class, 'eveningSessionSlip']);
    Route::get('/two-d-daily-winners', [TwoDigitBetController::class, 'dailyWinners']);
    // shan launch game
    Route::post('shan-launch-game', [ShanLaunchGameController::class, 'launch']);

Route::get('contact', [ContactController::class, 'get']);
});


    Route::get('promotion', [PromotionController::class, 'index']);
    Route::get('winnerText', [BannerController::class, 'winnerText']);
    Route::get('banner_Text', [BannerController::class, 'bannerText']);
    Route::get('popup-ads-banner', [BannerController::class, 'AdsBannerIndex']);
    Route::get('banner', [BannerController::class, 'index']);
    Route::get('videoads', [BannerController::class, 'ApiVideoads']);
    Route::get('toptenwithdraw', [BannerController::class, 'TopTen']);

// games
Route::get('/game_types', [GSCPlusProviderController::class, 'gameTypes']);
Route::get('/providers/{type}', [GSCPlusProviderController::class, 'providers']);
Route::get('/game_lists/{type}/{provider}', [GSCPlusProviderController::class, 'gameLists']);

Route::get('/game_lists/{type}/{productcode}', [GSCPlusProviderController::class, 'NewgameLists']);
Route::get('/hot_game_lists', [GSCPlusProviderController::class, 'hotGameLists']);

// Route::group(['prefix' => 'shanreport', 'middleware' => ['auth:sanctum']], function () {
//     Route::get('player-history', [ShanPlayerHistoryController::class, 'getPlayerHistory']);
// });

Route::group(['prefix' => 'shan'], function () {
    Route::post('getbalance', [ShanGetBalanceController::class, 'getBalance']);
    //Route::post('launch-game', [ShanLaunchGameController::class, 'launch']);
});

// provider shan api
Route::group(['prefix' => 'provider/shan'], function () {
    Route::post('ShanGetBalances', [ShankomeeGetBalanceController::class, 'shangetbalance']);
    Route::post('ShanLaunchGame', [ShankomeeGetBalanceController::class, 'LaunchGame']);
});

Route::prefix('v1')->group(function () {
    Route::prefix('game')->group(function () {
        Route::post('transactions', [ProviderTransactionCallbackController::class, 'handle']);
        // Route::post('transactions', [ProviderTransactionCallbackController::class]);
    });
});

// Route::post('/callback', ProviderTransactionCallbackController::class);.

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//Route::post('/auth/login', [LoginController::class, 'login']);
//Route::post('/auth/logout', [LoginController::class, 'logout']);

Route::middleware(['auth:sanctum'])->group(function () {
    //Route::get('/profile', [ProfileController::class, 'profile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    //Route::get('/banks', [BankControllerAlias::class, 'all']);
    //Route::get('/promotions', [PromotionControllerAlias::class, 'index']);
    //Route::get('/game-list', [GameController::class, 'gameList']);
    //Route::get('/launch-game', [LaunchGameController::class, 'launchGame']);
    //Route::get('/wallet-balance', [WalletController::class, 'balance']);
});

// DigitBet API routes, protected by sanctum middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/digitbet', [DigitBetController::class, 'store']); // Endpoint for placing a bet
    Route::get('/digitbet/history', [DigitBetController::class, 'history']); // Endpoint for getting bet history
    Route::post('/digit-slot/bet', [DigitSlotController::class, 'bet']);
});
