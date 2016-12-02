<?php
namespace BinWrapper;

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

        $this->dest = rtrim($dest, '/');

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
        $files = $this->osFilterObj($this->src());

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
        $this->binCheck($this->path(), $cmd);

        // if ($this->version()) {
        // $this->binVersionCheck($this->path(), $this->version());
        // }
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

    private function binCheck($bin, $args = ['--help'])
    {
        $isExecutable = is_executable($bin);

        if (!$isExecutable) {
            throw new \Exception(
                'Couldn\'t execute the `' . $bin . '` binary. Make sure it has the right permissions.'
            );
        }

        exec($bin . ' ' . implode(' ', $args), $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('The `' . $this->path() . '` binary doesn\'t seem to work correctly');
        }
    }

    private function binVersionCheck($bin, $semverRange, $opts = null)
    {
        // Need implemented
    }

    private function osFilterObj($arr)
    {
        $arch = null;
        $platform = strtolower(php_uname('s'));

        if (!empty(strstr(php_uname('m'), '64'))) {
            $arch = 'x64';
        } else {
            $arch = 'x86';
        }

        if (!$arr || count($arr) == 0) {
            return [];
        }

        return array_filter(
            $arr,
            function ($obj) use ($arch, $platform) {
                if ($obj['os'] == $platform && $obj['arch'] == $arch) {
                    unset($obj['os']);
                    unset($obj['arch']);

                    return $obj;
                } elseif ($obj['os'] == $platform && !$obj['arch']) {
                    unset($obj['os']);

                    return $obj;
                } elseif ($obj['arch'] == $arch && !$obj['os']) {
                    unset($obj['arch']);

                    return $obj;
                } elseif (!$obj['os'] && !$obj['arch']) {
                    return $obj;
                }
            }
        );
    }
}
