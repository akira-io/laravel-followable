<?php

declare(strict_types=1);

use Akira\Followable\Events\UnFollowed;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
    $this->user1 = Akira\Followable\Tests\Fixtures\User::factory()->create();
    $this->user2 = Akira\Followable\Tests\Fixtures\User::factory()->create();
    $this->user1->follow($this->user2);
});

test('user can  unfollow users', function () {

    $this->user1->unfollow($this->user2);

    expect($this->user1->isFollowing($this->user2))->toBeFalse()
        ->and($this->user2->isFollowedBy($this->user1))->toBeFalse()
        ->and($this->user1->followings)->toHaveCount(0);
});

test('Followed Event can be dispatched in follow process', function () {

    $this->user1->unfollow($this->user2);

    Event::assertDispatched(UnFollowed::class, function ($event) {

        return $event->unfollow->followable->is($this->user2) && $event->unfollow->follower->is($this->user1);
    });
});
