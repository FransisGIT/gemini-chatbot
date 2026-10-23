<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Admin\Team;

class TeamController extends Controller
{
    public function index()
    {
        return view('admin.teams.index');
    }

    public function create()
    {
        return view('admin.teams.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teams,email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'status' => 'nullable|in:aktif,nonaktif'
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'role.required' => 'Peran/jabatan wajib diisi.',
            'role.max' => 'Peran/jabatan maksimal 100 karakter.',
            'bio.max' => 'Biografi singkat maksimal 1000 karakter.',
            'photo.image' => 'File yang diunggah harus berupa gambar.',
            'photo.max' => 'Ukuran gambar maksimal adalah 5MB.',
            'status.in' => 'Status anggota tim tidak valid.'
        ]);

        try {
            $team = new Team();
            $team->name = $request->name;
            $team->email = $request->email;
            $team->phone = $request->phone;
            $team->role = $request->role;
            $team->bio = $request->bio;
            $team->status = $request->status ?? 'aktif';

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');

                $folder = Str::uuid()->toString();
                $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
                $path = public_path('uploads/teams/' . $folder);

                if (!file_exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }

                $file->move($path, $filename);
                $team->photo = $folder . '/' . $filename;
            }

            $team->save();
            session()->flash('success_message', 'Anggota tim baru berhasil ditambahkan ke dalam sistem.');
            return to_route('admin.teams.index');
        } catch (\Exception $e) {
            session()->flash('failed_message', 'Terjadi kesalahan saat menambahkan anggota tim: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function edit($id)
    {
        $team = Team::findOrFail($id);
        return view('admin.teams.edit', compact('team'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:teams,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teams,email,' . $request->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'status' => 'nullable|in:aktif,nonaktif'
        ], [
            'id.required' => 'ID anggota tim wajib diisi.',
            'id.exists' => 'Anggota tim tidak ditemukan dalam sistem.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'role.required' => 'Peran/jabatan wajib diisi.',
            'role.max' => 'Peran/jabatan maksimal 100 karakter.',
            'bio.max' => 'Biografi singkat maksimal 1000 karakter.',
            'photo.image' => 'File yang diunggah harus berupa gambar.',
            'photo.max' => 'Ukuran gambar maksimal adalah 5MB.',
            'status.in' => 'Status anggota tim tidak valid.'
        ]);

        try {
            $team = Team::findOrFail($request->id);
            $team->name = $request->name;
            $team->email = $request->email;
            $team->phone = $request->phone;
            $team->role = $request->role;
            $team->bio = $request->bio;
            $team->status = $request->status ?? 'aktif';

            if ($request->hasFile('photo')) {
                if ($team->photo) {
                    $file = public_path('uploads/teams/' . $team->photo);
                    $folder = dirname($file);

                    if (File::isDirectory($folder)) {
                        File::deleteDirectory($folder);
                    }
                }

                $file = $request->file('photo');

                $folder = Str::uuid()->toString();
                $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
                $path = public_path('uploads/teams/' . $folder);

                if (!file_exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }

                $file->move($path, $filename);
                $team->photo = $folder . '/' . $filename;
            }
            $team->save();
            session()->flash('success_message', 'Data anggota tim berhasil diperbarui.');
            return to_route('admin.teams.index');
        } catch (\Exception $e) {
            session()->flash('failed_message', 'Terjadi kesalahan saat memperbarui data anggota tim: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:teams,id'
        ], [
            'id.required' => 'ID anggota tim wajib diisi.',
            'id.exists' => 'Anggota tim tidak ditemukan dalam sistem.'
        ]);

        try {
            $team = Team::findOrFail($request->id);

            if ($team->photo) {
                $file = public_path('uploads/teams/' . $team->photo);
                $folder = dirname($file);

                if (File::isDirectory($folder)) {
                    File::deleteDirectory($folder);
                }
            }

            $team->delete();
            session()->flash('success_message', 'Anggota tim berhasil dihapus dari sistem.');

            return to_route('admin.teams.index');
        } catch (\Exception $e) {
            session()->flash('failed_message', 'Terjadi kesalahan saat menghapus anggota tim: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function request(Request $request)
    {
        $query = Team::query();

        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('role', 'like', '%' . $search . '%')
                    ->orWhere('bio', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('status_filter') && $request->status_filter !== 'all') {
            $query->where('status', $request->status_filter);
        }

        $total = $query->count();

        if ($request->has('start') && $request->has('length')) {
            $query->skip($request->input('start'))->take($request->input('length'));
        }

        $data = $query->get();

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data
        ]);
    }
}
