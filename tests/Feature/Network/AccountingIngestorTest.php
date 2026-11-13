<?php

namespace Tests\Feature\Network;

use App\Domain\Accounting\Dto\AccountingEvent;
use App\Domain\Accounting\Models\RadiusAccountingSample;
use App\Domain\Accounting\Models\RadiusSession;
use App\Domain\Accounting\Services\AccountingIngestor;
use App\Domain\Network\Models\NasDevice;
use Carbon\CarbonImmutable;

class AccountingIngestorTest extends NetworkTestCase
{
    private NasDevice $nas;

    private AccountingIngestor $ingestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nas = NasDevice::query()->create([
            'code' => 'NAS-01',
            'name' => 'NAS Lab 01',
            'management_ip' => '10.10.0.1',
            'accounting_enabled' => true,
        ]);

        $this->ingestor = new AccountingIngestor();
    }

    private function event(string $type, string $at, array $overrides = []): AccountingEvent
    {
        $attributes = array_merge([
            'Acct-Status-Type' => $type,
            'Acct-Session-Id' => 'S-0001',
            'Acct-Unique-Session-Id' => 'U-0001',
            'User-Name' => 'pppoe-0001',
            'Event-Timestamp' => CarbonImmutable::parse($at, 'UTC')->getTimestamp(),
            'Acct-Start-Time' => CarbonImmutable::parse('2026-11-13 10:00:00', 'UTC')->getTimestamp(),
            'Acct-Input-Octets' => 0,
            'Acct-Output-Octets' => 0,
        ], $overrides);

        return AccountingEvent::fromRadiusAttributes($this->nas->id, $this->nas->code, $attributes);
    }

    public function test_duplicate_start_event_is_idempotent(): void
    {
        $event = $this->event('Start', '2026-11-13 10:00:00');

        $first = $this->ingestor->ingest($event);
        $second = $this->ingestor->ingest($event);

        $this->assertFalse($first->duplicate);
        $this->assertTrue($second->duplicate);
        $this->assertSame(1, RadiusAccountingSample::query()->count());
        $this->assertSame(1, RadiusSession::query()->count());
    }

    public function test_interim_delta_is_current_cumulative_minus_previous(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));
        $result = $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:05:00', [
            'Acct-Input-Octets' => 1500,
            'Acct-Output-Octets' => 2500,
        ]));

        $sample = RadiusAccountingSample::query()->findOrFail($result->sampleId);

        $this->assertTrue($result->deltaValid);
        $this->assertSame(1500, $sample->input_delta_bytes);
        $this->assertSame(2500, $sample->output_delta_bytes);
        $this->assertSame(1500, $sample->input_total_bytes);
        $this->assertSame(2500, $sample->output_total_bytes);
    }

    public function test_repeated_interim_does_not_add_bytes_twice(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));

        $interim = $this->event('Interim-Update', '2026-11-13 10:05:00', [
            'Acct-Input-Octets' => 1500,
            'Acct-Output-Octets' => 2500,
        ]);

        $this->ingestor->ingest($interim);
        $duplicate = $this->ingestor->ingest($interim);

        $this->assertTrue($duplicate->duplicate);
        $this->assertSame(2, RadiusAccountingSample::query()->count());
        $this->assertSame(1500, (int) RadiusAccountingSample::query()->sum('input_delta_bytes'));
    }

    public function test_out_of_order_sample_is_stored_but_not_counted(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));
        $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:10:00', [
            'Acct-Input-Octets' => 2000,
            'Acct-Output-Octets' => 2000,
        ]));

        $result = $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:05:00', [
            'Acct-Input-Octets' => 1200,
            'Acct-Output-Octets' => 1200,
        ]));

        $sample = RadiusAccountingSample::query()->findOrFail($result->sampleId);
        $session = RadiusSession::query()->firstOrFail();

        $this->assertTrue($result->outOfOrder);
        $this->assertFalse($result->deltaValid);
        $this->assertSame(0, (int) $sample->input_delta_bytes);
        $this->assertSame(2000, (int) $session->input_bytes_64);
        $this->assertSame(2000, (int) RadiusAccountingSample::query()->sum('input_delta_bytes'));
    }

    public function test_counter_reset_is_flagged_and_negative_delta_never_counted(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));
        $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:05:00', [
            'Acct-Input-Octets' => 5000,
        ]));

        $result = $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:10:00', [
            'Acct-Input-Octets' => 100,
        ]));

        $this->assertTrue($result->counterReset);
        $this->assertFalse($result->deltaValid);
        $this->assertSame(5000, (int) RadiusAccountingSample::query()->sum('input_delta_bytes'));
        $this->assertSame(100, (int) RadiusSession::query()->firstOrFail()->input_bytes_64);
    }

    public function test_gigawords_are_converted_to_64_bit_totals(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));
        $result = $this->ingestor->ingest($this->event('Interim-Update', '2026-11-13 10:05:00', [
            'Acct-Input-Octets' => 100,
            'Acct-Input-Gigawords' => 1,
            'Acct-Output-Octets' => 0,
            'Acct-Output-Gigawords' => 2,
        ]));

        $sample = RadiusAccountingSample::query()->findOrFail($result->sampleId);

        $this->assertSame(4294967296 + 100, (int) $sample->input_total_bytes);
        $this->assertSame(4294967296 * 2, (int) $sample->output_total_bytes);
        $this->assertSame(4294967296 + 100, (int) $sample->input_delta_bytes);
    }

    public function test_stop_closes_the_session_without_estimating(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));
        $this->ingestor->ingest($this->event('Stop', '2026-11-13 10:30:00', [
            'Acct-Input-Octets' => 10,
            'Acct-Output-Octets' => 20,
            'Acct-Session-Time' => 1800,
            'Acct-Terminate-Cause' => 'User-Request',
        ]));

        $session = RadiusSession::query()->firstOrFail();

        $this->assertSame(RadiusSession::CLOSED, $session->status);
        $this->assertSame(1800, (int) $session->session_seconds);
        $this->assertSame('User-Request', $session->terminate_cause);
        $this->assertFalse((bool) $session->estimated_close);
        $this->assertNotNull($session->stop_at);
    }

    public function test_reused_session_id_with_new_start_time_creates_a_new_session(): void
    {
        $this->ingestor->ingest($this->event('Start', '2026-11-13 10:00:00'));
        $this->ingestor->ingest($this->event('Stop', '2026-11-13 10:30:00'));

        $this->ingestor->ingest($this->event('Start', '2026-11-13 11:00:00', [
            'Acct-Start-Time' => CarbonImmutable::parse('2026-11-13 11:00:00', 'UTC')->getTimestamp(),
        ]));

        $this->assertSame(2, RadiusSession::query()->count());
        $this->assertSame(1, RadiusSession::query()->where('status', RadiusSession::ACTIVE)->count());
    }

    public function test_attributes_from_radius_packet_are_normalized(): void
    {
        $event = $this->event('Interim-Update', '2026-11-13 10:05:00', [
            'Framed-IP-Address' => '100.64.0.15',
            'Acct-Terminate-Cause' => 'Idle-Timeout',
        ]);

        $this->assertSame(AccountingEvent::INTERIM, $event->eventType);
        $this->assertSame('100.64.0.15', $event->framedIp);
        $this->assertSame($this->nas->id, $event->nasDeviceId);
    }
}
