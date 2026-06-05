<?php

namespace Tests\Unit;

use App\Filament\Resources\FeeStructures\Schemas\FeeStructureForm;
use PHPUnit\Framework\TestCase;

class FeeStructureFormTest extends TestCase
{
    public function test_it_sanitizes_formatted_amounts_before_saving(): void
    {
        $this->assertSame('3000.00', FeeStructureForm::sanitizeAmount('3,000.00'));
        $this->assertSame('45000.50', FeeStructureForm::sanitizeAmount('NGN 45,000.50'));
        $this->assertSame('0.00', FeeStructureForm::sanitizeAmount('not a number'));
    }
}
