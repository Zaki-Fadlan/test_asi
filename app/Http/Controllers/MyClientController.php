<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MyClientController extends Controller
{
  // Create
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:250',
        'slug' => 'required|string|max:100|unique:my_client',
        'client_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'is_project' => 'required|in:0,1',
        'self_capture' => 'required|in:0,1',
        'client_prefix' => 'required|string|max:4',
    ]);

    // Ambil data dari request
    $data = $request->all();

    // Upload logo ke S3 jika ada
    if ($request->hasFile('client_logo')) {
        $logoPath = $request->file('client_logo')->store('client_logos', 's3');
        $data['client_logo'] = $logoPath;
    }

    // Simpan data ke database
    $client = MyClient::create($data);

    // Simpan data client ke Redis dengan key slug
    Redis::set('client:' . $client->slug, json_encode($client));

    return response()->json($client, 201);
}

// Read (Get all clients)
public function index()
{
    // Ambil semua klien dari database
    $clients = MyClient::all();

    return response()->json($clients);
}

// Read (Get a specific client by slug)
public function show($slug)
{
    // Cek apakah client ada di cache Redis
    $cachedClient = Redis::get('client:' . $slug);
    
    if ($cachedClient) {
        return response()->json(json_decode($cachedClient));
    }

    // Ambil data client dari database jika tidak ada di Redis
    $client = MyClient::where('slug', $slug)->first();
    if (!$client) {
        return response()->json(['error' => 'Client not found'], 404);
    }

    // Simpan data client ke Redis untuk cache
    Redis::set('client:' . $slug, json_encode($client));

    return response()->json($client);
}

// Update
public function update(Request $request, $slug)
{
    $request->validate([
        'name' => 'nullable|string|max:250',
        'slug' => 'nullable|string|max:100|unique:my_client,slug,' . $slug,
        'client_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'is_project' => 'nullable|in:0,1',
        'self_capture' => 'nullable|in:0,1',
        'client_prefix' => 'nullable|string|max:4',
    ]);

    $client = MyClient::where('slug', $slug)->first();
    if (!$client) {
        return response()->json(['error' => 'Client not found'], 404);
    }

    $data = $request->all();

    // Jika ada file logo baru, hapus logo lama dan upload yang baru
    if ($request->hasFile('client_logo')) {
        // Hapus logo lama dari S3
        Storage::disk('s3')->delete($client->client_logo);
        
        // Simpan logo baru
        $logoPath = $request->file('client_logo')->store('client_logos', 's3');
        $data['client_logo'] = $logoPath;
    }

    $client->update($data);

    // Hapus data client dari Redis dan simpan yang baru
    Redis::del('client:' . $client->slug);
    Redis::set('client:' . $client->slug, json_encode($client));

    return response()->json($client);
}

// Soft Delete (Update deleted_at)
public function destroy($slug)
{
    $client = MyClient::where('slug', $slug)->first();
    if (!$client) {
        return response()->json(['error' => 'Client not found'], 404);
    }

    // Soft delete: Update kolom deleted_at
    $client->delete();

    // Hapus data client dari Redis
    Redis::del('client:' . $client->slug);

    return response()->json(['message' => 'Client deleted']);
}
}
