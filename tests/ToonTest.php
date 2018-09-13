<?php

use PHPUnit\Framework\TestCase;

/**
 * @author SliceOLife
 */
class ToonTest extends TestCase
{
    public function defaultTest()
    {
        $var = new SliceOLife\Toon\Toon;
        $this->assertTrue(is_object($var));
        unset($var);
    }
}
