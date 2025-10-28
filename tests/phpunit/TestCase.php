<?php

declare(strict_types=1);

/**
 * Base Test Case.
 *
 * This extends WP_UnitTestCase to provide additional functionality.
 */
abstract class TestCase extends WP_UnitTestCase
{
    /**
     * Capture output from a callback without printing it to console.
     *
     * This method tells PHPUnit to expect output, which prevents it from
     * printing the output to the console while still allowing assertions
     * on the captured output.
     *
     * @param callable $callback The function that produces output
     * @return string The captured output
     */
    protected function captureOutput(callable $callback): string
    {
        // Tell PHPUnit we expect output (this prevents console printing)
        $this->expectOutputRegex('/.*/s');

        // Execute the callback (output will be captured by PHPUnit)
        $callback();

        // Return the captured output for assertions
        return $this->getActualOutput();
    }
}
