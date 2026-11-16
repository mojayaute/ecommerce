<?php

declare(strict_types=1);

namespace App\Domain\Importing;

enum IssueSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
