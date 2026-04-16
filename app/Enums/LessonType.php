<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Scorm = 'scorm';
    case Document = 'document';
}

