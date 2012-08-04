<?php

use Illuminate\Events\Dispatcher;

class DispatcherTest extends PHPUnit_Framework_TestCase {

	public function testBasicEventFiring()
	{
		$e = new Dispatcher;
		$e->listen('foo', function()
		{
			return 'bar';
		});
		$e->listen('foo', function()
		{
			return 'baz';
		});
		$responses = $e->fire('foo');

		$this->assertEquals(2, count($responses));
		$this->assertEquals('bar', $responses[0]);
		$this->assertEquals('baz', $responses[1]);
	}


	public function testFirstMethod()
	{
		$GLOBALS['__event.test.foo'] = false;
		$e = new Dispatcher;
		$e->listen('foo', function()
		{
			return 'baz';
		});
		$e->listen('foo', function()
		{
			$GLOBALS['__event.test.foo'] = true;
			return 'bar';
		});
		$response = $e->first('foo');

		$this->assertEquals('baz', $response);
		$this->assertFalse($GLOBALS['__event.test.foo']);

		unset($GLOBALS['__event.test.foo']);
	}


	public function testQueue()
	{
		unset($GLOBALS['__event.test.queue']);
		$e = new Dispatcher;
		$e->queue('foo', 1, array('name' => 'Taylor'));
		$e->queue('foo', 2, array('name' => 'Eric'));
		$e->flusher('foo', function($key, $payload)
		{
			$GLOBALS['__event.test.queue'][] = compact('key', 'payload');
		});
		$e->flush('foo');

		$this->assertEquals(array('key' => 1, 'payload' => 'Taylor'), $GLOBALS['__event.test.queue'][0]);
		$this->assertEquals(array('key' => 2, 'payload' => 'Eric'), $GLOBALS['__event.test.queue'][1]);
		unset($GLOBALS['__event.test.queue']);
	}


	public function testMultiFlush()
	{
		unset($GLOBALS['__event.test.multi']);
		$e = new Dispatcher;
		$e->queue('foo', 1, array('name' => 'Taylor'));
		$e->flusher('foo', function($key, $payload)
		{
			$GLOBALS['__event.test.multi'] = 1;
		});
		$e->flusher('foo', function($key, $payload)
		{
			$GLOBALS['__event.test.multi'] = 2;
		});
		$e->flush('foo');

		$this->assertEquals(2, $GLOBALS['__event.test.multi']);

		unset($GLOBALS['__event.test.multi']);		
	}


	public function testFlushingWithoutPayloads()
	{
		$e = new Dispatcher;
		$e->flusher('foo', function() {});
		$e->flush('foo');
		// If we didn't have an exception the test passed
		$this->assertTrue(true);
	}


	public function testGlobalEventCalledForAllEvents()
	{
		$_SERVER['__event.test'] = 0;
		$e = new Dispatcher;
		$e->listen('*', function()
		{
			$_SERVER['__event.test']++;
		});
		$e->listen('*', function()
		{
			$_SERVER['__event.test']++;
		});
		$e->listen('foo', function()
		{
			$_SERVER['__event.test']++;
		});
		$e->fire('foo');
		$this->assertEquals(3, $_SERVER['__event.test']);
		unset($_SERVER['__event.test']);
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testListenException()
	{
		$e = new Dispatcher;
		$e->listen('foo', 'adlkasd');
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testFlusherException()
	{
		$e = new Dispatcher;
		$e->flusher('foo', 'adslkadf');
	}

}