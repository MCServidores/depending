<?php

/*
 * This file is part of the depending.in package.
 *
 * (c) depending.in 2013
 */

use app\Parameter;

class ParameterTest extends DependingInTestCase {

	/**
	 * Cek konsistensi parameter instance
	 */
	public function testCekKonsistensiAppParameter()
	{
		$parameter = new Parameter(array('foo' => 'bar'));
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\ParameterBag', $parameter);
		$this->assertFalse($parameter->isEmpty());
		$this->assertEquals('bar', $parameter->foo());
		$this->assertEmpty($parameter->undefined());
	}
}