<?php

declare(strict_types=1);

use Akira\Followable\Events\Followed;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake();
    $this->user1 = Akira\Followable\Tests\Fixtures\User::factory()->create();
    $this->user2 = Akira\Followable\Tests\Fixtures\User::factory()
        ->isPrivate()
        ->create();
});

test('user can follow users', function (): void {

    $user = Akira\Followable\Tests\Fixtures\User::factory()->create();
    $this->user1->follow($user);

    expect($this->user1->isFollowing($user))->toBeTrue()
        ->and($user->isFollowedBy($this->user1))->toBeTrue();
});

test('Followed Event can be dispatched in follow process', function (): void {

    $this->user1->follow($this->user2);

    Event::assertDispatched(Followed::class, fn ($event): bool => $event->follow->followable->is($this->user2) && $event->follow->follower->is($this->user1));
});

test('Private users must be accept follow requests', function (): void {

    $this->user1->follow($this->user2);

    expect($this->user1->hasRequestedToFollow($this->user2))
        ->toBeTrue()
        ->and($this->user2->isFollowedBy($this->user1))
        ->toBeFalse();

});

it('should accept pending requests', function (): void {

    $this->user1->follow($this->user2);

    $this->user2->acceptFollowRequestFrom($this->user1);

    expect($this->user1->isFollowing($this->user2))
        ->toBeTrue()
        ->and($this->user2->isFollowedBy($this->user1))
        ->toBeTrue();

});

it('should reject pending requests', function (): void {

    $this->user1->follow($this->user2);

    $this->user2->rejectFollowRequestFrom($this->user1);

    expect($this->user1->isFollowing($this->user2))
        ->toBeFalse()
        ->and($this->user2->isFollowedBy($this->user1))
        ->toBeFalse();

});
