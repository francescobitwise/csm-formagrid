<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MediaDuration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaDurationTest extends TestCase
{
    #[Test]
    public function parses_iso8601_durations(): void
    {
        $this->assertSame(3000, MediaDuration::parseDurationSeconds('PT50M'));
        $this->assertSame(4830, MediaDuration::parseDurationSeconds('PT1H20M30S'));
        $this->assertSame(90, MediaDuration::parseDurationSeconds('PT90S'));
        $this->assertSame(45, MediaDuration::parseDurationSeconds('PT44.6S'));
    }

    #[Test]
    public function parses_clock_durations(): void
    {
        $this->assertSame(3000, MediaDuration::parseDurationSeconds('00:50:00'));
        $this->assertSame(3000, MediaDuration::parseDurationSeconds('50:00'));
        $this->assertSame(4830, MediaDuration::parseDurationSeconds('1:20:30'));
        $this->assertSame(125, MediaDuration::parseDurationSeconds('02:05.500'));
    }

    #[Test]
    public function rejects_invalid_or_zero_durations(): void
    {
        $this->assertNull(MediaDuration::parseDurationSeconds(''));
        $this->assertNull(MediaDuration::parseDurationSeconds('abc'));
        $this->assertNull(MediaDuration::parseDurationSeconds('PT0S'));
        $this->assertNull(MediaDuration::parseDurationSeconds('00:00:00'));
    }

    #[Test]
    public function extracts_typical_learning_time_from_manifest_xml(): void
    {
        $xml = <<<'XML'
        <manifest xmlns:imsmd="http://www.imsglobal.org/xsd/imsmd_v1p2">
            <metadata>
                <imsmd:lom>
                    <imsmd:educational>
                        <imsmd:typicallearningtime>
                            <imsmd:datetime>00:50:00</imsmd:datetime>
                        </imsmd:typicallearningtime>
                    </imsmd:educational>
                </imsmd:lom>
            </metadata>
        </manifest>
        XML;

        $this->assertSame(3000, MediaDuration::scormManifestXmlSeconds($xml));
    }

    #[Test]
    public function extracts_duration_from_real_ispring_manifest_with_lom_prefix(): void
    {
        // Struttura reale degli export iSpring del corso "MOG 231" (SCORM 1.2, prefisso lom:).
        $xml = <<<'XML'
        <manifest identifier="FbIKeUxouP6jA" xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2" xmlns:lom="http://www.imsglobal.org/xsd/imsmd_rootv1p2p1">
        <metadata>
        <schema>ADL SCORM</schema>
        <schemaversion>1.2</schemaversion>
        <lom:lom>
        <lom:educational>
        <lom:typicallearningtime>
        <lom:datetime>01:00:00</lom:datetime>
        </lom:typicallearningtime>
        </lom:educational>
        </lom:lom>
        </metadata>
        </manifest>
        XML;

        $this->assertSame(3600, MediaDuration::scormManifestXmlSeconds($xml));
    }

    #[Test]
    public function manifest_without_duration_returns_null(): void
    {
        $this->assertSame(null, MediaDuration::scormManifestXmlSeconds('<manifest></manifest>'));
    }
}
