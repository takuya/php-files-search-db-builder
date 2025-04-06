## find files and save result into sqlite3

To speed up for searching files, build search indexer fts.

## FTS 5 

The fts in sqlite3, can search by trigram. this package make use of it to search files. 

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
