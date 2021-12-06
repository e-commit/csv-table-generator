<?php

declare(strict_types=1);

/*
 * This file is part of the csv-table-generator package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CsvTableGenerator;

class Csv
{
    public const EOL_LF = 'LF';
    public const EOL_CRLF = 'CR+LF';

    protected $handle;
    protected $pathDir;
    protected $filename;
    protected $currentPathname;
    protected $header;
    protected $maxLines;
    protected $delimiter;
    protected $enclosure;
    protected $eol;
    protected $escape;
    protected $fileNumber = 0;
    protected $lines = 0;
    protected $totalLines = 0;
    protected $unixToDos = false;
    protected $unixToDosPath;
    protected $addUtf8Bom = false;

    /**
     * Constructor.
     *
     * @param string $pathDir  Path folder
     * @param string $filename Filename (without path folder and extension)
     * @param array  $options  See README.md
     */
    public function __construct($pathDir, $filename, $options = [])
    {
        $defaultOptions = [
            'header' => [],
            'max_lines' => null,
            'delimiter' => ',',
            'enclosure' => '"',
            'eol' => self::EOL_LF,
            'escape' => '\\',
            'unix2dos_path' => '/usr/bin/unix2dos',
            'add_utf8_bom' => false,
        ];
        $options = array_merge($defaultOptions, $options);

        //Test folder
        $realPath = realpath($pathDir);
        if (false === $realPath || !is_writable($realPath)) {
            throw new \Exception(sprintf('Folder %s does not exist or is not writable', $pathDir));
        }

        $this->pathDir = $realPath;
        $this->filename = $filename;
        $this->header = $options['header'];
        if (empty($options['max_lines'])) {
            $this->maxLines = null;
        } else {
            $this->maxLines = (int) $options['max_lines'];
        }
        $this->delimiter = $options['delimiter'];
        $this->enclosure = $options['enclosure'];
        $this->eol = $options['eol'];
        $this->escape = $options['escape'];

        if (self::EOL_CRLF === $options['eol'] && \PHP_VERSION_ID < 80100) { //PHP < 8.1
            $this->unixToDos = true;
        }
        $this->unixToDosPath = $options['unix2dos_path'];

        $this->addUtf8Bom = $options['add_utf8_bom'];

        $this->open();
    }

    /**
     * Open CSV file.
     */
    protected function open(): void
    {
        if ($this->handle) {
            throw new \Exception(sprintf('The file %s is already open', $this->filename));
        }
        ++$this->fileNumber;
        $this->lines = 0;
        $filename = $this->filename;
        if ($this->fileNumber > 1) {
            $filename .= '-'.$this->fileNumber;
        }
        $this->currentPathname = $this->pathDir.'/'.$filename.'.csv';
        $this->handle = fopen($this->currentPathname, 'wb'); //Binary is forced. EOL = "\n"
        if (false === $this->handle) {
            throw new \Exception(sprintf('Error during the opening of the %s file', $this->filename));
        }
        if ($this->addUtf8Bom) {
            if (!fwrite($this->handle, \chr(0xEF).\chr(0xBB).\chr(0xBF))) {
                throw new \Exception(sprintf('Error during the UTF8-BOM writing in %s file', $this->filename));
            }
        }
        if (!empty($this->header)) {
            $this->write($this->header);
            $this->lines = 0;
            --$this->totalLines;
        }
    }

    /**
     * Close CSV file.
     */
    public function close(): void
    {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;

            if ($this->unixToDos) { //PHP < 8.1
                if (\PHP_OS_FAMILY === 'Linux') {
                    $command = sprintf('%s %s 2> /dev/null', $this->unixToDosPath, $this->currentPathname);
                } else {
                    $command = sprintf('%s %s', $this->unixToDosPath, $this->currentPathname);
                }
                exec($command, $output, $returnVar);
                if (0 !== $returnVar) {
                    throw new \Exception(sprintf('Unix2dos error (%s file)', $this->filename));
                }
            }
            $this->currentPathname = null;
        }
    }

    /**
     * Create new CSV file.
     */
    protected function newFile(): void
    {
        $this->close();
        $this->open();
    }

    /**
     * Add line in CSV file.
     *
     * @param array $data
     */
    public function write($data): void
    {
        if (!$this->handle) {
            throw new \Exception(sprintf('Handle does not exist. File %s', $this->filename));
        }

        //New file
        if ($this->maxLines && $this->maxLines == $this->lines) {
            $this->newFile();
        }

        //Write
        if (\PHP_VERSION_ID >= 80100) { //PHP >= 8.1
            $eol = (self::EOL_CRLF === $this->eol) ? "\r\n" : "\n";
            $result = fputcsv($this->handle, $data, $this->delimiter, $this->enclosure, $this->escape, $eol);
        } else { //PHP < 8.1
            $result = fputcsv($this->handle, $data, $this->delimiter, $this->enclosure, $this->escape);
        }
        if (false === $result) {
            throw new \Exception(sprintf('Error during the writing in %s file', $this->filename));
        }

        ++$this->lines;
        ++$this->totalLines;
    }

    public function getTotalLines(): int
    {
        return $this->totalLines;
    }

    /**
     * Gets the path to the current file.
     *
     * @return string
     */
    public function getCurrentPathname(): ?string
    {
        return $this->currentPathname;
    }
}
