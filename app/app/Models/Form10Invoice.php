<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form10Invoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'invoice_date',
        'month',
        'contract_number',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'bank_branch',
        'bank_routing',
        'upeo_mobile',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
    ];
}
