<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Admin\Setting;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        $rules = [
            'company_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'short_description' => 'nullable|string',
            'company_logo' => 'nullable|image|max:5120',
            'province' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'address_detail' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'map_title' => 'nullable|string|max:255',
            'map_embed_url' => 'nullable|string',
            'linkedin' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'tiktok' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
        ];

        $messages = [
            'company_name.max' => 'Nama perusahaan tidak boleh lebih dari 255 karakter.',
            'tagline.max' => 'Tagline tidak boleh lebih dari 255 karakter.',
            'short_description.string' => 'Deskripsi singkat harus berupa teks.',
            'company_logo.image' => 'Logo perusahaan harus berupa file gambar.',
            'company_logo.max' => 'Logo perusahaan tidak boleh lebih dari 5MB.',
            'province.max' => 'Provinsi tidak boleh lebih dari 255 karakter.',
            'city.max' => 'Kota tidak boleh lebih dari 255 karakter.',
            'district.max' => 'Kecamatan tidak boleh lebih dari 255 karakter.',
            'postal_code.max' => 'Kode pos tidak boleh lebih dari 20 karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email tidak boleh lebih dari 255 karakter.',
            'map_title.max' => 'Judul peta tidak boleh lebih dari 255 karakter.',
            'linkedin.url' => 'URL LinkedIn tidak valid.',
            'facebook.url' => 'URL Facebook tidak valid.',
            'instagram.url' => 'URL Instagram tidak valid.',
            'youtube.url' => 'URL YouTube tidak valid.',
            'tiktok.url' => 'URL TikTok tidak valid.',
            'twitter.url' => 'URL Twitter tidak valid.',
        ];

        $data = $request->validate($rules, $messages);

        $setting = Setting::first() ?? new Setting();

        $uploadPath = 'uploads/settings/logo/';

        if (!file_exists(public_path($uploadPath))) {
            mkdir(public_path($uploadPath), 0755, true);
        }

        if ($request->hasFile('company_logo')) {
            if ($setting->company_logo && file_exists(public_path('uploads/settings/logo/' . $setting->company_logo))) {
                unlink(public_path('uploads/settings/logo/' . $setting->company_logo));
            }

            $file = $request->file('company_logo');
            $filename = Str::uuid()->toString() . "." . $file->getClientOriginalExtension();

            $file->move(public_path($uploadPath), $filename);

            $data['company_logo'] = $filename;
        }

        $setting->fill($data)->save();

        session()->flash('success_message', 'Berhasil memperbarui pengaturan perusahaan.');
        return back();
    }

    public function delete_logo()
    {
        $setting = Setting::first();

        if ($setting && $setting->company_logo) {
            $path = public_path('uploads/settings/logo/' . $setting->company_logo);
            if (file_exists($path)) {
                unlink($path);
            }
            $setting->company_logo = null;
            $setting->save();
        }

        session()->flash('success_message', 'Logo perusahaan berhasil dihapus.');
        return back();
    }
}
