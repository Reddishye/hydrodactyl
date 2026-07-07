<?php

namespace Pterodactyl\Tests\Unit\Traits\Services;

use Pterodactyl\Tests\TestCase;
use Pterodactyl\Traits\Services\ReturnsUpdatedModels;

class ReturnsUpdatedModelsTest extends TestCase
{
    /**
     * Test that the default value is false.
     */
    public function testDefaultReturnsFalse()
    {
        $instance = new ReturnsUpdatedModelsTestClass();

        $this->assertFalse($instance->getUpdatedModel());
    }

    /**
     * Test that returnUpdatedModel sets the flag to true and returns self.
     */
    public function testReturnUpdatedModelSetsTrueAndReturnsSelf()
    {
        $instance = new ReturnsUpdatedModelsTestClass();

        $result = $instance->returnUpdatedModel();

        $this->assertSame($instance, $result);
        $this->assertTrue($instance->getUpdatedModel());
    }

    /**
     * Test that returnUpdatedModel(false) sets the flag to false.
     */
    public function testReturnUpdatedModelFalseSetsFalse()
    {
        $instance = new ReturnsUpdatedModelsTestClass();
        $instance->returnUpdatedModel();

        $this->assertTrue($instance->getUpdatedModel());

        $instance->returnUpdatedModel(false);

        $this->assertFalse($instance->getUpdatedModel());
    }

    /**
     * Test that returnUpdatedModel(true) explicitly sets the flag to true.
     */
    public function testReturnUpdatedModelTrueSetsTrue()
    {
        $instance = new ReturnsUpdatedModelsTestClass();

        $instance->returnUpdatedModel(true);

        $this->assertTrue($instance->getUpdatedModel());
    }
}

class ReturnsUpdatedModelsTestClass
{
    use ReturnsUpdatedModels;
}
