<?php

namespace App\Enums;

// True/false is just a single-choice question with two options, so it is not a
// separate type — one less branch in grading and in the builder.
enum QuestionType: string
{
    case Single = 'single';
    case Multi = 'multi';
}
