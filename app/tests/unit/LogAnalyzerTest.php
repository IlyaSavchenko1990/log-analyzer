<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LogAnalyzerTest extends TestCase
{
    public static function logLineProvider(): array
    {
        return [
            ''
        ];
    }

    #[DataProvider('logLineProvider')]
    public function testFileReading(int $a, int $b, int $expected): void
    {
        // $this->assertSame($expected, $a + $b);
    }
}

