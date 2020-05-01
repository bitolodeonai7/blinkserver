<?php

namespace blink\tests\log;

use blink\core\Exception;
use blink\log\Logger;
use blink\tests\TestCase;
use blink\log\StreamTarget;
use Psr\Log\LogLevel;
use blink\core\Application;
use blink\core\ErrorHandler;
use blink\http\Request;

class LoggerTest extends TestCase
{
    protected $logFile;

    public function setUp(): void
    {

        parent::setUp();

        $this->logFile = __DIR__ . '/test.log';
    }

    public function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        parent::tearDown();
    }

    /**
     * @param $logFile
     * @return Logger
     */
    protected function createLogger($logFile)
    {
        return new Logger([
            'targets' => [
                'test' => [
                    'class' => StreamTarget::class,
                    'stream' => $logFile,
                ]
            ]
        ]);
    }

    public function testLogBasic()
    {
        $log = $this->createLogger($this->logFile);

        $this->assertEquals(LogLevel::NOTICE, $log->targets['test']->level);

        $log->alert('alert message');
        $log->info('info message');

        $content = file_get_contents($this->logFile);

        $this->assertTrue(strpos($content, 'alert message') !== false);
        $this->assertFalse(strpos($content, 'info message') !== false);
    }

    public function testLogException()
    {
        $log = $this->createLogger($this->logFile);

        $log->alert(new Exception('my exception'));

        $this->assertTrue(true);
    }

    protected function createApp($callback = null)
    {
        $app = new Application([
            'root' => '.',
            'services' => [
                'log' => $this->createLogger($this->logFile)
            ]
        ]);
        if ($callback) {
            $app->route('GET', '/', $callback);
        }
        $app->bootstrapIfNeeded();

        return $app;
    }

    public function testErrorHandlerDefaults()
    {
        $app = $this->createApp();

        $this->assertInstanceOf(ErrorHandler::class, $app->get('errorHandler'));
        $this->assertInstanceOf(Logger::class, $app->get('log'));
    }

    public function testHandlerError()
    {
        $func = function () {
            $a = [];
            return $a['undefined'];
        };
        $app = $this->createApp($func);
        $response = $app->handle(new Request(['method' => 'GET', 'uri' => '/']));

        $this->assertEquals(['name', 'message', 'code', 'file', 'line', 'trace'], array_keys($response->data));
    }

    public function testHandlerException()
    {
        $func = function () {
            throw new \LogicException('test exception');
        };
        $app = $this->createApp($func);
        $response = $app->handle(new Request(['method' => 'GET', 'uri' => '/']));

        $this->assertEquals(['name', 'message', 'code', 'file', 'line', 'trace'], array_keys($response->data));
    }
}
