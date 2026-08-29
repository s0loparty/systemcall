<?php

it('loads the public home page without browser errors', function () {
    visit('/')
        ->assertSee('Видеозвонки, в которые легко пригласить команду и гостей.')
        ->assertNoJavaScriptErrors();
});
