<?php

test('registration screen cannot be rendered', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});
