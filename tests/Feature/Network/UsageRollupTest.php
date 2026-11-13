<?php

namespace Tests\Feature\Network;

use App\Domain\Accounting\Dto\AccountingEvent;
use App\Domain\Accounting\Models\CustomerUsageDaily;
use App\Domain\Accounting\Models\PartnerUsageDaily;
use App\Domain\Accounting\Models\RadiusSession;
use App\Domain\Accounting\Models\RouterQueueSample;
use App\Domain\Accounting\Services\AccountingIngestor;
use App\Domain\Accounting\Services\StaleSessionReconciler;
use App\Domain\Accounting\Services\UsageRollupService;
use App\Domain\Network\Models\NasDevice;
use App\Domain\Network\Models\NetworkAccessAssignment;
use Carbon\CarbonImmutable;

class UsageRollupTest extends NetworkTestCase
{
    private NasDevice $nas;

    private NetworkAccessAssignment $assignment;

    private AccountingIngestor $ingestor;

    private UsageRollupService $rollup;

    private int $customerId;

    private int $partnerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nas = NasDevice::query()->create([
            'code' => 'NAS-01',
            'name' => 'NAS Lab 01',
            'management_ip' => '10.10.0.1',
            'accounting_enabled' => true,
        ]);

        $this->partnerId = $this->makeMitra();
        $this->customerId = $this->makeCustomer($this->partnerId);

        $this->assignment = NetworkAccessAssignment::query()->create([
            'subscription_id' => 501,
            'customer_id' => $this->customerId,
            'partner_id' => $this->partnerId,
            'nas_device_id' => $this->nas->id,
            'pppoe_username' => 'pppoe-0001',
            'desired_state' => 'active',
        ]);

        $this->ingestor = new AccountingIngestor();
        $this->rollup = new UsageRollupService(calculationVersion: 1);
    }

    private function event(string $type, string $at, array $overrides = [], string $session = 'S-1'): AccountingEvent
    {
        $attributes = array_merge([
            'Acct-Status-Type' => $type,
            'Acct-Session-Id' => $session,
            'User-Name' => 'pppoe-0001',
            'Event-Timestamp' => CarbonImmutable::parse($at, 'UTC')->getTimestamp(),
            'Acct-Start-Time' => CarbonImmutable::parse($at, 'UTC')->getTimestamp(),
        ], $overrides);

        return AccountingEvent::fromRadiusAttributes($this->nas->id, $this->nas->code, $attributes);
    }

    public function test_rollup_uses_deltas_not_cumulative_totals(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));
        $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:05:00', [
            'Acct-Input-Octets' => 1000,
            'Acct-Output-Octets' => 2000,
        ]));
        $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:10:00', [
            'Acct-Input-Octets' => 1500,
            'Acct-Output-Octets' => 3000,
        ]));
        $this->ingestor->ingest($this->event('Stop', '2026-11-13 10:15:00', [
            'Acct-Input-Octets' => 1500,
            'Acct-Output-Octets' => 3000,
        ]));

        $row = $this->rollup->rollupCustomerDay($this->customerId, CarbonImmutable::parse('2026-11-13', 'UTC'));

        $this->assertNotNull($row);
        $this->assertSame(1500, (int) $row->upload_bytes);
        $this->assertSame(3000, (int) $row->download_bytes);
        $this->assertSame(4500, (int) $row->total_bytes);
        $this->assertSame(CustomerUsageDaily::COMPLETE, $row->completeness);
        $this->assertSame($this->partnerId, (int) $row->partner_id);
    }

    public function test_midnight_straddling_interval_is_split_and_flagged_estimated(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-12 23:50:00'));
        $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 00:10:00', [
            'Acct-Input-Octets' => 600,
            'Acct-Output-Octets' => 600,
        ]));

        $day1 = $this->rollup->rollupCustomerDay($this->customerId, CarbonImmutable::parse('2026-11-12', 'UTC'));
        $day2 = $this->rollup->rollupCustomerDay($this->customerId, CarbonImmutable::parse('2026-11-13', 'UTC'));

        $this->assertSame(300, (int) $day1->upload_bytes);
        $this->assertSame(300, (int) $day1->download_bytes);
        $this->assertSame(300, (int) $day2->upload_bytes);
        $this->assertSame(300, (int) $day2->download_bytes);
        $this->assertSame(CustomerUsageDaily::ESTIMATED, $day1->completeness);
        $this->assertSame(CustomerUsageDaily::ESTIMATED, $day2->completeness);
    }

    public function test_partner_rollup_aggregates_customers_and_independent_queue_peak(): void
    {
        CustomerUsageDaily::query()->create([
            'customer_id' => $this->customerId,
            'partner_id' => $this->partnerId,
            'usage_date' => '2026-11-13',
            'upload_bytes' => 100,
            'download_bytes' => 900,
            'total_bytes' => 1000,
            'completeness' => CustomerUsageDaily::COMPLETE,
            'calculation_version' => 1,
            'calculated_at' => CarbonImmutable::now('UTC'),
        ]);

        RouterQueueSample::query()->create([
            'nas_device_id' => $this->nas->id,
            'partner_id' => $this->partnerId,
            'queue_key' => 'partner-'.$this->partnerId,
            'sampled_at' => CarbonImmutable::parse('2026-11-13 12:00:00', 'UTC'),
            'current_rate_down_bps' => 50_000_000,
            'current_rate_up_bps' => 10_000_000,
            'sample_interval_seconds' => 60,
            'dedupe_key' => hash('sha256', 'queue-1'),
        ]);

        $row = $this->rollup->rollupPartnerDay($this->partnerId, CarbonImmutable::parse('2026-11-13', 'UTC'));

        $this->assertNotNull($row);
        $this->assertSame(1000, (int) $row->total_bytes);
        $this->assertEquals(50.0, (float) $row->peak_download_mbps);
        $this->assertEquals(10.0, (float) $row->peak_upload_mbps);
        $this->assertSame(PartnerUsageDaily::COMPLETE, $row->completeness);
    }

    public function test_partner_rollup_marks_estimated_without_queue_samples(): void
    {
        CustomerUsageDaily::query()->create([
            'customer_id' => $this->customerId,
            'partner_id' => $this->partnerId,
            'usage_date' => '2026-11-13',
            'upload_bytes' => 10,
            'download_bytes' => 10,
            'total_bytes' => 20,
            'completeness' => CustomerUsageDaily::COMPLETE,
            'calculation_version' => 1,
            'calculated_at' => CarbonImmutable::now('UTC'),
        ]);

        $row = $this->rollup->rollupPartnerDay($this->partnerId, CarbonImmutable::parse('2026-11-13', 'UTC'));

        $this->assertSame(PartnerUsageDaily::ESTIMATED, $row->completeness);
        $this->assertNull($row->peak_download_mbps);
    }

    public function test_missing_stop_is_reconciled_as_estimated_without_fabricated_stop_time(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 06:00:00'));

        $closed = (new StaleSessionReconciler())->reconcile(CarbonImmutable::parse('2026-11-13 10:00:00', 'UTC'));

        $session = RadiusSession::query()->firstOrFail();

        $this->assertSame(1, $closed);
        $this->assertSame(RadiusSession::STALE, $session->status);
        $this->assertTrue((bool) $session->estimated_close);
        $this->assertSame('missing_stop', $session->close_reason);
        $this->assertNull($session->stop_at);

        $row = $this->rollup->rollupCustomerDay($this->customerId, CarbonImmutable::parse('2026-11-13', 'UTC'));

        $this->assertSame(CustomerUsageDaily::ESTIMATED, $row->completeness);
    }

    public function test_invalid_sample_excludes_bytes_and_marks_estimated(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));
        $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:10:00', [
            'Acct-Input-Octets' => 900,
        ]));
        // Out-of-order sample (older timestamp) must never be added to the day total.
        $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:05:00', [
            'Acct-Input-Octets' => 700,
        ]));

        $row = $this->rollup->rollupCustomerDay($this->customerId, CarbonImmutable::parse('2026-11-13', 'UTC'));

        $this->assertSame(900, (int) $row->upload_bytes);
        $this->assertSame(CustomerUsageDaily::ESTIMATED, $row->completeness);
    }
}
