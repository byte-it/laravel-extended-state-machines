<?php

namespace byteit\LaravelExtendedStateMachines\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;

/**
 * Class StateHistory
 *
 * @package byteit\LaravelExtendedStateMachines\Models
 * @property string $field
 * @property States $from
 * @property States $to
 * @property array $custom_properties
 * @property int $responsible_id
 * @property string $responsible_type
 * @property mixed $responsible
 * @property Carbon $created_at
 * @property array $changed_attributes

 * @todo Add enum field
 */
class Transition extends Model
{

    protected $guarded = [];

    protected $casts = [
      'custom_properties' => 'array',
      'changed_attributes' => 'array',
    ];

    public function from(): Attribute{
        return new Attribute(
          get: fn($value) => $this->states::from($value),
          set: fn(?States $value) => optional($value)->value,
        );
    }

    public function to(): Attribute{
        return new Attribute(
          get: fn($value) => $this->states::from($value),
          set: fn(?States $value) => optional($value)->value,
        );
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getCustomProperty($key): mixed
    {
        return data_get($this->custom_properties, $key, null);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function responsible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array
     */
    public function allCustomProperties(): array
    {
        return $this->custom_properties ?? [];
    }

    /**
     * @return array
     */
    public function changedAttributesNames(): array
    {
        return collect($this->changed_attributes ?? [])->keys()->toArray();
    }

    /**
     * @param $attribute
     *
     * @return mixed
     * @todo Proper Parameter types
     */
    public function changedAttributeOldValue($attribute): mixed
    {
        return data_get($this->changed_attributes, "$attribute.old", null);
    }

    /**
     * @param $attribute
     *
     * @return mixed
     * @todo Proper Parameter types
     */
    public function changedAttributeNewValue($attribute): mixed
    {
        return data_get($this->changed_attributes, "$attribute.new", null);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $field
     *
     * @return void
     */
    public function scopeForField(Builder $query, string $field): void
    {
        $query->where('field', $field);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  States  $from
     *
     * @return void
     */
    public function scopeFrom(Builder $query, States $from): void
    {
        $query->where('from', $from->value);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  States  $from
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeTransitionedFrom(Builder $query, States $from): void
    {
        $query->from($from);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  States  $to
     *
     * @return void
     */
    public function scopeTo(Builder $query, States $to): void
    {
        $query->where('to', $to->value);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  States  $to
     *
     * @return void
     */
    public function scopeTransitionedTo(Builder $query, States $to): void
    {
        $query->to($to);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  States  $from
     * @param  States  $to
     *
     * @return void
     */
    public function scopeWithTransition(Builder $query, States $from, States $to): void
    {
        $query->from($from)->to($to);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param $key
     * @param $operator
     * @param  null  $value
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeWithCustomProperty(
      Builder $query,
      $key,
      $operator,
      $value = null
    ): void {
        $query->where("custom_properties->{$key}", $operator, $value);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $responsible
     *
     * @return mixed
     */
    public function scopeWithResponsible(Builder $query, Model|string|int $responsible): mixed
    {
        if ($responsible instanceof Model) {
            return $query
              ->where('responsible_id', $responsible->getKey())
              ->where('responsible_type', get_class($responsible));
        }

        return $query->where('responsible_id', $responsible);
    }

}
