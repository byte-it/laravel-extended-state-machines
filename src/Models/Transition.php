<?php

namespace byteit\LaravelExtendedStateMachines\Models;

use byteit\LaravelExtendedStateMachines\Contracts\Transition as TransitionContract;
use byteit\LaravelExtendedStateMachines\Database\Factories\TransitionFactory;
use byteit\LaravelExtendedStateMachines\Traits\HasStateMachines;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;

/**
 * Class Transition
 *
 * @package byteit\LaravelExtendedStateMachines\Models
 *
 * @method static TransitionFactory factory($count = null, $state = [])
 */
class Transition extends AbstractTransition implements TransitionContract
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
      'custom_properties' => 'array',
      'changed_attributes' => 'array',
    ];


    /**
     * @return array
     */
    public function changedAttributesNames(): array
    {
        return collect($this->changed_attributes ?? [])->keys()->toArray();
    }

    /**
     * @param  string  $attribute
     *
     * @return mixed
     */
    public function changedAttributeOldValue(string $attribute): mixed
    {
        return data_get($this->changed_attributes, "$attribute.old", null);
    }

    /**
     * @param  string  $attribute
     *
     * @return mixed
     */
    public function changedAttributeNewValue(string $attribute): mixed
    {
        return data_get($this->changed_attributes, "$attribute.new", null);
    }

    protected static function newFactory()
    {
        return new TransitionFactory();
    }

}
