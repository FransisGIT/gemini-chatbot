<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    public const JUDUL_DEFAULT = 'Percakapan Baru';

    protected $fillable = ['user_id', 'title'];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public static function daftar(?int $userId = 1)
    {
        return static::where('user_id', $userId ?? 1)
            ->orderByDesc('updated_at')
            ->get(['id', 'title']);
    }

    public static function buat(?int $userId = 1): self
    {
        return static::create(['user_id' => $userId ?? 1, 'title' => self::JUDUL_DEFAULT]);
    }

    public static function milik(?int $userId, int $id): self
    {
        return static::where('user_id', $userId ?? 1)->findOrFail($id);
    }

    public function pesan()
    {
        return $this->messages()->orderBy('id')->get(['role', 'content']);
    }

    public function kirim(string $pesan): array
    {
        $riwayat = $this->messages()
            ->orderBy('id')
            ->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'text' => $m->content])
            ->all();

        $hasil = GeminiChat::kirim($pesan, $riwayat);

        if (!$hasil['ok']) {
            return $hasil;
        }

        $this->messages()->createMany([
            ['role' => 'user', 'content' => $pesan],
            ['role' => 'model', 'content' => $hasil['text']],
        ]);

        if ($this->title === self::JUDUL_DEFAULT) {
            $this->title = Str::limit($pesan, 40);
        }

        $this->touch();
        $this->save();

        $hasil['title'] = $this->title;

        return $hasil;
    }
}
