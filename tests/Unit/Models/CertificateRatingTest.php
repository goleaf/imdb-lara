<?php

namespace Tests\Unit\Models;

use App\Models\CertificateRating;
use PHPUnit\Framework\TestCase;

class CertificateRatingTest extends TestCase
{
    public function test_certificate_rating_resolves_common_mpaa_metadata(): void
    {
        $certificateRating = new CertificateRating;
        $certificateRating->setRawAttributes([
            'id' => 57,
            'name' => 'PG-13',
        ], sync: true);

        $this->assertSame('PG-13', $certificateRating->resolvedLabel());
        $this->assertSame('Some material may be unsuitable for children under 13.', $certificateRating->shortDescription());
        $this->assertSame('teen', $certificateRating->tone());
        $this->assertSame('triangle-exclamation', $certificateRating->iconName());
    }

    public function test_certificate_rating_resolves_adult_only_metadata_for_regional_values(): void
    {
        $certificateRating = new CertificateRating;
        $certificateRating->setRawAttributes([
            'id' => 154,
            'name' => '18A',
        ], sync: true);

        $this->assertSame('18A', $certificateRating->resolvedLabel());
        $this->assertSame('Adults-only classification; 18 is the local reference age.', $certificateRating->shortDescription());
        $this->assertSame('adult', $certificateRating->tone());
        $this->assertSame('lock', $certificateRating->iconName());
    }

    public function test_certificate_rating_resolves_television_metadata(): void
    {
        $certificateRating = new CertificateRating;
        $certificateRating->setRawAttributes([
            'id' => 166,
            'name' => 'TV-Y7-FV',
        ], sync: true);

        $this->assertSame('TV-Y7-FV', $certificateRating->resolvedLabel());
        $this->assertSame('Television rating for children 7 and older, with fantasy violence.', $certificateRating->shortDescription());
        $this->assertSame('tv', $certificateRating->tone());
        $this->assertSame('tv', $certificateRating->iconName());
    }
}
