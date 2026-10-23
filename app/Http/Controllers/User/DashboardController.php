<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\GeminiChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function chatbot()
    {
        return view('user.dashboard');
    }

    public function chatbotKirim(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['nullable', 'array'],
        ]);

        $hasil = GeminiChat::kirim($data['message'], $data['history'] ?? []);

        return response()->json($hasil, $hasil['ok'] ? 200 : 502);
    }
}
