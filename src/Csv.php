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

use Symfony\Component\OptionsResolver\OptionsResolver;

class Csv
{
    public const EOL_LF = 'LF';
    public const EOL_CRLF = 'CR+LF';

    /**
     * @var resource|false|null
     *
     * @psalm-var resource|closed-resource|false|null
     */
    protected mixed $handle = null;

    protected string $pathDir;
    protected string $filename;
    protected string $currentPathname;

    /**
     * @var array<string, string>|null
     */
    protected ?array $header;

    protected ?int $maxLines;
    protected string $delimiter;
    protected string $enclosure;
    protected string $eol;
    protected string $escape;
    protected int $fileNumber = 0;
    protected int $lines = 0;
    protected int $totalLines = 0;
    protected bool $addUtf8Bom = false;

    /**
     * Constructor.
     *
     * @param string $pathDir  Path folder
     * @param string $filename Filename (without path folder and extension)
     * @param array  $options  See README.md
     */
    public function __construct(string $pathDir, string $filename, array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'header' => null,
            'max_lines' => null,
            'delimiter' => ',',
            'enclosure' => '"',
            'eol' => self::EOL_LF,
            'escape' => '',
            'add_utf8_bom' => false,
        ]);
        $resolver->setAllowedTypes('header', ['null', 'array']);
        $resolver->setAllowedTypes('max_lines', ['null', 'int']);
        $resolver->setAllowedTypes('delimiter', 'string');
        $resolver->setAllowedTypes('enclosure', 'string');
        $resolver->setAllowedValues('eol', [self::EOL_LF, self::EOL_CRLF]);
        $resolver->setAllowedTypes('escape', 'string');
        $resolver->setAllowedTypes('add_utf8_bom', 'bool');
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        // Test folder
        $realPath = realpath($pathDir);
        if (false === $realPath || !is_writable($realPath)) {
            throw new \Exception(\sprintf('Folder %s does not exist or is not writable', $pathDir));
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

        $this->addUtf8Bom = $options['add_utf8_bom'];

        $this->open();
    }

    /**
     * Open CSV file.
     */
    protected function open(): void
    {
        if (\is_resource($this->handle)) {
            throw new \Exception(\sprintf('The file %s is already open', $this->filename));
        }
        ++$this->fileNumber;
        $this->lines = 0;
        $filename = $this->filename;
        if ($this->fileNumber > 1) {
            $filename .= '-'.$this->fileNumber;
        }
        $this->currentPathname = $this->pathDir.'/'.$filename.'.csv';
        $this->handle = fopen($this->currentPathname, 'wb'); // Binary is forced. EOL = "\n"
        if (false === $this->handle) {
            throw new \Exception(\sprintf('Error during the opening of the %s file', $this->filename));
        }
        if ($this->addUtf8Bom) {
            if (false === fwrite($this->handle, \chr(0xEF).\chr(0xBB).\chr(0xBF))) {
                throw new \Exception(\sprintf('Error during the UTF8-BOM writing in %s file', $this->filename));
            }
        }
        if (null !== $this->header && \count($this->header) > 0) {
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
        if (\is_resource($this->handle)) {
            fclose($this->handle);
            $this->handle = null;
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
        if (!\is_resource($this->handle)) {
            throw new \Exception(\sprintf('Handle does not exist. File %s', $this->filename));
        }

        // New file
        if (null !== $this->maxLines && $this->maxLines == $this->lines) {
            $this->newFile();
        }

        // Write
        $eol = (self::EOL_CRLF === $this->eol) ? "\r\n" : "\n";
        /** @psalm-suppress TooManyArguments */
        $result = fputcsv($this->handle, $data, $this->delimiter, $this->enclosure, $this->escape, $eol);
        if (false === $result) {
            throw new \Exception(\sprintf('Error during the writing in %s file', $this->filename));
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
     */
    public function getCurrentPathname(): ?string
    {
        return $this->currentPathname;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
    }
}
