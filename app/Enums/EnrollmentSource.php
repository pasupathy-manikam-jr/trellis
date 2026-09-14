<?php

namespace App\Enums;

enum EnrollmentSource: string
{
    case Purchase = 'purchase';
    case Manual = 'manual';
    case Free = 'free';
}
