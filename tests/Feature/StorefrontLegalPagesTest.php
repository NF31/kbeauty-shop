<?php

test('the mentions legales page renders', function () {
    $this->get('/mentions-legales')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/legal/mentions-legales'));
});

test('the cgv page renders', function () {
    $this->get('/cgv')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/legal/cgv'));
});

test('the confidentialite page renders', function () {
    $this->get('/confidentialite')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/legal/confidentialite'));
});

test('the livraison page renders', function () {
    $this->get('/livraison')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/legal/livraison'));
});

test('the retours page renders', function () {
    $this->get('/retours')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/legal/retours'));
});
