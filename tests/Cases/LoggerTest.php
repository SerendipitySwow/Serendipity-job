<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SerendipityTest\Cases;

use SerendipityTest\HttpTestCase;

/**
 * @internal
 * @coversNothing
 */
class LoggerTest extends HttpTestCase
{
    public function testLogger(): void
    {
        $this->assertTrue(true);
        $stack = [];
        $this->assertCount(0, $stack);

        $stack[] = 'foo';
        $this->assertSame('foo', $stack[count($stack) - 1]);
        $this->assertCount(1, $stack);

        $this->assertSame('foo', array_pop($stack));
        $this->assertCount(0, $stack);
    }
}
