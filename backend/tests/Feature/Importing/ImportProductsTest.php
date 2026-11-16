<?php

declare(strict_types=1);

namespace Tests\Feature\Importing;

use App\Domain\Importing\ImportRowStatus;
use App\Models\Category;
use App\Models\ImportRow;
use App\Models\Product;
use Database\Factories\CategoryFactory;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ImportProductsTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/admin/imports';

    private const HEADER = "name,sku,description,category,price,stock,weight_kg\n";

    #[Test]
    public function it_imports_the_challenge_catalog_and_reports_every_row(): void
    {
        $this->importFixture()
            ->assertCreated()
            ->assertJsonPath('data.original_filename', 'products.csv')
            ->assertJsonPath('data.total_rows', 97)
            ->assertJsonPath('data.created_count', 81)
            ->assertJsonPath('data.updated_count', 0)
            ->assertJsonPath('data.rejected_count', 13)
            ->assertJsonPath('data.skipped_count', 3);

        $this->assertDatabaseCount('products', 81);
        $this->assertDatabaseCount('import_rows', 97);
    }

    #[Test]
    #[DataProvider('rowsThatBreakProductRules')]
    public function it_rejects_rows_that_break_the_product_rules(int $line, string $field): void
    {
        $this->importFixture()->assertCreated();

        $row = $this->row($line);

        $this->assertSame(ImportRowStatus::Rejected, $row->status);
        $this->assertContains($field, array_column($row->issues, 'field'));
        $this->assertContains('error', array_column($row->issues, 'severity'));
        $this->assertNull($row->product_id);
    }

    /** @return iterable<string, array{int, string}> */
    public static function rowsThatBreakProductRules(): iterable
    {
        yield 'price written as text' => [7, 'price'];
        yield 'negative stock' => [16, 'stock'];
        yield 'html markup in the name' => [20, 'name'];
        yield 'empty name' => [25, 'name'];
        yield 'sql injection in the name' => [29, 'name'];
        yield 'whitespace-only name' => [41, 'name'];
        yield 'zero price' => [47, 'price'];
        yield 'missing weight' => [50, 'weight_kg'];
        yield 'missing category' => [52, 'category'];
        yield 'zero weight' => [52, 'weight_kg'];
    }

    #[Test]
    public function it_reports_only_the_first_problem_of_each_field(): void
    {
        $this->importFixture()->assertCreated();

        $this->assertEquals(
            [['field' => 'price', 'severity' => 'error', 'message' => 'The price field must be a number.']],
            $this->row(7)->issues,
        );
    }

    #[Test]
    public function it_rejects_every_version_of_a_sku_that_appears_with_different_data(): void
    {
        $this->importFixture()->assertCreated();

        foreach ([2, 36, 11, 56] as $line) {
            $row = $this->row($line);
            $this->assertSame(ImportRowStatus::Rejected, $row->status, "Line {$line}");
            $this->assertContains('sku', array_column($row->issues, 'field'), "Line {$line}");
        }

        $this->assertDatabaseMissing('products', ['sku' => 'RS-001']);
        $this->assertDatabaseMissing('products', ['sku' => 'BS-021']);
    }

    #[Test]
    public function it_skips_empty_rows_and_exact_copies_of_another_row(): void
    {
        $this->importFixture()->assertCreated();

        foreach ([62, 63, 89] as $line) {
            $this->assertSame(ImportRowStatus::Skipped, $this->row($line)->status, "Line {$line}");
        }
    }

    #[Test]
    public function it_removes_the_currency_symbol_from_a_price_and_records_a_warning(): void
    {
        $this->importFixture()->assertCreated();

        $row = $this->row(4);

        $this->assertSame(ImportRowStatus::Created, $row->status);
        $this->assertEquals([['field' => 'price', 'severity' => 'warning', 'message' => 'Removed the currency symbol from "$29.99".']], $row->issues);
        $this->assertSame('29.99', Product::query()->where('sku', 'WM-042')->value('price'));
    }

    #[Test]
    public function it_keeps_quotes_commas_and_unicode_characters_intact(): void
    {
        $this->importFixture()->assertCreated();

        $this->assertDatabaseHas('products', ['sku' => 'CI-001', 'name' => 'Comma, In Product Name']);
        $this->assertDatabaseHas('products', ['sku' => 'QI-001', 'name' => 'Quote "Inside" Name']);
        $this->assertDatabaseHas('products', ['sku' => 'WB-033', 'description' => 'Stainless steel, 1L — keeps drinks cold for 24hrs™']);
    }

    #[Test]
    public function it_creates_only_the_categories_of_imported_rows_and_reuses_existing_ones(): void
    {
        $electronics = CategoryFactory::new()->createOne(['name' => 'ELECTRONICS']);

        $this->importFixture()->assertCreated();

        $this->assertDatabaseCount('categories', 16);
        $this->assertSame($electronics->id, Product::query()->where('sku', 'WM-042')->value('category_id'));
        $this->assertFalse(Category::query()->whereIn('name', ['Misc', 'Gifts'])->exists());
    }

    #[Test]
    public function it_updates_products_that_are_already_in_the_catalog(): void
    {
        ProductFactory::new()->createOne(['sku' => 'YB-016', 'name' => 'Old Yoga Block', 'price' => '1.00']);

        $this->importFixture()
            ->assertCreated()
            ->assertJsonPath('data.created_count', 80)
            ->assertJsonPath('data.updated_count', 1);

        $this->assertSame(ImportRowStatus::Updated, $this->row(57)->status);
        $this->assertDatabaseHas('products', ['sku' => 'YB-016', 'name' => 'Yoga Block', 'price' => '12.99']);
    }

    #[Test]
    public function it_can_import_the_same_file_twice_without_duplicating_products(): void
    {
        $this->importFixture()->assertCreated();

        $this->importFixture()
            ->assertCreated()
            ->assertJsonPath('data.created_count', 0)
            ->assertJsonPath('data.updated_count', 81)
            ->assertJsonPath('data.rejected_count', 13)
            ->assertJsonPath('data.skipped_count', 3);

        $this->assertDatabaseCount('products', 81);
    }

    #[Test]
    public function it_rejects_a_row_whose_sku_belongs_to_a_deleted_product(): void
    {
        ProductFactory::new()->createOne(['sku' => 'JR-022'])->delete();

        $this->importFixture()->assertCreated();

        $row = $this->row(19);
        $this->assertSame(ImportRowStatus::Rejected, $row->status);
        $this->assertSame('sku', $row->issues[0]['field']);
    }

    #[Test]
    public function it_rejects_a_row_with_the_wrong_number_of_columns(): void
    {
        $this->importCsv(self::HEADER."Tent,CT-001,Dome tent,Outdoors,10.00,5\n")
            ->assertCreated()
            ->assertJsonPath('data.rejected_count', 1);

        $row = $this->row(2);
        $this->assertSame(ImportRowStatus::Rejected, $row->status);
        $this->assertSame('Expected 7 values but found 6.', $row->issues[0]['message']);
    }

    #[Test]
    public function it_accepts_columns_in_any_order_and_letter_case_with_a_byte_order_mark(): void
    {
        $this->importCsv("\u{FEFF}SKU, Price ,Name,Category,Stock,Weight_KG,Description\nct-001,10.00,Tent,Outdoors,5,2.5,Dome tent\n")
            ->assertCreated()
            ->assertJsonPath('data.created_count', 1);

        $this->assertDatabaseHas('products', ['sku' => 'CT-001', 'name' => 'Tent', 'price' => '10.00']);
    }

    #[Test]
    public function it_numbers_rows_by_their_line_in_the_file_even_with_multi_line_values(): void
    {
        $this->importCsv(self::HEADER."Tent,CT-001,\"Dome tent\nfor four\",Outdoors,10.00,5,2.5\nLamp,DL-001,Desk lamp,Home,20.00,3,1\n")
            ->assertCreated();

        $this->assertSame('DL-001', $this->row(4)->raw_data['sku']);
    }

    #[Test]
    #[DataProvider('invalidFiles')]
    public function it_rejects_files_it_cannot_read_as_a_product_csv(UploadedFile $file): void
    {
        $this->post(self::ENDPOINT, ['file' => $file], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('import_batches', 0);
    }

    /** @return iterable<string, array{UploadedFile}> */
    public static function invalidFiles(): iterable
    {
        yield 'not a csv' => [UploadedFile::fake()->create('photo.png', 10, 'image/png')];
        yield 'empty file' => [UploadedFile::fake()->createWithContent('products.csv', '')];
        yield 'missing columns' => [UploadedFile::fake()->createWithContent('products.csv', "name,sku,price\nTent,CT-001,10.00\n")];
        yield 'not utf-8' => [UploadedFile::fake()->createWithContent('products.csv', self::HEADER."Caf\xE9,CF-001,Coffee,Food,5.00,1,1\n")];
    }

    #[Test]
    public function it_requires_a_file(): void
    {
        $this->postJson(self::ENDPOINT)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    #[Test]
    public function it_lists_previous_imports_newest_first(): void
    {
        $first = $this->importFixture()->json('data.id');
        $second = $this->importCsv(self::HEADER)->json('data.id');

        $this->getJson(self::ENDPOINT)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $second)
            ->assertJsonPath('data.1.id', $first);
    }

    #[Test]
    public function it_shows_an_import_summary(): void
    {
        $id = $this->importFixture()->json('data.id');

        $this->getJson(self::ENDPOINT."/{$id}")
            ->assertOk()
            ->assertJsonPath('data.rejected_count', 13);

        $this->getJson(self::ENDPOINT.'/999999')->assertNotFound();
    }

    #[Test]
    public function it_lists_the_rows_of_an_import_filtered_by_status(): void
    {
        $id = $this->importFixture()->json('data.id');

        $this->getJson(self::ENDPOINT."/{$id}/rows?status=rejected")
            ->assertOk()
            ->assertJsonCount(13, 'data')
            ->assertJsonPath('data.0.line_number', 2)
            ->assertJsonPath('data.0.status', 'rejected')
            ->assertJsonPath('data.0.raw_data.sku', 'RS-001')
            ->assertJsonPath('data.0.issues.0.field', 'sku');

        $this->getJson(self::ENDPOINT."/{$id}/rows?status=created")
            ->assertOk()
            ->assertJsonPath('meta.total', 81)
            ->assertJsonPath('links.next', fn (string $next): bool => str_contains($next, 'status=created'));

        $this->getJson(self::ENDPOINT."/{$id}/rows?status=unknown")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    /** @return TestResponse<JsonResponse> */
    private function importFixture(): TestResponse
    {
        $contents = file_get_contents(base_path('tests/Fixtures/products.csv'));
        $this->assertIsString($contents);

        return $this->importCsv($contents);
    }

    /** @return TestResponse<JsonResponse> */
    private function importCsv(string $contents): TestResponse
    {
        return $this->post(
            self::ENDPOINT,
            ['file' => UploadedFile::fake()->createWithContent('products.csv', $contents)],
            ['Accept' => 'application/json'],
        );
    }

    private function row(int $line): ImportRow
    {
        return ImportRow::query()->latest('id')->where('line_number', $line)->firstOrFail();
    }
}
