<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Text = 'text';
    case Download = 'download';
}
