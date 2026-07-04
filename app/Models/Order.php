<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function items()
        {
            return $this->hasMany(OrderItem::class, 'order_id');
        }

        protected function casts(): array
        {
            return [
                'created_at' => 'datetime:d M, Y',
            ];
        }




}
