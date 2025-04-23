<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreMovieRequest;

class MovieController extends Controller
{

    public function index()
    {

        $query = Movie::latest();
        if (request('search')) {
            $query->where('judul', 'like', '%' . request('search') . '%')
                ->orWhere('sinopsis', 'like', '%' . request('search') . '%');
        }
        $movies = $query->paginate(6)->withQueryString();
        return view('homepage', compact('movies'));
    }

    public function detail($id)
    {
        $movie = Movie::find($id);
        return view('detail', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('input', compact('categories'));
    }

    public function store(StoreMovieRequest $request)
    {
    // Ambil data yang sudah tervalidasi
    $validated = $request->validated();

    // Simpan file foto jika ada
    if ($request->hasFile('foto_sampul')) {
        $validated['foto_sampul'] = $request->file('foto_sampul')->store('movie_covers', 'public');
    }

    // Simpan data ke database
    Movie::create($validated);

    return redirect()->route('movies.index')->with('success', 'Film berhasil ditambahkan.');
    }

    public function data()
    {
        $movies = Movie::latest()->paginate(10);
        return view('data-movies', compact('movies'));
    }

    public function form_edit($id)
    {
        $movie = Movie::find($id);
        $categories = Category::all();
        return view('form-edit', compact('movie', 'categories'));
    }

    public function update(StoreMovieRequest $request, $id)
    {
        // Ambil data movie yang akan diupdate
        $movie = Movie::findOrFail($id);

        // Simpan foto baru jika ada
        if ($request->hasFile('foto_sampul')) {
            // Hapus foto lama jika ada
            if (File::exists(public_path('images/' . $movie->foto_sampul))) {
                File::delete(public_path('images/' . $movie->foto_sampul));
            }

            // Simpan foto baru
            $fileName = Str::uuid() . '.' . $request->file('foto_sampul')->getClientOriginalExtension();
            $request->file('foto_sampul')->move(public_path('images'), $fileName);

            // Perbarui nama foto di database
            $movie->foto_sampul = $fileName;
        }

        // Update data lainnya
        $movie->update($request->only(['judul', 'sinopsis', 'category_id', 'tahun', 'pemain']));

        return redirect('/movies/data')->with('success', 'Data berhasil diperbarui');
    }

    public function delete($id)
    {
        $movie = Movie::findOrFail($id);

        // Delete the movie's photo if it exists
        if (File::exists(public_path('images/' . $movie->foto_sampul))) {
            File::delete(public_path('images/' . $movie->foto_sampul));
        }

        // Delete the movie record from the database
        $movie->delete();

        return redirect('/movies/data')->with('success', 'Data berhasil dihapus');
    }
}
