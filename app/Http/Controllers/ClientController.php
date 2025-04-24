<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClientController extends Controller
{
  // Create
  public function store(Request $request)
  {
      $request->validate([
          'name' => 'required|string|max:250',
          'slug' => 'required|string|max:100|unique:my_client',
          'client_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
      ]);

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

  // Read
  public function show($slug)
  {
      // Cek apakah client ada di cache Redis
      $cachedClient = Redis::get('client:' . $slug);
      
      if ($cachedClient) {
          // Jika ada di Redis, langsung kembalikan response JSON
          return response()->json(json_decode($cachedClient));
      }

      // Jika tidak ada di Redis, ambil dari database
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
      ]);

      $client = MyClient::where('slug', $slug)->first();
      if (!$client) {
          return response()->json(['error' => 'Client not found'], 404);
      }

      $data = $request->all();

      // Jika ada file client_logo baru, hapus logo lama dari S3 dan upload yang baru
      if ($request->hasFile('client_logo')) {
          // Hapus logo lama dari S3
          Storage::disk('s3')->delete($client->client_logo);
          
          // Simpan logo baru
          $logoPath = $request->file('client_logo')->store('client_logos', 's3');
          $data['client_logo'] = $logoPath;
      }

      $client->update($data);

      // Hapus data yang ada di Redis dan simpan yang baru
      Redis::del('client:' . $client->slug);
      Redis::set('client:' . $client->slug, json_encode($client));

      return response()->json($client);
  }

  // Delete (Soft Delete)
  public function destroy($slug)
  {
      $client = MyClient::where('slug', $slug)->first();
      if (!$client) {
          return response()->json(['error' => 'Client not found'], 404);
      }

      // Lakukan soft delete, yang akan mengupdate kolom deleted_at
      $client->delete();

      // Hapus data client dari Redis
      Redis::del('client:' . $client->slug);

      return response()->json(['message' => 'Client deleted']);
  }
}
