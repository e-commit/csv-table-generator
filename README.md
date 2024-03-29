# CSV Table Generator

Create a CSV file with PHP array.

![Tests](https://github.com/e-commit/csv-table-generator/workflows/Tests/badge.svg)

## Installation ##

To install csv-table-generator with Composer just run :

```bash
$ composer require ecommit/csv-table-generator
```



## Usage ##

```php
use Ecommit\CsvTableGenerator\Csv;

$csv = new Csv('/home/test', 'myfilename', array(
    'header' => array(
        'Column A',
        'Column B',
    ),
));

$csv->write(array('Hello', 'world')); //Add line
$csv->write(array('Test1', 'Test2')); //Add line
$csv->close();
```

/home/test/myfilename.csv is generated :

```
"Column A","Column B"
Hello,world
Test1,Test2
```

**Constructor arguments :**

* **String $pathDir** : Path folder (when CSV file is generated) **Required**
* **String $filename** : Filename (without path folder and extension) **Required**
* **Array $options** : Options. See below

**Availabled options :**

* **header** (array) : Header array. If empty, no header. **Default: array()**
* **max_lines** (null | int) : Max lines per CSV file. If lines > max_lines, many files are generated. **Default: null**
* **delimiter** (string) : CSV delimiter. **Default: ,**
* **enclosure** (string) : CSV enclosure. **Default: "**
* **eol** (string - Csv::EOL_ constants) : EOF(End Of Line) character. See **Csv::EOL_** constants. **Default: Csv::EOL_LF**. If **Csv::EOL_CRLF**
  is used with PHP ≤ 8.1, **unix2dos** program is required
* **escape** : CSV escape. **Default: \\**
* **unix2dos_path** (string) : Unix2dos path. Only used if eol=Csv::EOL_CRLF with PHP ≤ 8.1. **Default: /usr/bin/unix2dos**
* **add_utf8_bom** (bool) : Add or not UTF8 bom. **Default: false**

## License ##

This librairy is under the MIT license. See the complete license in *LICENSE* file.
