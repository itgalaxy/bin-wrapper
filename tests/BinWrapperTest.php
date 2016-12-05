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
        $platform = strtolower(PHP_OS);
        $binWrapper = new BinWrapper();
        $binWrapper->src('http://foo.com/bar.tar.gz', $platform);

        $this->assertEquals($platform, $binWrapper->src()[0]['os']);
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
        $platform = strtolower(PHP_OS);
        $fs = new Filesystem();

        // Create a mock and queue two responses.
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    file_get_contents(
                        __DIR__ . '/fixtures/gifsicle-' . $platform . '.tar.gz'
                    )
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
            ->using($platform === 'winnt' ? 'gifsicle.exe' : 'gifsicle');

        $binWrapper->run();
        $this->assertTrue(file_exists($binWrapper->path()));
        $fs->remove($binWrapper->dest());
    }

    public function testMeetTheDesiredVersion()
    {
        $platform = strtolower(PHP_OS);
        $fs = new Filesystem();

        // Create a mock and queue two responses.
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    file_get_contents(
                        __DIR__ . '/fixtures/gifsicle-' . $platform . '.tar.gz'
                    )
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
            ->using($platform === 'winnt' ? 'gifsicle.exe' : 'gifsicle')
            ->version('>=1.71');

        $binWrapper->run();
        $this->assertTrue(file_exists($binWrapper->path()));
        $fs->remove($binWrapper->dest());
    }

    public function testDownloadFilesEvenIfTheyAreNotUsed()
    {
        $platform = strtolower(PHP_OS);
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
                    file_get_contents(__DIR__ . '/fixtures/gifsicle-winnt.tar.gz')
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
            ->src('http://foo.com/gifsicle-winnt.tar.gz')
            ->src('http://foo.com/test.php')
            ->dest($tempDirectory)
            ->using($platform === 'winnt' ? 'gifsicle.exe' : 'gifsicle');

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
        $platform = strtolower(PHP_OS);
        $fs = new Filesystem();

        // Create a mock and queue two responses.
        $mock = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    file_get_contents(
                        __DIR__ . '/fixtures/gifsicle-' . $platform . '.tar.gz'
                    )
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
            ->using(str_replace(
                ' ',
                '',
                $platform === 'winnt' ? 'gifsicle.exe' : 'gifsicle'
            ));

        $binWrapper->run(['--shouldNotFailAnyway']);
        $this->assertTrue(file_exists($binWrapper->path()));
        $fs->remove($binWrapper->dest());
    }

    public function testErrorIfNoBinaryIsFoundAndNoSourceIsProveded()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No binary found matching your system. It\'s probably not supported.');

        $platform = strtolower(PHP_OS);
        $tempDirectory = $this->getTempDirectory();

        $binWrapper = new BinWrapper();
        $binWrapper
            ->dest($tempDirectory)
            ->using($platform === 'winnt' ? 'gifsicle.exe' : 'gifsicle');

        $binWrapper->run();
    }

    public function testErrorIfRequestReturnNon200Code()
    {
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);

        $platform = strtolower(PHP_OS);

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
            ->using($platform === 'winnt' ? 'gifsicle.exe' : 'gifsicle');

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
