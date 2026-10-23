<?php
namespace App\Models;

use Illuminate\Support\Facades\Http;

class GeminiChat
{
    public static function kirim(string $pesan, array $riwayat = []): array
    {
        $transkrip = collect($riwayat)
            ->take(-20)
            ->map(fn($r) => ($r['role'] === 'user' ? 'User' : 'Asisten') . ': ' . $r['text'])
            ->implode("\n");

        $input = $transkrip !== '' ? $transkrip . "\nUser: " . $pesan . "\nAsisten:" : $pesan;

        $response = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->timeout(60)
            ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                'model' => 'gemini-3.5-flash-lite',
                'input' => $input,
            ]);

        if ($response->failed()) {
            return [
                'ok' => false,
                'text' => $response->json('error.message') ?? $response->body(),
            ];
        }

        $step = collect($response->json('steps'))->firstWhere('type', 'model_output');

        return [
            'ok' => true,
            'text' => $step['content'][0]['text'] ?? '',
        ];
    }
}
