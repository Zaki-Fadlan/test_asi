<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'my_client';

    protected $fillable = [
        'name',
        'slug',
        'is_project',
        'self_capture',
        'client_prefix',
        'client_logo',
        'address',
        'phone_number',
        'city',
    ];

    protected $dates = ['deleted_at'];

    // Menyimpan data ke Redis sebagai JSON
    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            \Redis::set($model->slug, json_encode($model)); // Menyimpan JSON ke Redis
        });

        static::deleted(function ($model) {
            \Redis::del($model->slug); // Menghapus data Redis saat dihapus
        });
    }
}
