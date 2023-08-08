<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Server extends Model
{
    use BelongsToTenant;


    protected $fillable = [
        'mail_mailer','sender_name','host', 'port', 'username', 'password', 'encryption',
    ];

    protected $casts = [
        'port' => 'integer',
    ];

}
