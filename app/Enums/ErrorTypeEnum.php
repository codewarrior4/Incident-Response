<?php

namespace App\Enums;

enum ErrorTypeEnum: string
{
    case DatabaseError = 'database_error';
    case NetworkError = 'network_error';
    case AuthError = 'auth_error';
    case PerformanceIssue = 'performance_issue';
    case Unknown = 'unknown';
}
