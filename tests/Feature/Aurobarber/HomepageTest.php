<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_public_homepage_renders_the_aurobarber_product(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('AuroBarber');
    }
}
