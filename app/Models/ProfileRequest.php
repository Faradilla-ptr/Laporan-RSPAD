<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_type',
        'new_name',
        'new_email',
        'new_password',
        'new_nip_nrp',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
