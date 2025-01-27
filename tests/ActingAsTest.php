<?php

use markhuot\craftpest\factories\User;

it('logs in users by factory', function () {
    $userFactory = User::factory();

    $this->actingAs($userFactory);

    expect(\Craft::$app->user->identity->email)
        ->toBe($userFactory->getMadeModels()->first()->email);
});

it('logs in users by email address', function () {
    $user = \markhuot\craftpest\factories\User::factory()->create();

    $this->actingAs($user->email);

    expect(\Craft::$app->user->identity->email)->toBe($user->email);
});

it('logs in user objects', function () {
    $user = \markhuot\craftpest\factories\User::factory()->create();

    $this->actingAs($user);

    expect(\Craft::$app->user->identity->email)->toBe($user->email);
});

it('throws on missing users', function () {
    $this->expectException(\Exception::class);
    $this->actingAs('foobar');
});

it('should not be logged in on subsequent tests', function () {
    expect(\Craft::$app->user->id)->toBeEmpty();
});

it('acts as a user on get requests', function () {
    $user = \markhuot\craftpest\factories\User::factory()->create();

    $this->expectException(\Twig\Error\RuntimeError::class);
    $this->actingAs($user)->get('admin/settings');
});

it('creates admin users', function () {
    $user = \markhuot\craftpest\factories\User::factory()->admin(true)->create();

    $this->actingAs($user)->get('admin/settings')->assertOk();
});
