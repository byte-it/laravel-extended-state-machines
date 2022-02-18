<?php

namespace byteit\LaravelExtendedStateMachines\Tests;

use CreateSalesManagersTable;
use CreatePostponedTransitionsTable;
use CreateSalesOrdersTable;
use CreateTransitionsTable;
use Javoscript\MacroableModels\MacroableModelsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use byteit\LaravelExtendedStateMachines\LaravelExtendedStateMachinesServiceProvider;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/../database/factories');

        $this->withFactories(__DIR__.'/database/factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            MacroableModelsServiceProvider::class,
            LaravelExtendedStateMachinesServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        include_once __DIR__ . '/../database/migrations/create_transitions_table.php.stub';
        include_once __DIR__ . '/../database/migrations/create_postponed_transitions_table.php.stub';

        include_once __DIR__ . '/database/migrations/create_sales_orders_table.php';
        include_once __DIR__ . '/database/migrations/create_sales_managers_table.php';

        (new CreateTransitionsTable())->up();
        (new CreatePostponedTransitionsTable())->up();
        (new CreateSalesOrdersTable())->up();
        (new CreateSalesManagersTable())->up();
    }
}
