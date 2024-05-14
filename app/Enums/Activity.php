<?php

namespace App\Enums;

enum Activity: string
{
    case MEETING = 'meeting';
    case WORK = 'work';
    case STUDY = 'study';

    public static function getValues(): array
    {
        return [
            self::MEETING->value,
            self::WORK->value,
            self::STUDY->value,
        ];
    }
}
