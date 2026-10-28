<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Τα τεστ δεν χρειάζονται build των styles/scripts
        $this->withoutVite();
    }
}
