<?php

namespace byteit\LaravelExtendedStateMachines\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;

/**
 * Class PendingTransition
 * @package byteit\LaravelExtendedStateMachines\Models
 * @property string $id
 * @property string $field
 * @property States $from
 * @property States $to
 * @property Carbon $transition_at
 * @property Carbon $applied_at
 * @property string $custom_properties
 * @property int $model_id
 * @property string $model_type
 * @property Model $model
 * @property int $responsible_id
 * @property string $responsible_type
 * @property Model $responsible
 *
 *
 * @todo Add enum field
 */
class PostponedTransition extends Model
{
    protected $guarded = [];

    protected $casts = [
        'custom_properties' => 'array',
    ];

    protected $dates = [
        'transition_at' => 'date',
        'applied_at' => 'date',
    ];


    public function from(): Attribute{
        return new Attribute(
          get: fn($value) => $this->states::from($value),
          set: fn(States $value) => $value->value,
        );
    }

    public function to(): Attribute{
        return new Attribute(
          get: fn($value) => $this->states::from($value),
          set: fn(States $value) => $value->value,
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function responsible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param $query
     *
     * @return void
     */
    public function scopeNotApplied($query): void
    {
        $query->whereNull('applied_at');
    }

    /**
     * @param $query
     *
     * @return void
     */
    public function scopeOnScheduleOrOverdue($query): void
    {
        $query->where('transition_at', '<=', now());
    }

    /**
     * @param $query
     * @param string $field
     *
     * @return void
     */
    public function scopeForField($query, string $field): void
    {
        $query->where('field', $field);
    }
}
