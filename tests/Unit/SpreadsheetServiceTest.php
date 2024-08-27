<?php

namespace Tests\Unit;

use App\Jobs\ProcessProductImage;
use App\Models\Product;
use App\Services\SpreadsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class SpreadsheetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
    }

    /** @test */
    public function it_processes_spreadsheet_and_creates_products()
    {
        // Mock the importer service
        $importerMock = Mockery::mock('importer');
        $importerMock->shouldReceive('import')
            ->with('path/to/demo.xlsx')
            ->andReturn([
                ['name' => 'lab-1', 'product_code' => 'CODE123', 'quantity' => 10],
                ['name' => 'lab-2', 'product_code' => 'CODE124', 'quantity' => 5],
            ]);
        $this->app->instance('importer', $importerMock);

        // Mock Product creation and make sure correct data is passed
        $productMock = Mockery::mock(Product::class);
        $productMock->shouldReceive('create')
            ->andReturnUsing(function ($data) {
                return new Product($data);
            });
        $this->app->instance(Product::class, $productMock);

        // Create the service and run the method
        $service = new SpreadsheetService;
        $service->processSpreadsheet('path/to/demo.xlsx');

        // Assert that the job was dispatched
        Bus::assertDispatched(ProcessProductImage::class, function ($job) {
            return $job->product->product_code === 'CODE124';
        });
    }

    /** @test */
    public function it_skips_invalid_rows()
    {
        // Mock the importer service
        $importerMock = Mockery::mock('importer');
        $importerMock->shouldReceive('import')
            ->with('path/to/demo.xlsx')
            ->andReturn([
                ['name' => 'lab-1', 'product_code' => 'inValid', 'quantity' => -5], // Invalid row
                ['name' => 'lab-2', 'product_code' => 'valid', 'quantity' => 10], // Valid row
            ]);
        $this->app->instance('importer', $importerMock);

        // Mock Product model
        $productMock = Mockery::mock(Product::class);
        $productMock->shouldReceive('create')
            ->with([
                'product_code' => 'valid',
                'quantity' => 10,
            ])
            ->andReturn(new Product(['product_code' => 'valid', 'quantity' => 10]));

        $this->app->instance(Product::class, $productMock);

        $service = new SpreadsheetService;
        $service->processSpreadsheet('path/to/demo.xlsx');

        // Assert that the job was dispatched for the valid product only
        Bus::assertDispatched(ProcessProductImage::class, function ($job) {
            return $job->product->product_code === 'valid';
        });

        // Assert that the invalid row was skipped
        Bus::assertNotDispatched(ProcessProductImage::class, function ($job) {
            return $job->product->product_code === 'inValid';
        });
    }
}
