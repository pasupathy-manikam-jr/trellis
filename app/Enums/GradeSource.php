<?php

namespace App\Enums;

enum GradeSource: string
{
    case Quiz = 'quiz';
    case Assignment = 'assignment';
    /** A column the instructor keeps by hand — participation, an oral, off-platform work. */
    case Manual = 'manual';
}
