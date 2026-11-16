<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Product;
use Database\Factories\CategoryFactory;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SearchProductsTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/products';

    private Category $electronics;

    private Category $sports;

    protected function setUp(): void
    {
        parent::setUp();

        $this->electronics = CategoryFactory::new()->createOne(['name' => 'Electronics']);
        $this->sports = CategoryFactory::new()->createOne(['name' => 'Sports']);

        $this->product($this->electronics, 'BS-021', 'Bluetooth Speaker', 'Portable waterproof speaker, 10W, 12hr battery', '59.99', 110);
        $this->product($this->electronics, 'SS-022', 'Shower Speaker', 'Bluetooth shower speaker, suction cup mount, IPX7', '15.99', 280);
        $this->product($this->electronics, 'WM-042', 'Wireless Mouse', 'Ergonomic wireless mouse with USB receiver', '29.99', 75);
        $this->product($this->electronics, 'PB-016', 'Portable Charger', '20000mAh power bank, USB-C + dual USB-A output', '37.99', 0);
        $this->product($this->sports, 'YM-015', 'Yoga Mat', 'Premium non-slip yoga mat, 6mm thick', '24.99', 200);
        $this->product($this->sports, 'JR-022', 'Jump Rope', 'Speed jump rope, adjustable length, ball bearing', '9.99', 400);
    }

    #[Test]
    public function it_lists_every_product_alphabetically_when_no_filter_is_given(): void
    {
        $this->assertNames($this->getJson(self::ENDPOINT), [
            'Bluetooth Speaker', 'Jump Rope', 'Portable Charger', 'Shower Speaker', 'Wireless Mouse', 'Yoga Mat',
        ]);
    }

    #[Test]
    public function it_finds_products_by_a_partial_name_ignoring_letter_case(): void
    {
        $this->assertNames(
            $this->getJson(self::ENDPOINT.'?q=SPEAKER&sort=name'),
            ['Bluetooth Speaker', 'Shower Speaker'],
        );
    }

    #[Test]
    public function it_finds_a_product_by_its_sku(): void
    {
        $this->assertNames($this->getJson(self::ENDPOINT.'?q=wm-042'), ['Wireless Mouse']);
    }

    #[Test]
    public function it_finds_products_by_a_word_in_their_description(): void
    {
        $this->assertNames($this->getJson(self::ENDPOINT.'?q=ergonomic'), ['Wireless Mouse']);
    }

    #[Test]
    public function it_tolerates_typos_in_the_search_term(): void
    {
        $response = $this->getJson(self::ENDPOINT.'?q=speakr');

        $response->assertOk();
        $this->assertContains('Bluetooth Speaker', $response->json('data.*.name'));
        $this->assertContains('Shower Speaker', $response->json('data.*.name'));
    }

    #[Test]
    public function it_ranks_an_exact_sku_match_first(): void
    {
        $this->getJson(self::ENDPOINT.'?q=SS-022')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'SS-022');
    }

    #[Test]
    public function it_ranks_name_matches_above_description_only_matches(): void
    {
        $this->getJson(self::ENDPOINT.'?q=bluetooth')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Bluetooth Speaker')
            ->assertJsonPath('data.1.name', 'Shower Speaker');
    }

    #[Test]
    public function it_treats_like_wildcards_in_the_term_as_plain_text(): void
    {
        $this->getJson(self::ENDPOINT.'?q=%25')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_filters_by_category(): void
    {
        $this->assertNames(
            $this->getJson(self::ENDPOINT."?category_id={$this->sports->id}"),
            ['Jump Rope', 'Yoga Mat'],
        );
    }

    #[Test]
    public function it_filters_by_an_inclusive_price_range(): void
    {
        $this->assertNames(
            $this->getJson(self::ENDPOINT.'?min_price=24.99&max_price=37.99'),
            ['Portable Charger', 'Wireless Mouse', 'Yoga Mat'],
        );
    }

    #[Test]
    public function it_filters_out_products_without_stock(): void
    {
        $response = $this->getJson(self::ENDPOINT.'?in_stock=true');

        $response->assertOk()->assertJsonCount(5, 'data');
        $this->assertNotContains('Portable Charger', $response->json('data.*.name'));
    }

    #[Test]
    public function it_sorts_by_price_in_both_directions(): void
    {
        $this->getJson(self::ENDPOINT.'?sort=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Jump Rope')
            ->assertJsonPath('data.5.name', 'Bluetooth Speaker');

        $this->getJson(self::ENDPOINT.'?sort=price_desc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Bluetooth Speaker')
            ->assertJsonPath('data.5.name', 'Jump Rope');
    }

    #[Test]
    public function it_combines_the_search_term_with_filters(): void
    {
        $this->assertNames(
            $this->getJson(self::ENDPOINT."?q=speaker&category_id={$this->electronics->id}&max_price=20"),
            ['Shower Speaker'],
        );
    }

    #[Test]
    public function it_never_returns_deleted_products(): void
    {
        Product::query()->where('sku', 'YM-015')->firstOrFail()->delete();

        $this->assertNames($this->getJson(self::ENDPOINT.'?q=yoga'), []);
    }

    #[Test]
    public function it_keeps_the_search_filters_in_the_pagination_links(): void
    {
        ProductFactory::new()->for($this->sports)->count(20)->sequence(
            fn ($sequence): array => ['name' => "Tennis Ball {$sequence->index}"],
        )->create();

        $this->getJson(self::ENDPOINT.'?q=ball&in_stock=true')
            ->assertOk()
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('links.next', fn (string $next): bool => str_contains($next, 'q=ball') && str_contains($next, 'in_stock=true'));
    }

    #[Test]
    public function it_offers_the_same_search_in_the_admin_listing(): void
    {
        $this->assertNames($this->getJson('/api/v1/admin/products?q=speakr&sort=name'), ['Bluetooth Speaker', 'Shower Speaker']);
    }

    #[Test]
    #[DataProvider('invalidFilters')]
    public function it_rejects_invalid_filters(string $query, string $field): void
    {
        $this->getJson(self::ENDPOINT.'?'.$query)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidFilters(): iterable
    {
        yield 'negative minimum price' => ['min_price=-1', 'min_price'];
        yield 'maximum below minimum' => ['min_price=50&max_price=10', 'max_price'];
        yield 'price written as text' => ['max_price=cheap', 'max_price'];
        yield 'unknown sort' => ['sort=popularity', 'sort'];
        yield 'non numeric category' => ['category_id=abc', 'category_id'];
        yield 'term longer than 100 characters' => ['q='.str_repeat('a', 101), 'q'];
    }

    #[Test]
    public function it_shows_a_single_product(): void
    {
        $product = Product::query()->where('sku', 'WM-042')->firstOrFail();

        $this->getJson(self::ENDPOINT."/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Wireless Mouse')
            ->assertJsonPath('data.category.name', 'Electronics');
    }

    #[Test]
    public function it_does_not_show_a_deleted_product(): void
    {
        $product = Product::query()->where('sku', 'WM-042')->firstOrFail();
        $product->delete();

        $this->getJson(self::ENDPOINT."/{$product->id}")->assertNotFound();
        $this->getJson(self::ENDPOINT.'/abc')->assertNotFound();
    }

    private function product(Category $category, string $sku, string $name, string $description, string $price, int $stock): void
    {
        ProductFactory::new()->for($category)->createOne([
            'sku' => $sku,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    /**
     * @param  TestResponse<JsonResponse>  $response
     * @param  list<string>  $expected
     */
    private function assertNames(TestResponse $response, array $expected): void
    {
        $response->assertOk();
        $this->assertSame($expected, $response->json('data.*.name'));
    }
}
