<?php

namespace Tests\Feature;

use App\Models\DocumentSequence;
use App\Services\DocumentSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_formats_sequential_reference_numbers(): void
    {
        $service = app(DocumentSequenceService::class);

        $this->assertSame('JL-2026-00001', $service->next('JL', 2026));
        $this->assertSame('JL-2026-00002', $service->next('JL', 2026));
        $this->assertSame('JL-2026-00003', $service->next('JL', 2026));
    }

    public function test_sequences_are_isolated_per_prefix_and_year(): void
    {
        $service = app(DocumentSequenceService::class);

        $service->next('JL', 2026);
        $service->next('JL', 2026);

        $this->assertSame('PS-2026-00001', $service->next('PS', 2026));
        $this->assertSame('JL-2027-00001', $service->next('JL', 2027));
        $this->assertSame('JL-2026-00003', $service->next('JL', 2026));
    }

    public function test_generated_numbers_are_unique_under_repeated_creation(): void
    {
        $service = app(DocumentSequenceService::class);

        $seen = [];
        for ($i = 0; $i < 200; $i++) {
            $ref = $service->next('JL', 2026);
            $this->assertArrayNotHasKey($ref, $seen, "Duplicate reference generated: {$ref}");
            $seen[$ref] = true;
        }

        $this->assertCount(200, $seen);
        $this->assertSame(200, DocumentSequence::where('prefix', 'JL')->where('year', 2026)->value('last_number'));
    }

    public function test_it_does_not_derive_numbers_from_row_counts(): void
    {
        $service = app(DocumentSequenceService::class);

        // Two prefixes exist, but each keeps its own independent counter —
        // proving the number is not a table-wide COUNT/MAX.
        $service->next('JL', 2026);
        $service->next('PS', 2026);

        $this->assertSame('JL-2026-00002', $service->next('JL', 2026));
    }
}
