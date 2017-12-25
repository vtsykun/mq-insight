<?php
declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Storage;

use Okvpn\Bundle\MQInsightBundle\Model\AppConfig;

class FileSystemStorage implements KeyValueStorageInterface
{
    /** @var null|string */
    protected $fileNameDir;

    /**
     * @param null $baseDir
     */
    public function __construct($baseDir = null)
    {
        if ($baseDir === null) {
            $baseDir = sys_get_temp_dir();
        }

        $this->fileNameDir = $baseDir . DIRECTORY_SEPARATOR . (string) AppConfig::getApplicationID();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        $this->init();
        $content = @file_get_contents($this->getFileName($key));
        if ($content === false) {
            return null;
        }

        $data = unserialize($content);
        return $data[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value)
    {
        $this->init();
        $fileName = $this->getFileName($key);
        $content = @file_get_contents($fileName);
        if ($content !== false) {
            $data = unserialize($content);
        }

        $data[$key] = $value;

        if (false === @file_put_contents($fileName, serialize($data))) {
            throw new \RuntimeException(sprintf('Failed to write file "%s".', $fileName));
        }

        @chmod($fileName, 0777);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key)
    {
        $this->init();

        return @unlink($this->getFileName($key));
    }

    protected function init()
    {
        if (!file_exists($this->fileNameDir)) {
            @mkdir($this->fileNameDir, 0777, true);
            @chmod($this->fileNameDir, 0777);
        }
    }

    protected function getFileName(string $key): string
    {
        return  $this->fileNameDir . DIRECTORY_SEPARATOR . $key . '.txt';
    }
}
