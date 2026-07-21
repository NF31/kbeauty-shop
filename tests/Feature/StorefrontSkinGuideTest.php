<?php

test('the skin guide page lists the skin type options', function () {
    $this->get('/guide-de-choix')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/skin-guide')
            ->has('skinTypeOptions', 9)
            ->where('skinTypeOptions.0.value', 'seche')
        );
});
