<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    protected $fillable = ['company_name','address','contact_person','phone_number','bank_name','bank_account'];

    public function getNameAttribute()
    {
        return $this->attributes['company_name'] ?? null; // inven_db: suppliers.company_name
    }
}
