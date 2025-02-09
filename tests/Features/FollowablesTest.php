<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake();
    $this->user1 = Akira\Followable\Tests\Fixtures\User::factory()->create();
    $this->user2 = Akira\Followable\Tests\Fixtures\User::factory()->create();
    $this->user1->follow($this->user2);
});

test('Get users followings', function (): void {

    expect($this->user1->followings)->toHaveCount(1);
});

test('Get users followables', function (): void {

    $this->user2->follow($this->user1);

    expect($this->user1->followables)->toHaveCount(1);
});
