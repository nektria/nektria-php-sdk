<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

enum PersistenceType
{
    case HardUpdate;
    case New;
    case None;
    case SoftUpdate;
}
