<?php

namespace byteit\LaravelExtendedStateMachines\Models;

use byteit\LaravelExtendedStateMachines\Contracts\Transition as TransitionContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;

/**
 * Class PendingTransition
 * @package byteit\LaravelExtendedStateMachines\Models
 *
 * @property Carbon $transition_at
 * @property Carbon $applied_at
 *
 *
 */
class PostponedTransition extends AbstractTransition implements TransitionContract
{
    protected $guarded = [];

    protected $casts = [
        'custom_properties' => 'array',
    ];

    protected $dates = [
        'transition_at' => 'date',
        'applied_at' => 'date',
    ];





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


}
