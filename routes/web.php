    <?php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Artisan;
    use App\Http\Controllers\User\ProfileController;
    use App\Http\Controllers\User\DashboardController;

    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/', [DashboardController::class, 'chatbot'])->name('user.dashboard.chatbot');
    Route::post('/chatbot/kirim', [DashboardController::class, 'chatbotKirim'])->name('user.dashboard.chatbot.kirim');
