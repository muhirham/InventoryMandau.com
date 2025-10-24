<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $fillable = ['category_name','description'];

    // biar $category->name tetap jalan
    public function getNameAttribute()
    {
        return $this->attributes['category_name'] ?? null; // inven_db: categories.category_name
    }
}
