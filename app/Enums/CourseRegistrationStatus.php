<?php

namespace App\Enums;

enum CourseRegistrationStatus: int
{
    case Pending = 0;
    case Confirmed = 1;
    case Cancelled = 2;
    case Expired = 3;
}
