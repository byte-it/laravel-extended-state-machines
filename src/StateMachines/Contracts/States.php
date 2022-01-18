<?php

namespace byteit\LaravelExtendedStateMachines\StateMachines\Contracts;

use BackedEnum;

interface States extends BackedEnum
{
    public function transition(): array;
}
