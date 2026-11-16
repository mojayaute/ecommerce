<?php

declare(strict_types=1);

namespace App\Domain\Importing;

enum ImportRowStatus: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Rejected = 'rejected';
    case Skipped = 'skipped';
}
