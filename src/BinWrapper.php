<?php
namespace BinWrapper;

use BinCheck\BinCheck;
use BinVersionCheck\BinVersionCheck;
use GuzzleHttp\Client;
use Mmoreram\Extractor\Extractor;
use Mmoreram\Extractor\Filesystem\SpecificDirectory;
use Mmoreram\Extractor\Resolver\ExtensionResolver;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class BinWrapper
{
    private $options = [
        'skipCheck' => false,
        'guzzleClientOptions' => []
    ];

    private $src = [];

    private $dest = null;

    private $using = null;

    private $version = null;

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function src($src = null, $os = null, $arch = null)
    {
        if (func_num_args() == 0) {
            return $this->src;
        }

        array_push($this->src, [
            'url' => $src,
            'os' => strtolower($os),
            'arch' => strtolower($arch)
        ]);

        return $this;
    }

    public function dest($dest = null)
    {
        if (func_num_args() == 0) {
            return $this->dest;
        }

        // Compat with windows
        $this->dest = rtrim($dest, '/\\');

        return $this;
    }

    public function using($bin = null)
    {
        if (func_num_args() == 0) {
            return $this->using;
        }

        $this->using = $bin;

        return $this;
    }

    public function version($range = null)
    {
        if (func_num_args() == 0) {
            return $this->version;
        }

        $this->version = $range;

        return $this;
    }

    public function path()
    {
        return Path::canonicalize(implode('/', [
            $this->dest(),
            $this->using()
        ]));
    }

    public function download()
    {
        $osFilter = new \OsFilter\OsFilter();
        $files = $osFilter->find($this->src());

        if (count($files) === 0) {
            throw new \Exception('No binary found matching your system. It\'s probably not supported.');
        }

        $client = new Client($this->options['guzzleClientOptions']);
        $fs = new Filesystem();

        array_walk(
            $files,
            function ($file) use ($client, $fs) {
                $sink = $this->dest() . '/' . basename($file['url']);
                $client->request(
                    'GET',
                    $file['url'],
                    [
                        'sink' => $sink
                    ]
                );

                $fs->chmod($sink, 0755);

                if (!Path::hasExtension(
                    $sink,
                    [
                        'rar',
                        'zip',
                        'phar',
                        'tar',
                        'gz',
                        'bz2'
                    ]
                )) {
                    return;
                }

                $specificDirectory = new SpecificDirectory($this->dest());
                $extensionResolver = new ExtensionResolver();
                $extractor = new Extractor(
                    $specificDirectory,
                    $extensionResolver
                );

                $files = $extractor->extractFromFile($sink);

                unlink($sink);

                foreach ($files as $file) {
                    $fs->chmod($file->getRealpath(), 0755);
                }
            }
        );
    }

    public function findExisting()
    {
        $fileExist = file_exists($this->path());

        if (!$fileExist) {
            $this->download();
        }
    }

    public function runCheck($cmd)
    {
        BinCheck::check($this->path(), $cmd);

        if ($this->version()) {
            BinVersionCheck::check($this->path(), $this->version());
        }
    }

    public function run($cmd = null)
    {
        if (!$cmd) {
            $cmd = ['--version'];
        }

        $this->findExisting();

        if ($this->options['skipCheck']) {
            return true;
        }

        $this->runCheck($cmd);
    }
}
