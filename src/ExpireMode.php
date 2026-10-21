<?php

declare(strict_types=1);

namespace Matsubo\Redis;

/**
 * Condition for Ranking::expire() (Redis 7 PEXPIRE options).
 */
enum ExpireMode: string
{
    /** Only when the key has no expiry yet. */
    case IfNone = 'NX';

    /** Only when the key already has an expiry. */
    case IfSet = 'XX';

    /** Only when the new expiry is later than the current one. */
    case IfLater = 'GT';

    /** Only when the new expiry is earlier than the current one. */
    case IfEarlier = 'LT';
}
