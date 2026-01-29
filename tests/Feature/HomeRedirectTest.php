<?php

test('GET / redirects to login', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});
