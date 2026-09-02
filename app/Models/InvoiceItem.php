<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $table = 'invoice_items';
    protected $fillable = ['invoice_id', 'description', 'quantity', 'unit_price_minor', 'line_total_minor', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
