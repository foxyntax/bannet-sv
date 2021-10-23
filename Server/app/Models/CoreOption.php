<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoreOption extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @param string
     */
    protected $table = 'core_options';
    

    /**
     * The attributes that are mass assignable.
     *
     * @param array
     */
    protected $fillable = [
        'option',
        'value'
    ];

    /**
     * The options item asociated with app configuration.
     *
     * @param array
     */
    protected $app_options = [];

    /**
     * Get the App Options.
     *
     * @return array
     */
    public function getFullOptionsAttribute()
    {
        $records = DB::table('core_options')->get();
        foreach ($records as $item) {
            $this->app_options[$item->option] = $item->value;
        }

        return $this->app_options;
    }
}
