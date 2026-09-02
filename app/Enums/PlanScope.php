<?php

namespace App\Enums;
enum PlanScope: string
{
    case Subject = 'subject';
    case MultiSubject = 'multi_subject';
    case Course = 'course';
    case AllSubjects = 'all_subjects';
    case Institute = 'institute';
}
