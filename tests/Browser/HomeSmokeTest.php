<?php

it('loads the public home page without browser errors', function () {
    visit('/')
        ->assertSee('Systemcall')
        ->assertNoJavaScriptErrors();
});
