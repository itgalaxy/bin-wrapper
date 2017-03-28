<?php
namespace Itgalaxy\BinWrapper;

use GuzzleHttp\Client;
use Itgalaxy\BinVersionCheck\BinVersionCheck;
use Itgalaxy\OsFilter\OsFilter;
use Mmoreram\Extractor\Extractor;
use Mmoreram\Extractor\Filesystem\SpecificDirectory;
use Mmoreram\Extractor\Resolver\ExtensionResolver;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class BinWrapper
{
    private $src = [];

    private $dest = null;

    private $using = null;

    private $version = null;

    private $options = [
        'skipCheck' => false,
        'guzzleClientOptions' => []
    ];

    private $fs = null;

    public function __construct($options = [])
    {
        $this->options = array_merge_recursive($this->options, $options);
        $this->fs = new Filesystem();
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

    public function run($cmd = null)
    {
        if (!$cmd) {
            $cmd = ['--version'];
        }

        $isFind = $this->findExisting();

        if (!$isFind) {
            throw new \Exception('Binary not found and not possible to download');
        }

        if ($this->options['skipCheck']) {
            return true;
        }

        return $this->runCheck($cmd);
    }

    public function runCheck($cmd)
    {
        $isBinChecked = $this->binCheck($this->path(), $cmd);

        if (!$isBinChecked) {
            throw new \Exception('The `' . $this->path() . '` binary doesn\'t seem to work correctly');
        }

        if ($this->version()) {
            BinVersionCheck::check($this->path(), $this->version());
        }

        return true;
    }

    public function findExisting()
    {
        $path = $this->path();
        $fileExist = $this->fs->exists($path) && is_file($path);

        if (!$fileExist) {
            $this->download();
        }

        return true;
    }

    public function download()
    {
        $files = OsFilter::find($this->src());

        if (count($files) === 0) {
            throw new \Exception('No binary found matching your system. It\'s probably not supported.');
        }

        $client = new Client($this->options['guzzleClientOptions']);
        $fs = $this->fs;

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

    // Todo move to separatly repository
    private function binCheck($bin, $args = ['--help'])
    {
        $isExecutable = is_executable($bin);

        if (!$isExecutable) {
            throw new \Exception(
                'Couldn\'t execute the ' . $bin . ' binary. Make sure it has the right permissions.'
            );
        }

        exec($bin . ' ' . implode(' ', $args), $output, $returnVar);

        if ($returnVar !== 0) {
            return false;
        }

        return true;
    }
}
