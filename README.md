# CSV Table Generator

Create a CSV file with PHP array.

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/28c792f3-d5ac-4efb-9500-c7bf7cc06b7c/big.png)](https://insight.sensiolabs.com/projects/28c792f3-d5ac-4efb-9500-c7bf7cc06b7c)

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
  is used, **unix2dos** program is required
* **unix2dos_path** (string) : Unix2dos path. Only used if eol=Csv::EOL_CRLF. **Default: /usr/bin/unix2dos**
* **add_utf8_bom** (bool) : Add or not UTF8 bom. **Default: false**

## License ##

This librairy is under the MIT license. See the complete license in *LICENSE* file.
