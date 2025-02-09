<?php

declare(strict_types=1);

namespace Akira\Followable\Tests\Fixtures;

use Akira\Followable\Concerns\Followable;
use Akira\Followable\Concerns\Follower;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final class User extends Model
{
    use Followable;
    use Follower;
    use HasFactory;

    protected $fillable = ['name'];

    public function needsToApproveFollowRequests(): bool
    {
        return $this->is_private;
    }

    protected function casts(): array
    {

        return [
            'is_private' => 'boolean',
        ];
    }
}
