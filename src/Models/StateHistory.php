<?php

namespace byteit\LaravelExtendedStateMachines\Models;

use Carbon\Carbon;
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
 * @todo Add accessor/mutator form from/to
 */
class StateHistory extends Model
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
     * @todo Proper Parameter types
     */
    public function allCustomProperties(): array
    {
        return $this->custom_properties ?? [];
    }

    /**
     * @return array
     * @todo Proper Parameter types
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
     * @param $query
     * @param  string  $field
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeForField($query, string $field): void
    {
        $query->where('field', $field);
    }

    /**
     * @param $query
     * @param States $from
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeFrom($query, States $from): void
    {
        $query->where('from', $from->value);
    }

    /**
     * @param $query
     * @param States $from
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeTransitionedFrom($query, States $from): void
    {
        $query->from($from);
    }

    /**
     * @param $query
     * @param States $to
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeTo($query, States $to): void
    {
        $query->where('to', $to->value);
    }

    /**
     * @param $query
     * @param States $to
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeTransitionedTo($query, States $to): void
    {
        $query->to($to);
    }

    /**
     * @param $query
     * @param  States  $from
     * @param  States  $to
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeWithTransition($query, States $from, States $to): void
    {
        $query->from($from)->to($to);
    }

    /**
     * @param $query
     * @param $key
     * @param $operator
     * @param $value
     *
     * @return void
     * @todo Proper Parameter types
     */
    public function scopeWithCustomProperty(
      $query,
      $key,
      $operator,
      $value = null
    ): void {
        $query->where("custom_properties->{$key}", $operator, $value);
    }

    /**
     * @param $query
     * @param $responsible
     *
     * @return mixed
     * @todo Proper Parameter types
     */
    public function scopeWithResponsible($query, $responsible)
    {
        if ($responsible instanceof Model) {
            return $query
              ->where('responsible_id', $responsible->getKey())
              ->where('responsible_type', get_class($responsible));
        }

        return $query->where('responsible_id', $responsible);
    }

}
