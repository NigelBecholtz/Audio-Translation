<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the welcome page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Turn one recording into the same message');
});

it('renders the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('action="'.route('login').'"', false);
});

it('renders the register page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('action="'.route('register').'"', false);
});

it('renders the admin login page', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('action="'.route('admin.login').'"', false);
});
