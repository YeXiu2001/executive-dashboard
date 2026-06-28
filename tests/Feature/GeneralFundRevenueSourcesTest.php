<?php

use App\Livewire\Pages\GeneralFund\RevenueSourcesCard;
use App\Models\AppLookup;
use App\Models\Fund;
use App\Models\RevenueSource;
use App\Models\User;
use Database\Seeders\AppLookupSeeder;
use Database\Seeders\GeneralFundRevenueSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        AppLookupSeeder::class,
        GeneralFundRevenueSourceSeeder::class,
    ]);
});

test('general fund revenue tables and default hierarchy are seeded', function () {
    expect(Schema::hasTable('app_lookups'))->toBeTrue()
        ->and(Schema::hasTable('funds'))->toBeTrue()
        ->and(Schema::hasTable('revenue_sources'))->toBeTrue();

    $fund = Fund::query()->where('code', 'general_fund')->first();

    expect($fund)->not->toBeNull()
        ->and(AppLookup::query()->where('lookup_type', 'revenue_source_type')->count())->toBe(3)
        ->and(RevenueSource::query()->where('fund_id', $fund->id)->count())->toBe(33);

    $this->assertDatabaseHas('revenue_sources', [
        'fund_id' => $fund->id,
        'code' => 'tax_on_sand_gravel_and_other_quarry_products',
        'name' => 'Tax on Sand, Gravel and Other Quarry Products',
        'source_type' => RevenueSource::TYPE_LINE_ITEM,
        'display_code' => null,
    ]);

    $this->assertDatabaseHas('revenue_sources', [
        'fund_id' => $fund->id,
        'code' => 'real_property_tax',
        'name' => 'Real Property Tax',
        'source_type' => RevenueSource::TYPE_LINE_ITEM,
        'display_code' => null,
    ]);

    $this->assertDatabaseHas('revenue_sources', [
        'fund_id' => $fund->id,
        'code' => 'share_from_national_tax_allocation_nta',
        'name' => 'Share from National Tax Allocation (NTA)',
        'source_type' => RevenueSource::TYPE_CATEGORY,
        'accepts_values' => true,
    ]);
});

test('authenticated users can access the general fund module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('general-fund.index'))
        ->assertOk()
        ->assertSee('General Fund')
        ->assertSee('Revenue Sources')
        ->assertSee('Community Tax');
});

test('users can create revenue source line items', function () {
    $category = RevenueSource::query()->where('code', 'tax_revenue')->firstOrFail();

    Livewire::test(RevenueSourcesCard::class)
        ->set('sourceType', RevenueSource::TYPE_LINE_ITEM)
        ->set('parentId', (string) $category->id)
        ->set('code', 'environmental_tax')
        ->set('displayCode', 'k')
        ->set('name', 'Environmental Tax')
        ->set('sortOrder', 11)
        ->set('acceptsValues', true)
        ->set('isEnabled', true)
        ->call('store')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('revenue_sources', [
        'parent_id' => $category->id,
        'source_type' => RevenueSource::TYPE_LINE_ITEM,
        'code' => 'environmental_tax',
        'name' => 'Environmental Tax',
        'display_code' => null,
    ]);
});

test('users can edit revenue sources', function () {
    $source = RevenueSource::query()->where('code', 'community_tax')->firstOrFail();

    Livewire::test(RevenueSourcesCard::class)
        ->call('editSource', $source->id)
        ->set('name', 'Community Tax Updated')
        ->set('displayCode', 'aa')
        ->call('update')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('revenue_sources', [
        'id' => $source->id,
        'name' => 'Community Tax Updated',
        'display_code' => null,
    ]);
});

test('revenue source search filters the hierarchy table', function () {
    Livewire::test(RevenueSourcesCard::class)
        ->set('search', 'Community Tax')
        ->assertSee('Community Tax')
        ->assertDontSee('Business Tax');
});

test('validation blocks invalid parent levels', function () {
    $category = RevenueSource::query()->where('code', 'tax_revenue')->firstOrFail();

    Livewire::test(RevenueSourcesCard::class)
        ->set('sourceType', RevenueSource::TYPE_CATEGORY)
        ->set('parentId', (string) $category->id)
        ->set('code', 'invalid_nested_category')
        ->set('name', 'Invalid Nested Category')
        ->call('store')
        ->assertHasErrors(['parentId']);
});

test('delete is blocked when a revenue source has children', function () {
    $source = RevenueSource::query()->where('code', 'tax_revenue')->firstOrFail();

    Livewire::test(RevenueSourcesCard::class)
        ->call('destroy', $source->id);

    $this->assertDatabaseHas('revenue_sources', [
        'id' => $source->id,
        'code' => 'tax_revenue',
    ]);
});
