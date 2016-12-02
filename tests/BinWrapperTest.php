<?php
namespace BinWrapper\Tests;

use BinWrapper\BinWrapper;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class BinWrapperTest extends TestCase
{
    public function testAddASource()
    {
        $binWrapper = new BinWrapper();
        $binWrapper->src('http://foo.com/bar.tar.gz');

        $this->assertEquals('http://foo.com/bar.tar.gz', $binWrapper->src()[0]['url']);
    }

    public function testAddASourceToASpecificOs()
    {
        $binWrapper = new BinWrapper();
        $binWrapper->src('http://foo.com/bar.tar.gz', php_uname('s'));

        $this->assertEquals(strtolower(php_uname('s')), $binWrapper->src()[0]['os']);
    }

    public function testSetDestinationDirectory()
    {
        $binWrapper = new BinWrapper();
        $binWrapper->dest(__DIR__ . '/foo');

        $this->assertEquals(__DIR__ . '/foo', $binWrapper->dest());
    }

    public function testSetWhichFileToUseAsTheBinary()
    {
        $binWrapper = new BinWrapper();
        $binWrapper->using('foo');

        $this->assertEquals('foo', $binWrapper->using());
    }

    public function testSetAVersionRangeToTestAgainst()
    {
        $binWrapper = new BinWrapper();
        $binWrapper->version('1.0.0');

        $this->assertEquals('1.0.0', $binWrapper->version());
    }

    public function testGetTheBinaryPath()
    {
        $binWrapper = new BinWrapper();
        $binWrapper->dest('tmp')->using('foo');

        $this->assertEquals('tmp/foo', $binWrapper->path());
    }

    public function testVerifyThatABinaryIsWorking()
    {
        $fs = new Filesystem();

        // Create a mock and queue two responses.
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    file_get_contents(__DIR__ . '/fixtures/gifsicle-' . strtolower(php_uname('s')) . '.tar.gz')
                )
            ]
        );
        $handler = HandlerStack::create($mock);

        $tempDirectory = $this->getTempDirectory();

        $binWrapper = new BinWrapper([
            'guzzleClientOptions' => [
                'handler' => $handler
            ]
        ]);
        $binWrapper
            ->src('http://foo.com/gifsicle.tar.gz')
            ->dest($tempDirectory)
            ->using(strtolower(php_uname('s')) === 'window nt' ? 'gifsicle.exe' : 'gifsicle');

        $binWrapper->run();
        $this->assertTrue(file_exists($binWrapper->path()));
        $fs->remove($binWrapper->dest());
    }

    public function testMeetTheDesiredVersion()
    {
        $fs = new Filesystem();

        // Create a mock and queue two responses.
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    file_get_contents(__DIR__ . '/fixtures/gifsicle-' . strtolower(php_uname('s')) . '.tar.gz')
                )
            ]
        );
        $handler = HandlerStack::create($mock);

        $tempDirectory = $this->getTempDirectory();

        $binWrapper = new BinWrapper([
            'guzzleClientOptions' => [
                'handler' => $handler
            ]
        ]);
        $binWrapper
            ->src('http://foo.com/gifsicle.tar.gz')
            ->dest($tempDirectory)
            ->using(strtolower(php_uname('s')) === 'window nt' ? 'gifsicle.exe' : 'gifsicle')
            ->version('>=1.71');

        $binWrapper->run();
        $this->assertTrue(file_exists($binWrapper->path()));
        $fs->remove($binWrapper->dest());
    }

    public function testDownloadFilesEvenIfTheyAreNotUsed()
    {
        $fs = new Filesystem();

        // Create a mock and queue two responses.
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    file_get_contents(__DIR__ . '/fixtures/gifsicle-darwin.tar.gz')
                ),
                new Response(
                    200,
                    [],
                    file_get_contents(__DIR__ . '/fixtures/gifsicle-win32.tar.gz')
                ),
                new Response(
                    200,
                    [],
                    file_get_contents(__DIR__ . '/fixtures/test.php')
                )
            ]
        );
        $handler = HandlerStack::create($mock);

        $tempDirectory = $this->getTempDirectory();

        $binWrapper = new BinWrapper([
            'skipCheck' => true,
            'guzzleClientOptions' => [
                'handler' => $handler
            ]
        ]);
        $binWrapper
            ->src('http://foo.com/gifsicle-darwin.tar.gz')
            ->src('http://foo.com/gifsicle-win32.tar.gz')
            ->src('http://foo.com/test.php')
            ->dest($tempDirectory)
            ->using(strtolower(php_uname('s')) === 'window nt' ? 'gifsicle.exe' : 'gifsicle');

        $binWrapper->run();
        $files = scandir($binWrapper->dest());
        $this->assertTrue(count($files) === 5);
        $this->assertEquals('gifsicle', $files[2]);
        $this->assertEquals('gifsicle.exe', $files[3]);
        $this->assertEquals('test.php', $files[4]);
        $fs->remove($binWrapper->dest());
    }

    public function testSkipRunningBinaryCheck()
    {
        $fs = new Filesystem();

        // Create a mock and queue two responses.
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    file_get_contents(__DIR__ . '/fixtures/gifsicle-' . strtolower(php_uname('s')) . '.tar.gz')
                ),
            ]
        );
        $handler = HandlerStack::create($mock);

        $tempDirectory = $this->getTempDirectory();

        $binWrapper = new BinWrapper([
            'skipCheck' => true,
            'guzzleClientOptions' => [
                'handler' => $handler
            ]
        ]);
        $binWrapper
            ->src('http://foo.com/gifsicle.tar.gz')
            ->dest($tempDirectory)
            ->using(strtolower(php_uname('s')) === 'window nt' ? 'gifsicle.exe' : 'gifsicle');

        $binWrapper->run(['--shouldNotFailAnyway']);
        $this->assertTrue(file_exists($binWrapper->path()));
        $fs->remove($binWrapper->dest());
    }

    public function testErrorIfNoBinaryIsFoundAndNoSourceIsProveded()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No binary found matching your system. It\'s probably not supported.');

        $tempDirectory = $this->getTempDirectory();

        $binWrapper = new BinWrapper();
        $binWrapper
            ->dest($tempDirectory)
            ->using(strtolower(php_uname('s')) === 'window nt' ? 'gifsicle.exe' : 'gifsicle');

        $binWrapper->run();
    }

    public function testErrorIfRequestReturnNon200Code()
    {
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        // Create a mock and queue two responses.
        $mock = new MockHandler(
            [
                new Response(
                    404,
                    []
                ),
            ]
        );
        $handler = HandlerStack::create($mock);

        $tempDirectory = $this->getTempDirectory();

        $binWrapper = new BinWrapper([
            'skipCheck' => true,
            'guzzleClientOptions' => [
                'handler' => $handler
            ]
        ]);
        $binWrapper
            ->src('http://foo.com/gifsicle.tar.gz')
            ->dest($tempDirectory)
            ->using(strtolower(php_uname('s')) === 'window nt' ? 'gifsicle.exe' : 'gifsicle');

        $binWrapper->run();
    }

    private function getTempDirectory()
    {
        $tempDirectory = tempnam(sys_get_temp_dir(), 'bin-wrapper-');
        unlink($tempDirectory);
        mkdir($tempDirectory);

        return $tempDirectory;
    }
}
