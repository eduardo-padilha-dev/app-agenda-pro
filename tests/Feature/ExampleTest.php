<?php

test('guests are sent to login from the home page', function () {
    $this->get('/')->assertRedirect(route('login'));
});
