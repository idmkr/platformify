## Test Helper for Cartalyst Platform Framework

## Installation

```sh
composer require idmkr/platformify --dev
```

```php
idmkr\platformify\Providers\CodeceptionServiceProvider::class,
```

```use (tests\_support\Helper\Functional.php)

<?php
namespace Helper;

use Codeception\TestInterface;
use idmkr\platformify\Traits\Platformify;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Functional extends \Codeception\Module
{
    use Platformify;

    public $app;

    // HOOK: before each suite
    public function _before(TestInterface $test) {
        $this->boot();
        $this->artisan('app:install', ['--seed-only' => true, '--env' => 'testing']);
    }

}
