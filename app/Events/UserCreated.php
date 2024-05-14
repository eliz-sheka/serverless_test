<?php

namespace App\Events;

class UserCreated
{
    public function __construct(public int $userId)
    {

    }
}
