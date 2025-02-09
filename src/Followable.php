<?php

declare(strict_types=1);

namespace Akira\Followable;

use Akira\Followable\Events\Followed;
use Akira\Followable\Events\UnFollowed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Str;

final class Followable extends Model
{
    use Dispatchable;

    protected $fillable = [
        'user_id',
        'followable_id',
        'followable_type',
        'accepted_at',
    ];

    protected $dispatchesEvents = [
        'created' => Followed::class,
        'deleted' => UnFollowed::class,
    ];

    /**
     * Get the table associated with the model.
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('followable.followables_table', 'followables');

        parent::__construct($attributes);
    }

    /**
     * Get all of the owning followable models.
     *
     * @return MorphTo<Model, $this>
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that the followable belongs to.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model'),
            config('followable.user_foreign_key', 'user_id')
        );
    }

    /**
     * Get the follower that the followable belongs to.
     */
    public function follower(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Scope a query to only include followable of a given type.
     */
    public function scopeWithType(Builder $query, string $type): Builder
    {
        return $query->where('followable_type', resolve($type)->getMorphClass());
    }

    /**
     * Scope a query to only include followable of a given type.
     */
    public function scopeOf(Builder $query, Model $model): Builder
    {
        return $query->where('followable_type', $model->getMorphClass())
            ->where('followable_id', $model->getKey());
    }

    /**
     * Scope a query to only include followable of a given type.
     */
    public function scopeFollowedBy(Builder $query, Model $follower): Builder
    {
        return $query->where(config('followable.user_foreign_key', 'user_id'), $follower->getKey());
    }

    /**
     * Scope a query to only include followable of a given type.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at');
    }

    /**
     * Scope a query to only include followable of a given type.
     */
    public function scopeNotAccepted(Builder $query): Builder
    {
        return $query->whereNull('accepted_at');
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();
        self::saving(function (Followable $follower): void {
            $userForeignKey = config('followable.user_foreign_key', 'user_id');
            $follower->setAttribute($userForeignKey, $follower->{$userForeignKey} ?: auth()->id());

            if (config('followable.uuids')) {
                $follower->setAttribute($follower->getKeyName(), $follower->{$follower->getKeyName()} ?: (string) Str::orderedUuid());
            }
        });
    }

    /**
     * Get the attributes that should be type-cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {

        return [
            'accepted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
