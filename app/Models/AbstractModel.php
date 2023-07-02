<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
abstract class AbstractModel extends Model
{
    public function jsonUpdate(string $field, string $key, mixed $value, bool $save = true): void
    {
        $current = $this->{$field};
        $current[$key] = $value;
        $this->{$field} = $current;
        if ($save) {
            $this->save();
        }
    }
}
