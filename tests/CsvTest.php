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

namespace Ecommit\CsvTableGenerator\Tests;

use Ecommit\CsvTableGenerator\Csv;
use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase
{
    protected $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir().'/test-csv';
        $this->deleteDir();
        mkdir($this->path);
    }

    protected function tearDown(): void
    {
        $this->deleteDir();
    }

    protected function deleteDir(): void
    {
        if (!file_exists($this->path)) {
            return;
        }

        $files = glob($this->path.'/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->deleteDir($file) : unlink($file);
        }
        rmdir($this->path);
    }

    public function testWithDefaultOption(): void
    {
        $csv = $this->createCsv();
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['c"c', 'd\"d']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '"a a",bb',
            'cc,dd',
            '"c""c","d\""d"',
        ]);
    }

    public function testWithHeaderOption(): void
    {
        $csv = $this->createCsv([
            'header' => ['col1', 'col2'],
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            'col1,col2',
            '"a a",bb',
            'cc,dd',
        ]);
    }

    public function testWithMaxLinesOptionBigValue(): void
    {
        $csv = $this->createCsv([
            'max_lines' => 10,
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['ee', 'ff']);
        $csv->write(['gg', 'hh']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '"a a",bb',
            'cc,dd',
            'ee,ff',
            'gg,hh',
        ]);
    }

    public function testWithMaxLinesOptionSmallValue(): void
    {
        $csv = $this->createCsv([
            'max_lines' => 3,
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['ee', 'ff']);
        $csv->write(['gg', 'hh']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '"a a",bb',
            'cc,dd',
            'ee,ff',
        ]);
        $this->assertCsvFile('my-csv-2.csv', [
            'gg,hh',
        ]);
    }

    public function testWithMaxLinesOptionSmallValueAndHeader(): void
    {
        $csv = $this->createCsv([
            'max_lines' => 3,
            'header' => ['col1', 'col2'],
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['ee', 'ff']);
        $csv->write(['gg', 'hh']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            'col1,col2',
            '"a a",bb',
            'cc,dd',
            'ee,ff',
        ]);
        $this->assertCsvFile('my-csv-2.csv', [
            'col1,col2',
            'gg,hh',
        ]);
    }

    public function testWithDelimiterOption(): void
    {
        $csv = $this->createCsv([
            'delimiter' => ';',
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '"a a";bb',
            'cc;dd',
        ]);
    }

    public function testWithEnclosureOption(): void
    {
        $csv = $this->createCsv([
            'enclosure' => '#',
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '#a a#,bb',
            'cc,dd',
        ]);
    }

    public function testWithEolOption(): void
    {
        $csv = $this->createCsv([
            'eol' => Csv::EOL_CRLF,
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '"a a",bb',
            'cc,dd',
        ], true);
    }

    public function testWithEscapeOption(): void
    {
        $csv = $this->createCsv([
            'escape' => '@',
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['c"c', 'd@"d']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '"a a",bb',
            'cc,dd',
            '"c""c","d@"d"',
        ]);
    }

    public function testWithEmptyEscapeOption(): void
    {
        $csv = $this->createCsv([
            'escape' => '',
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['c"c', 'd\"d']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '"a a",bb',
            'cc,dd',
            '"c""c","d\""d"',
        ]);
    }

    public function testWithAddUtf8BomOption(): void
    {
        $csv = $this->createCsv([
            'add_utf8_bom' => true,
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->close();

        $this->assertCsvFile('my-csv.csv', [
            '"a a",bb',
            'cc,dd',
        ], false, \chr(0xEF).\chr(0xBB).\chr(0xBF));
    }

    public function testWithFakePath(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Folder fake does not exist or is not writable');

        $csv = new Csv('fake', 'my-csv');
    }

    public function testWithWithClosedFile(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Handle does not exist. File my-csv');

        $csv = $this->createCsv();
        $csv->write(['a a', 'bb']);
        $csv->close();
        $csv->write(['cc', 'dd']);
    }

    public function testGetTotalLines(): Csv
    {
        $csv = $this->createCsv([
            'max_lines' => 3,
        ]);

        $this->assertSame(0, $csv->getTotalLines());

        return $csv;
    }

    /**
     * @depends testGetTotalLines
     */
    public function testGetTotalLinesAfterWrite(Csv $csv): Csv
    {
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['ee', 'ff']);

        $this->assertSame(3, $csv->getTotalLines());

        return $csv;
    }

    /**
     * @depends testGetTotalLinesAfterWrite
     */
    public function testGetTotalLinesAfterWriteInSecondFile(Csv $csv): void
    {
        $csv->write(['gg', 'hh']);
        $csv->write(['ii', 'jj']);
        $csv->close();

        $this->assertSame(5, $csv->getTotalLines());
    }

    public function testGetTotalLinesWithHeader(): Csv
    {
        $csv = $this->createCsv([
            'max_lines' => 3,
            'header' => ['col1', 'col2'],
        ]);

        $this->assertSame(0, $csv->getTotalLines());

        return $csv;
    }

    /**
     * @depends testGetTotalLinesWithHeader
     */
    public function testGetTotalLinesWithHeaderAfterWrite(Csv $csv): Csv
    {
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['ee', 'ff']);

        $this->assertSame(3, $csv->getTotalLines());

        return $csv;
    }

    /**
     * @depends testGetTotalLinesWithHeaderAfterWrite
     */
    public function testGetTotalLinesWithHeaderAfterWriteInSecondFile(Csv $csv): void
    {
        $csv->write(['gg', 'hh']);
        $csv->write(['ii', 'jj']);
        $csv->close();

        $this->assertSame(5, $csv->getTotalLines());
    }

    public function testGetCurrentPathname(): Csv
    {
        $csv = $this->createCsv([
            'max_lines' => 3,
        ]);
        $csv->write(['a a', 'bb']);
        $csv->write(['cc', 'dd']);
        $csv->write(['ee', 'ff']);

        $this->assertSame($this->path.'/my-csv.csv', $csv->getCurrentPathname());

        return $csv;
    }

    /**
     * @depends testGetCurrentPathname
     */
    public function testGetCurrentPathnameSecondFile(Csv $csv): void
    {
        $csv->write(['gg', 'hh']);
        $csv->write(['ii', 'jj']);

        $this->assertSame($this->path.'/my-csv-2.csv', $csv->getCurrentPathname());

        $csv->close();
    }

    protected function createCsv(array $options = []): Csv
    {
        return new Csv($this->path, 'my-csv', $options);
    }

    protected function assertCsvFile(string $filename, array $expectedRows, bool $useCrlf = false, string $beforeContent = ''): void
    {
        $this->assertFileExists($this->path.'/'.$filename, 'File not found');
        $content = file_get_contents($this->path.'/'.$filename);

        $endOfLine = ($useCrlf) ? "\r\n" : "\n";
        $expectedContent = $beforeContent.implode($endOfLine, $expectedRows).$endOfLine;

        $this->assertSame($expectedContent, $content, 'Content not same');
    }
}
