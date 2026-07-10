<?php

use App\Livewire\Pages\Analytics\GeneralFundAnalyticsDashboard;
use App\Livewire\Pages\Analytics\GenFundComparisonDashboard;
use App\Models\RevenueForecastValue;
use App\Models\RevenueSource;
use App\Models\User;
use App\Support\GeneralFundRevenueAnalytics;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function generalFundAnalyticsComponent(): GeneralFundAnalyticsDashboard
{
    $component = app(GeneralFundAnalyticsDashboard::class);
    $component->mount();

    return $component;
}

function genFundComparisonComponent(): GenFundComparisonDashboard
{
    $component = app(GenFundComparisonDashboard::class);
    $component->mount();

    return $component;
}

test('authenticated users can access both general fund analytics pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('analytics.general-fund'))
        ->assertOk()
        ->assertSee('General Fund Analytics')
        ->assertSee('Level 1')
        ->assertSee('Line Items')
        ->assertSee('Level 1 Summary');

    $this->actingAs($user)
        ->get(route('analytics.gen-fund-comparison'))
        ->assertOk()
        ->assertSee('Gen Fund Comparison')
        ->assertSee('Level 2')
        ->assertSee('Level 1 Comparison');
});

test('general fund analytics defaults to latest year and comparison defaults earliest to latest', function () {
    Livewire::test(GeneralFundAnalyticsDashboard::class)
        ->assertSet('selectedYear', '2025');

    Livewire::test(GenFundComparisonDashboard::class)
        ->assertSet('baselineYear', '2015')
        ->assertSet('comparativeYear', '2025');
});

test('parent source selection toggles descendants in analytics modules', function () {
    $localSources = RevenueSource::query()->where('code', 'local_internal_sources')->firstOrFail();
    $analytics = new GeneralFundRevenueAnalytics($localSources->fund);
    $expectedSubtreeIds = collect($analytics->subtreeIds($localSources->id, $analytics->sources()))
        ->map(fn ($id) => (string) $id)
        ->sort()
        ->values()
        ->all();

    $component = Livewire::test(GeneralFundAnalyticsDashboard::class)
        ->set('selectedSourceIds', [])
        ->call('toggleSourceSelection', $localSources->id);

    expect(collect($component->get('selectedSourceIds'))->sort()->values()->all())->toBe($expectedSubtreeIds);

    $comparisonComponent = Livewire::test(GenFundComparisonDashboard::class)
        ->set('selectedSourceIds', [])
        ->call('toggleSourceSelection', $localSources->id);

    expect(collect($comparisonComponent->get('selectedSourceIds'))->sort()->values()->all())->toBe($expectedSubtreeIds);
});

test('multi-source selected total equals selected rollup rows', function () {
    $snapshot = generalFundAnalyticsComponent()->analyticsSnapshot();

    expect(round(collect($snapshot['selectedRootRows'])->sum('amount'), 2))->toBe($snapshot['selectedTotal']);
});

test('nta remains selectable and included in comparison analytics', function () {
    $nta = RevenueSource::query()->where('code', 'share_from_national_tax_allocation_nta')->firstOrFail();
    $component = genFundComparisonComponent();
    $component->selectedSourceIds = [(string) $nta->id];

    $snapshot = $component->analyticsSnapshot();
    $row = collect($snapshot['selectedRootComparisonRows'])->firstWhere('source_code', 'share_from_national_tax_allocation_nta');

    expect($row)->not->toBeNull()
        ->and($row['type'])->toBe(RevenueSource::TYPE_CATEGORY)
        ->and($row['accepts_values'])->toBeTrue()
        ->and($row['comparative_amount'])->toBeGreaterThan(0);
});

test('latest-year shares and year-over-year growth are computed for analytics', function () {
    $snapshot = generalFundAnalyticsComponent()->analyticsSnapshot();
    $firstRow = $snapshot['selectedRootRows'][0];

    expect($snapshot['previousYear'])->toBe(2024)
        ->and($snapshot['yearOverYearGrowth'])->not->toBeNull()
        ->and($firstRow['share'])->toBe(round(($firstRow['amount'] / $snapshot['selectedTotal']) * 100, 2));
});

test('general fund analytics combo chart includes column series and total line', function () {
    $component = generalFundAnalyticsComponent();
    $payload = $component->chartPayload($component->analyticsSnapshot());

    expect($payload['combo']['series'])->not->toBeEmpty()
        ->and(collect($payload['combo']['series'])->where('type', 'column')->count())->toBeGreaterThan(0)
        ->and(collect($payload['combo']['series'])->where('type', 'line')->pluck('name')->all())->toContain('Selected Total');
});

test('general fund analytics tabs show level one categories and line items', function () {
    $component = generalFundAnalyticsComponent();

    $levelOneRows = $component->analyticsSnapshot()['levelRows'];
    expect(collect($levelOneRows)->pluck('type')->unique()->values()->all())
        ->toBe([RevenueSource::TYPE_MAIN_SOURCE]);

    $component->setLevel('level_2');
    $levelTwoRows = $component->analyticsSnapshot()['levelRows'];
    expect(collect($levelTwoRows)->pluck('type')->unique()->values()->all())
        ->toBe([RevenueSource::TYPE_CATEGORY]);

    $component->setLevel('line_items');
    $lineItemRows = collect($component->analyticsSnapshot()['levelRows']);
    expect($lineItemRows->pluck('accepts_values')->unique()->values()->all())
        ->toBe([true])
        ->and($lineItemRows->pluck('source_code')->all())->toContain('share_from_national_tax_allocation_nta');
});

test('deselecting a selected category updates line item tab rows', function () {
    $taxRevenue = RevenueSource::query()->where('code', 'tax_revenue')->firstOrFail();
    $component = Livewire::test(GeneralFundAnalyticsDashboard::class)
        ->call('setLevel', 'line_items')
        ->call('toggleSourceSelection', $taxRevenue->id);

    $selectedSourceIds = $component->get('selectedSourceIds');
    $snapshot = generalFundAnalyticsComponent();
    $snapshot->selectedSourceIds = $selectedSourceIds;
    $snapshot->setLevel('line_items');
    $lineItemCodes = collect($snapshot->analyticsSnapshot()['levelRows'])->pluck('source_code')->all();

    expect($lineItemCodes)->not->toContain('business_tax')
        ->and($lineItemCodes)->not->toContain('community_tax');
});

test('deselecting a selected category updates comparison line item rows', function () {
    $taxRevenue = RevenueSource::query()->where('code', 'tax_revenue')->firstOrFail();
    $component = Livewire::test(GenFundComparisonDashboard::class)
        ->call('setLevel', 'line_items')
        ->call('toggleSourceSelection', $taxRevenue->id);

    $selectedSourceIds = $component->get('selectedSourceIds');
    $snapshot = genFundComparisonComponent();
    $snapshot->selectedSourceIds = $selectedSourceIds;
    $snapshot->setLevel('line_items');
    $lineItemCodes = collect($snapshot->analyticsSnapshot()['levelComparisonRows'])->pluck('source_code')->all();

    expect($lineItemCodes)->not->toContain('business_tax')
        ->and($lineItemCodes)->not->toContain('community_tax');
});

test('general fund analytics share chart follows the active tab level', function () {
    $component = generalFundAnalyticsComponent();
    $component->setLevel('level_2');
    $payload = $component->chartPayload($component->analyticsSnapshot());

    expect($payload['share']['labels'])->toContain('Tax Revenue')
        ->and($payload['bar']['categories'])->toContain('Tax Revenue');
});

test('comparison metrics calculate difference growth and zero-baseline growth', function () {
    $businessTax = RevenueSource::query()->where('code', 'business_tax')->firstOrFail();

    RevenueForecastValue::query()
        ->where('revenue_source_id', $businessTax->id)
        ->where('year', 2015)
        ->where('value_type', RevenueForecastValue::TYPE_HISTORICAL)
        ->update(['amount' => 0]);

    $component = genFundComparisonComponent();
    $component->selectedSourceIds = [(string) $businessTax->id];
    $component->baselineYear = '2015';
    $component->comparativeYear = '2025';

    $row = $component->analyticsSnapshot()['selectedRootComparisonRows'][0];

    expect($row['source_code'])->toBe('business_tax')
        ->and($row['baseline_amount'])->toBe(0.0)
        ->and($row['comparative_amount'])->toBeGreaterThan(0)
        ->and($row['difference'])->toBe($row['comparative_amount'])
        ->and($row['growth_percent'])->toBeNull();
});

test('comparison combo chart includes baseline comparative bars and growth line', function () {
    $component = genFundComparisonComponent();
    $payload = $component->chartPayload($component->analyticsSnapshot());

    expect($payload['combo']['series'])->toHaveCount(3)
        ->and($payload['combo']['series'][0]['type'])->toBe('column')
        ->and($payload['combo']['series'][1]['type'])->toBe('column')
        ->and($payload['combo']['series'][2]['type'])->toBe('line')
        ->and($payload['combo']['series'][2]['name'])->toBe('Growth %');
});

test('comparison tabs drive combo and share chart payloads', function () {
    $component = genFundComparisonComponent();
    $component->setLevel('line_items');
    $payload = $component->chartPayload($component->analyticsSnapshot());

    expect($payload['combo']['categories'])->toContain('Business Tax')
        ->and($payload['share']['labels'])->toContain('Business Tax')
        ->and(collect($component->analyticsSnapshot()['levelComparisonRows'])->pluck('accepts_values')->unique()->values()->all())->toBe([true]);
});
