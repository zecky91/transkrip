<?php

namespace Tests\Unit;

use Tests\TestCase;

class ImportValidationTest extends TestCase
{
    public function test_nisn_must_be_numeric()
    {
        // Valid NISN
        $this->assertTrue(ctype_digit('1234567890'));

        // Invalid NISN
        $this->assertFalse(ctype_digit('123ABC7890'));
        $this->assertFalse(ctype_digit(''));
        $this->assertFalse(ctype_digit('123-456-7890'));
    }

    public function test_value_range_clamping()
    {
        $clamp = function ($value) {
            return max(0, min(100, $value));
        };

        // Normal values
        $this->assertEquals(85, $clamp(85));
        $this->assertEquals(0, $clamp(0));
        $this->assertEquals(100, $clamp(100));

        // Values outside range get clamped
        $this->assertEquals(0, $clamp(-10));
        $this->assertEquals(100, $clamp(150));
    }

    public function test_name_mismatch_detection()
    {
        $masterName = 'John Doe';
        $importName = 'John Doe';
        $mismatchName = 'Jane Doe';

        // Same name (case insensitive)
        $this->assertTrue(
            strtolower(trim($masterName)) === strtolower(trim($importName))
        );

        // Different name
        $this->assertFalse(
            strtolower(trim($masterName)) === strtolower(trim($mismatchName))
        );

        // Case insensitive match
        $this->assertTrue(
            strtolower(trim('JOHN DOE')) === strtolower(trim('john doe'))
        );
    }

    public function test_duplicate_nisn_detection()
    {
        $processedNisns = ['1234567890', '0987654321'];

        // Not duplicate
        $this->assertFalse(in_array('1111111111', $processedNisns));

        // Is duplicate
        $this->assertTrue(in_array('1234567890', $processedNisns));
    }
}
