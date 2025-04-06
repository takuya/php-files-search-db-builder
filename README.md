## find files and save result into sqlite3

To speed up for searching files, build search indexer fts.

## FTS 5 

The fts in sqlite3, can search by trigram. this package make use of it to search files. 

ただ、日本語だとFTS5は３文字以上が曲者。bigram 作ればいいけど、ファイル名程度の検索なら LIKEで充分かも

## installation
```shell
name='php-files-search-db-builder'
composer config repositories.$name \
vcs https://github.com/takuya/$name  
composer require takuya/$name:master
composer install
```
```shell
name='takuya/php-files-search-db-builder'
repo=git@github.com:$name.git
composer config repositories.$name vcs $repo
composer require $name
```

## sample 

```shell
use Takuya\SearchFiles\FindDbBuilder;

require __DIR__.'/vendor/autoload.php';

$db = './sample.db';
file_exists($db) && unlink($db);
$dir = '/home/takuya/';
$builder = new FindDbBuilder( "sqlite:{$db}", $dir );
$builder->locates_build();
```
