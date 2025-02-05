<?php

namespace App\Models;

use App\Traits\template\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Sway\Traits\InvalidatableToken;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Ngo extends Authenticatable
{
    use HasFactory, Notifiable, InvalidatableToken, Auditable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
    public function email()
    {
        return $this->belongsTo(Email::class, 'email_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function ngoTrans()
    {
        return $this->hasMany(NgoTran::class);
    }

    public function ngoType()
    {
        return $this->belongsTo(NgoType::class, 'ngo_type_id');
    }

    public function ngoStatus()
    {
        return $this->hasOne(NgoStatus::class, 'ngo_id', 'id');
    }


    public function agreement()
    {
        return $this->hasOne(Agreement::class, 'ngo_id');
    }
}
