<?php

namespace Pterodactyl\Tests\Unit\Traits\Services;

use Pterodactyl\Tests\TestCase;
use Pterodactyl\Traits\Services\HasUserLevels;

class HasUserLevelsTest extends TestCase
{
    /**
     * Test that the default user level is USER.
     */
    public function testDefaultUserLevel()
    {
        $instance = new HasUserLevelsTestClass();

        $this->assertSame(\Pterodactyl\Models\User::USER_LEVEL_USER, $instance->getUserLevel());
    }

    /**
     * Test that setUserLevel updates the user level and returns self.
     */
    public function testSetUserLevelReturnsSelf()
    {
        $instance = new HasUserLevelsTestClass();

        $result = $instance->setUserLevel(\Pterodactyl\Models\User::USER_LEVEL_ADMIN);

        $this->assertSame($instance, $result);
        $this->assertSame(\Pterodactyl\Models\User::USER_LEVEL_ADMIN, $instance->getUserLevel());
    }

    /**
     * Test that isUserLevel returns true for the correct level.
     */
    public function testIsUserLevelReturnsTrueForMatchingLevel()
    {
        $instance = new HasUserLevelsTestClass();
        $instance->setUserLevel(\Pterodactyl\Models\User::USER_LEVEL_ADMIN);

        $this->assertTrue($instance->isUserLevel(\Pterodactyl\Models\User::USER_LEVEL_ADMIN));
    }

    /**
     * Test that isUserLevel returns false for a non-matching level.
     */
    public function testIsUserLevelReturnsFalseForNonMatchingLevel()
    {
        $instance = new HasUserLevelsTestClass();
        $instance->setUserLevel(\Pterodactyl\Models\User::USER_LEVEL_ADMIN);

        $this->assertFalse($instance->isUserLevel(\Pterodactyl\Models\User::USER_LEVEL_USER));
    }

    /**
     * Test that the user level can be changed multiple times.
     */
    public function testUserLevelCanBeChangedMultipleTimes()
    {
        $instance = new HasUserLevelsTestClass();

        $instance->setUserLevel(\Pterodactyl\Models\User::USER_LEVEL_ADMIN);
        $this->assertTrue($instance->isUserLevel(\Pterodactyl\Models\User::USER_LEVEL_ADMIN));

        $instance->setUserLevel(\Pterodactyl\Models\User::USER_LEVEL_USER);
        $this->assertTrue($instance->isUserLevel(\Pterodactyl\Models\User::USER_LEVEL_USER));
    }
}

class HasUserLevelsTestClass
{
    use HasUserLevels;
}
