<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'state', 'city', 'livrare_address_1', 'livrare_address_2',
        'livrare_first_name', 'livrare_last_name', 'phone',
        'payment_method', 'total', 'greutate', 'cif', 'company',
        'nr_colete', 'status'
    ];
}
