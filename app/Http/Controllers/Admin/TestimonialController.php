<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Admin\Testimonial;

class TestimonialController extends Controller
{
    public function index()
    {
        return view('admin.testimonials.index');
    }

    public function create()
    {
        return view('admin.testimonials.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'position' => 'nullable|string|max:100',
            'message' => 'required|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'status' => 'nullable|in:aktif,nonaktif'
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 200 karakter.',
            'position.max' => 'Jabatan maksimal 100 karakter.',
            'message.required' => 'Pesan/testimoni wajib diisi.',
            'message.max' => 'Pesan/testimoni maksimal 1000 karakter.',
            'photo.image' => 'File yang diunggah harus berupa gambar.',
            'photo.max' => 'Ukuran gambar maksimal adalah 5MB.',
            'status.in' => 'Status testimoni tidak valid.'
        ]);

        try {
            $testimonial = new Testimonial();
            $testimonial->name = $request->name;
            $testimonial->position = $request->position;
            $testimonial->message = $request->message;
            $testimonial->status = $request->status ?? 'aktif';

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');

                $folder = Str::uuid()->toString();
                $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
                $path = public_path('uploads/testimonials/' . $folder);

                if (!file_exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }

                $file->move($path, $filename);
                $testimonial->photo = $folder . '/' . $filename;
            }

            $testimonial->save();
            session()->flash('success_message', 'Testimoni baru berhasil ditambahkan ke dalam sistem.');
            return to_route('admin.testimonials.index');
        } catch (\Exception $e) {
            session()->flash('failed_message', 'Terjadi kesalahan saat menambahkan testimoni: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function edit($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:testimonials,id',
            'name' => 'required|string|max:200',
            'position' => 'nullable|string|max:100',
            'message' => 'required|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'status' => 'nullable|in:aktif,nonaktif'
        ], [
            'id.required' => 'ID testimoni wajib diisi.',
            'id.exists' => 'Testimoni tidak ditemukan dalam sistem.',
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 200 karakter.',
            'position.max' => 'Jabatan maksimal 100 karakter.',
            'message.required' => 'Pesan/testimoni wajib diisi.',
            'message.max' => 'Pesan/testimoni maksimal 1000 karakter.',
            'photo.image' => 'File yang diunggah harus berupa gambar.',
            'photo.max' => 'Ukuran gambar maksimal adalah 5MB.',
            'status.in' => 'Status testimoni tidak valid.'
        ]);

        try {
            $testimonial = Testimonial::findOrFail($request->id);
            $testimonial->name = $request->name;
            $testimonial->position = $request->position;
            $testimonial->message = $request->message;
            $testimonial->status = $request->status ?? 'aktif';

            if ($request->hasFile('photo')) {
                if ($testimonial->photo) {
                    $file = public_path('uploads/testimonials/' . $testimonial->photo);
                    $folder = dirname($file);

                    if (File::isDirectory($folder)) {
                        File::deleteDirectory($folder);
                    }
                }

                $file = $request->file('photo');

                $folder = Str::uuid()->toString();
                $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
                $path = public_path('uploads/testimonials/' . $folder);

                if (!file_exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }

                $file->move($path, $filename);
                $testimonial->photo = $folder . '/' . $filename;
            }
            $testimonial->save();
            session()->flash('success_message', 'Data testimoni berhasil diperbarui.');
            return to_route('admin.testimonials.index');
        } catch (\Exception $e) {
            session()->flash('failed_message', 'Terjadi kesalahan saat memperbarui data testimoni: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:testimonials,id'
        ], [
            'id.required' => 'ID testimoni wajib diisi.',
            'id.exists' => 'Testimoni tidak ditemukan dalam sistem.'
        ]);

        try {
            $testimonial = Testimonial::findOrFail($request->id);
            if ($testimonial->photo) {
                $file = public_path('uploads/testimonials/' . $testimonial->photo);
                $folder = dirname($file);

                if (File::isDirectory($folder)) {
                    File::deleteDirectory($folder);
                }
            }

            $testimonial->delete();
            session()->flash('success_message', 'Testimoni berhasil dihapus dari sistem.');

            return to_route('admin.testimonials.index');
        } catch (\Exception $e) {
            session()->flash('failed_message', 'Terjadi kesalahan saat menghapus testimoni: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function request(Request $request)
    {
        $query = Testimonial::query();

        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('position', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%');
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
