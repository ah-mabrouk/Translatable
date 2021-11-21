<?php

namespace JohnDoe\BlogPackage\Tests;

use Mabrouk\Translatable\TranslatableServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
  public function setUp(): void
  {
    parent::setUp();
    // additional setup
  }

  protected function getPackageProviders($app)
  {
    return [
        TranslatableServiceProvider::class,
    ];
  }

  protected function getEnvironmentSetUp($app)
  {
    //   perform environment setup
  }
}
