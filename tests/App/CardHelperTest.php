<?php

namespace Tests\App;

use App\CardHelper;
use PHPUnit\Framework\TestCase;

class CardHelperTest extends TestCase
{
    private CardHelper $cardHelper;

    public function setUp(): void
    {
        $this->cardHelper = new CardHelper();
    }

    /**
     * @test
     * @dataProvider isValid_data
     */
    public function test_isValid($name, $expectedIsValid)
    {
        $result = $this->cardHelper->isValidCardName($name);
        $this->assertEquals($expectedIsValid, $result);
    }

    public function isValid_data(): array
    {
        return [
            ['PIC-0001', true],
            ['PIC--0001', false],
            ['PI0001', false],
            ['PIC001', false],
            ['APIC0001', false],
            ['APIC-0001', false],
        ];
    }

    public function test_getSample_should_return_a_valid_name()
    {
        $this->assertTrue($this->cardHelper->isValidCardName($this->cardHelper->getSampleCardName()), 'The card name sample is invalid. Did you change something and forgot to update it?');
    }


}