<?php

namespace Kirby\Toolkit;

use Exception;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kirby\Toolkit\View
 */
class ViewTest extends TestCase
{
	protected string $fixtures = __DIR__ . '/fixtures/view';

	protected function view(array $data = [])
	{
		return new View($this->fixtures . '/view.php', $data);
	}

	/**
	 * @covers ::__construct
	 * @covers ::data
	 */
	public function testData()
	{
		$view = $this->view();
		$this->assertSame([], $view->data());

		$view = $this->view(['test']);
		$this->assertSame(['test'], $view->data());
	}

	/**
	 * @covers ::exists
	 */
	public function testExists()
	{
		$view = $this->view();
		$this->assertTrue($view->exists());

		$view = new View($this->fixtures . '/foo.php');
		$this->assertFalse($view->exists());
	}

	/**
	 * @covers ::__construct
	 * @covers ::file
	 */
	public function testFile()
	{
		$view = $this->view();
		$this->assertSame($this->fixtures . '/view.php', $view->file());
	}

	/**
	 * @covers ::render
	 */
	public function testRender()
	{
		$view = $this->view(['name' => 'Homer']);
		$this->assertSame('Hello Homer', $view->render());
	}

	/**
	 * @covers ::render
	 */
	public function testRenderWithMissingFile()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('The view does not exist: invalid-file.php');

		$view = new View('invalid-file.php');
		$view->render();
	}

	/**
	 * @covers ::render
	 */
	public function testRenderWithException()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('View exception');

		$view = new View($this->fixtures . '/view-with-exception.php');
		$view->render();
	}

	/**
	 * @covers ::toString
	 * @covers ::__toString
	 */
	public function testToString()
	{
		$view = $this->view(['name' => 'Tester']);
		$this->assertSame('Hello Tester', $view->toString());
		$this->assertSame('Hello Tester', $view->__toString());
		$this->assertSame('Hello Tester', (string)$view);
	}
}
