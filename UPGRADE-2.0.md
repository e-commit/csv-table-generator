# UPGRADE FROM 1.x to 2.0

* Remove deprecated `unix2dos_path` option
* The default value of the `escape` option is now an empty string (previously `\`), to ensure compliance with RFC 4180
* Update signature : `public function write($data): void` to `public function write(array $data): void`
