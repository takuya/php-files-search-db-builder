<?php

namespace Takuya\SearchFiles;

use Takuya\ProcessExec\ProcessExecutor;
use Takuya\ProcessExec\ExecArgStruct;
use PDO;
use Takuya\Utils\DateTimeConvert;
use PHPUnit\Util\Exception;
use Takuya\ProcOpen\ProcOpen;
use Takuya\Utils\PdoTable\PdoTableRepository;
use Takuya\Utils\PdoTable\Traits\TransactionBlock;
use Takuya\Utils\PdoTable\Traits\TableInfo;

class FindDbTable {
  protected array $find_size;
  protected array $ignore_pattern;
  
  public function __construct ( public PDO $pdo, protected string $base_path, public string $tablename = 'locates' ) {
    if ( !$this->repo()->table_exists() ) {
      throw new \RuntimeException( 'table not found' );
    }
  }
  
  protected function checkFilename ( $fname ) {
    if ( str_starts_with( './', $fname ) || !str_contains( $fname, $this->base_path ) ) {
      return true;
    }
    throw new \InvalidArgumentException( 'filename must be relative.' );
  }
  
  protected function repo () {
    return new PdoTableRepository( $this->pdo, $this->tablename );
  }
  
  public function count ( string $filename = null ) {
    $args = $filename ?['filename', 'LIKE', $filename ]: [];
    return $this->repo()->count( ...$args);
  }
  
  public function delete ( $filename ) {
    $this->checkFilename( $filename );
    return $this->repo()->delete( 'filename', $filename );
  }
  
  public function update ( array|object $key_and_val ) {
    $this->checkFilename( ( (array)$key_and_val )['filename'] );
    return $this->repo()->update( (array)$key_and_val );
  }
  
  public function insert ( array|object $key_and_val ) {
    $stat = (object)$key_and_val;
    $this->checkFilename( $stat->filename );
    $stat->mtime = is_numeric( $stat->ctime ) ? DateTimeConvert::ctime_jst( $stat->mtime ) : $stat->mtime;
    $stat->ctime = is_numeric( $stat->ctime ) ? DateTimeConvert::ctime_jst( $stat->ctime ) : $stat->ctime;
    return $this->repo()->insert( (array)$stat );
  }
  
  public function select ( string $filename, $cond = 'LIKE' ) {
    $this->checkFilename( $filename );
    return iterator_to_array( $this->repo()->select( 'filename', $cond, $filename ) );
  }
  
  public function select_one ( string $filename, $cond = 'LIKE' ) {
    $this->checkFilename( $filename );
    return $this->repo()->select_one( 'filename', $cond, $filename );
  }
  
}
