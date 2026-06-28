<?php

use App\Livewire\Pages\DataEntry\GeneralFundDataEntry;
use App\Livewire\Pages\GeneralFund\RevenueSourcesCard;
use App\Models\AppLookup;
use App\Models\Fund;
use App\Models\RevenueForecastValue;
use App\Models\RevenueSource;
use App\Models\User;
use Database\Seeders\AppLookupSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GeneralFundRevenueSourceSeeder;
use Database\Seeders\RegionIxHistoricalGeneralFundRevenueSeeder;
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

test('database seeder populates region ix historical values from 2015 to 2025', function () {
    $this->seed(DatabaseSeeder::class);

    $fund = Fund::query()->where('code', 'general_fund')->firstOrFail();
    $eligibleSources = RevenueSource::query()
        ->where('fund_id', $fund->id)
        ->where('is_enabled', true)
        ->where('accepts_values', true)
        ->get();

    expect($eligibleSources)->toHaveCount(28)
        ->and(RevenueForecastValue::query()
            ->where('fund_id', $fund->id)
            ->where('value_type', RevenueForecastValue::TYPE_HISTORICAL)
            ->count())->toBe($eligibleSources->count() * 11);

    foreach ($eligibleSources as $source) {
        $years = RevenueForecastValue::query()
            ->where('fund_id', $fund->id)
            ->where('revenue_source_id', $source->id)
            ->where('value_type', RevenueForecastValue::TYPE_HISTORICAL)
            ->pluck('year')
            ->sort()
            ->values()
            ->all();

        expect($years)->toBe(range(2015, 2025));
    }

    $this->assertDatabaseHas('revenue_forecast_values', [
        'fund_id' => $fund->id,
        'revenue_source_id' => RevenueSource::query()->where('code', 'share_from_national_tax_allocation_nta')->value('id'),
        'year' => 2025,
        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
    ]);
});

test('region ix historical seeder preserves existing values', function () {
    $fund = Fund::query()->where('code', 'general_fund')->firstOrFail();
    $source = RevenueSource::query()->where('code', 'business_tax')->firstOrFail();

    RevenueForecastValue::query()->create([
        'fund_id' => $fund->id,
        'revenue_source_id' => $source->id,
        'year' => 2020,
        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
        'amount' => '999999.99',
    ]);

    $this->seed(RegionIxHistoricalGeneralFundRevenueSeeder::class);

    $existingValue = RevenueForecastValue::query()
        ->where('fund_id', $fund->id)
        ->where('revenue_source_id', $source->id)
        ->where('year', 2020)
        ->where('value_type', RevenueForecastValue::TYPE_HISTORICAL)
        ->firstOrFail();

    expect($existingValue->amount)->toBe('999999.99')
        ->and(RevenueForecastValue::query()
            ->where('fund_id', $fund->id)
            ->where('value_type', RevenueForecastValue::TYPE_HISTORICAL)
            ->count())->toBe(28 * 11);
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

test('authenticated users can access the general fund data entry module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('data-entry.general-fund'))
        ->assertOk()
        ->assertSee('General Fund Data Entry')
        ->assertSee('Revenue Sources')
        ->assertSee('Tax Revenue');
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

test('data entry source picker shows hierarchy context and blocks non value sources from saving', function () {
    $mainSource = RevenueSource::query()->where('code', 'local_internal_sources')->firstOrFail();
    $communityTax = RevenueSource::query()->where('code', 'community_tax')->firstOrFail();

    Livewire::test(GeneralFundDataEntry::class)
        ->assertSee('Local (Internal) Sources')
        ->assertSee('Tax Revenue')
        ->call('toggleExpanded', RevenueSource::query()->where('code', 'tax_revenue')->value('id'))
        ->assertSee('Community Tax')
        ->set('selectedSourceIds', [(string) $mainSource->id])
        ->set('years', [2024])
        ->call('save')
        ->assertHasErrors(['selectedSourceIds'])
        ->call('toggleSourceSelection', $communityTax->id)
        ->set("amounts.{$communityTax->id}.2024", '100.50')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('revenue_forecast_values', [
        'revenue_source_id' => $communityTax->id,
        'year' => 2024,
        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
        'amount' => '100.50',
    ]);
});

test('clicking a line item row selects that source', function () {
    $communityTax = RevenueSource::query()->where('code', 'community_tax')->firstOrFail();

    $component = Livewire::test(GeneralFundDataEntry::class)
        ->call('toggleSourceSelection', $communityTax->id);

    expect($component->get('selectedSourceIds'))->toBe([(string) $communityTax->id]);
});

test('selecting a category selects all eligible child sources', function () {
    $category = RevenueSource::query()->where('code', 'tax_revenue')->firstOrFail();
    $expectedIds = RevenueSource::query()
        ->where('parent_id', $category->id)
        ->where('is_enabled', true)
        ->where('accepts_values', true)
        ->pluck('id')
        ->map(fn ($sourceId) => (string) $sourceId)
        ->sort()
        ->values()
        ->all();

    $component = Livewire::test(GeneralFundDataEntry::class)
        ->call('toggleSourceSelection', $category->id);

    expect(collect($component->get('selectedSourceIds'))->sort()->values()->all())
        ->toBe($expectedIds);
});

test('selecting a parent source selects all eligible descendants but not the non value parent', function () {
    $parent = RevenueSource::query()->where('code', 'local_internal_sources')->firstOrFail();
    $categoryIds = RevenueSource::query()
        ->where('parent_id', $parent->id)
        ->pluck('id');
    $expectedIds = RevenueSource::query()
        ->whereIn('parent_id', $categoryIds)
        ->where('is_enabled', true)
        ->where('accepts_values', true)
        ->pluck('id')
        ->map(fn ($sourceId) => (string) $sourceId)
        ->sort()
        ->values()
        ->all();

    $component = Livewire::test(GeneralFundDataEntry::class)
        ->call('toggleSourceSelection', $parent->id);

    $selectedIds = collect($component->get('selectedSourceIds'))->sort()->values()->all();

    expect($selectedIds)
        ->toBe($expectedIds)
        ->and($selectedIds)->not->toContain((string) $parent->id);
});

test('selecting an already fully selected category deselects its eligible children', function () {
    $category = RevenueSource::query()->where('code', 'tax_revenue')->firstOrFail();

    $component = Livewire::test(GeneralFundDataEntry::class)
        ->call('toggleSourceSelection', $category->id)
        ->call('toggleSourceSelection', $category->id);

    expect($component->get('selectedSourceIds'))->toBe([]);
});

test('users can add years and save multiple general fund source values', function () {
    $communityTax = RevenueSource::query()->where('code', 'community_tax')->firstOrFail();
    $businessTax = RevenueSource::query()->where('code', 'business_tax')->firstOrFail();

    Livewire::test(GeneralFundDataEntry::class)
        ->set('selectedSourceIds', [(string) $communityTax->id, (string) $businessTax->id])
        ->set('newYear', '2024')
        ->call('addYear')
        ->set('newYear', '2025')
        ->call('addYear')
        ->set("amounts.{$communityTax->id}.2024", '123456.78')
        ->set("amounts.{$communityTax->id}.2025", '223456.78')
        ->set("amounts.{$businessTax->id}.2024", '323456.78')
        ->set("amounts.{$businessTax->id}.2025", '423456.78')
        ->call('save')
        ->assertHasNoErrors();

    expect(RevenueForecastValue::query()->count())->toBe(4);

    $this->assertDatabaseHas('revenue_forecast_values', [
        'revenue_source_id' => $businessTax->id,
        'year' => 2025,
        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
        'amount' => '423456.78',
    ]);
});

test('data entry saves historical values only', function () {
    $source = RevenueSource::query()->where('code', 'community_tax')->firstOrFail();

    Livewire::test(GeneralFundDataEntry::class)
        ->call('toggleSourceSelection', $source->id)
        ->set('years', [2024])
        ->set("amounts.{$source->id}.2024", '1000.00')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('revenue_forecast_values', [
        'revenue_source_id' => $source->id,
        'year' => 2024,
        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
        'amount' => '1000.00',
    ]);
    $this->assertDatabaseMissing('revenue_forecast_values', [
        'revenue_source_id' => $source->id,
        'year' => 2024,
        'value_type' => RevenueForecastValue::TYPE_FORECAST,
    ]);
});

test('data entry validation blocks invalid amounts duplicate years and non selectable sources', function () {
    $source = RevenueSource::query()->where('code', 'community_tax')->firstOrFail();
    $nonSelectableSource = RevenueSource::query()->where('code', 'local_internal_sources')->firstOrFail();

    Livewire::test(GeneralFundDataEntry::class)
        ->set('selectedSourceIds', [(string) $source->id])
        ->set('years', [2024])
        ->set("amounts.{$source->id}.2024", '0.99')
        ->call('save')
        ->assertHasErrors(["amounts.{$source->id}.2024"])
        ->set("amounts.{$source->id}.2024", '1000000000000.00')
        ->call('save')
        ->assertHasErrors(["amounts.{$source->id}.2024"])
        ->set("amounts.{$source->id}.2024", '10.123')
        ->call('save')
        ->assertHasErrors(["amounts.{$source->id}.2024"])
        ->set('years', [2024, 2024])
        ->set("amounts.{$source->id}.2024", '10.12')
        ->call('save')
        ->assertHasErrors(['years.*'])
        ->set('years', [2024])
        ->set('selectedSourceIds', [(string) $nonSelectableSource->id])
        ->call('save')
        ->assertHasErrors(['selectedSourceIds']);
});

test('clearing a saved data entry cell removes the stored value', function () {
    $fund = Fund::query()->where('code', 'general_fund')->firstOrFail();
    $source = RevenueSource::query()->where('code', 'community_tax')->firstOrFail();

    RevenueForecastValue::query()->create([
        'fund_id' => $fund->id,
        'revenue_source_id' => $source->id,
        'year' => 2024,
        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
        'amount' => '2500.00',
    ]);

    Livewire::test(GeneralFundDataEntry::class)
        ->set('selectedSourceIds', [(string) $source->id])
        ->set('years', [2024])
        ->set("amounts.{$source->id}.2024", '')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('revenue_forecast_values', [
        'revenue_source_id' => $source->id,
        'year' => 2024,
        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
    ]);
});
