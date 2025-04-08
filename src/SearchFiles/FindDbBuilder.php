<?php

namespace Takuya\SearchFiles;


use PDO;
use Takuya\Utils\PdoTable\Traits\TransactionBlock;
use Takuya\Utils\PdoTable\Exceptions\TableNotFoundException;


class FindDbBuilder {
  use TransactionBlock;
  
  public PDO $pdo;
  public bool $dry_run = false;
  public bool $verbose = false;
  protected array $find_size;
  protected array $ignore_pattern;
  
  public function __construct ( string $DSN, protected string $base_path, public string $table = 'locates' ) {
    $this->pdo = new PDO( $DSN );
    $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $this->table_check_and_create_if_not_exit();
  }
  
  protected function table_check_and_create_if_not_exit (): void {
    try {
      $this->table();
    } catch (TableNotFoundException $e) {
      $this->createTable();
    }
  }
  
  
  public function addIgnore ( string $pattern ): void {
    $easy_delim_check = fn( $pattern ) => preg_match( '/^([\|\/#~])([^\/#~]*)\1([a-zA-Z]*)$/', $pattern );
    if ( $easy_delim_check( $pattern ) ) {
      throw new \InvalidArgumentException( 'regex with delim. remove delim.' );
    }
    $this->ignore_pattern ??= [];
    $this->ignore_pattern[] = $pattern;
  }
  
  public function isMatchIgnore ( $filename ) {
    if ( empty( $this->ignore_pattern ) ) {
      return false;
    }
    $regex = implode( '|', $this->ignore_pattern );
    return preg_match( "/{$regex}/", $filename ) == 1;
  }
  
  protected function createTable () {
    $pdo = $this->pdo;
    $sql = file_get_contents( __DIR__.'/create-fts-table.sql' );
    $pdo->beginTransaction();
    $pdo->exec( $sql );
    $pdo->commit();
  }
  
  public function insert ( $stat, $use_transaction = false ) {
    return $this->commit( function() use ( $stat ) {
      return $this->table()->insert( $stat );
    }, $use_transaction );
  }
  
  public function select_one ( string $filename, $cond = 'LIKE' ) {
    return $this->table()->select_one( $this->path_in_base_dir( $filename ), $cond );
  }
  
  public function select ( string $filename, $cond = 'LIKE' ) {
    return $this->table()->select( $this->path_in_base_dir( $filename ), $cond );
  }
  
  public function count () {
    return $this->table()->count();
  }
  
  public function build () {
    $this->beginTranaction();
    $this->find_files( function( $stat ) {
      if ( $this->isMatchIgnore( $stat->filename ) ) {
        return;
      }
      $this->dry_run || $ret = $this->insert( $stat, false );
      $this->verbose && ( fwrite( STDOUT, $ret.PHP_EOL ) && fflush( STDOUT ) );
    } );
    $this->commitTranscation();
  }
  
  public static function relative_filename ( $full_path, $to_base_dir ) {
    if ( false == str_contains( $full_path, $to_base_dir ) ) {
      throw new \InvalidArgumentException( "filename should be in \$this->base_path" );
    }
    $file = str_replace( $to_base_dir, '', $full_path );
    $file = preg_replace( '%^(/|\./)%', '', $file );
    return './'.$file;
  }
  
  public function path_in_base_dir ( $file ) {
    return str_contains( $file, $this->base_path ) ? static::relative_filename( $file, $this->base_path ) : $file;
  }
  
  public function updateEntry ( $filename, $use_transaction = false ) {
    if ( is_dir( $filename ) ) {
      throw new \InvalidArgumentException( 'filename is directory.' );
    }
    $filename = $this->path_in_base_dir( $filename );
    $found = $this->select_one( $filename ) != null;
    $stat = $this->getFileStat( $filename );
    return
      ( !$found && !empty( $stat ) ) && $this->insert( $stat, $use_transaction )
      || ( $found && !empty( $stat ) ) && $this->update( $stat, $use_transaction )
      || ( $found && empty( $stat ) ) && $this->delete( $filename, $use_transaction );
  }
  
  /**
   * @throws TableNotFoundException
   */
  private function operator () {
    return new FindDbTable( $this->pdo, $this->base_path, $this->table );
  }
  
  /**
   * @throws TableNotFoundException
   */
  public function table () {
    return $this->operator();
  }
  
  protected function update ( $stat, $with_transaction = true ) {
    return $this->commit( function() use ( $stat ) {
      return $this->table()->update( $stat );
    }, $with_transaction );
  }
  
  protected function delete ( $filename, $with_transaction = true ) {
    return $this->commit( function() use ( $filename ) {
      return $this->table()->delete( $this->path_in_base_dir( $filename ) );
    }, $with_transaction );
  }
  
  public function findSizeOpt ( $opt ) {
    $this->find_size = [substr( $opt, 0, 1 ), substr( $opt, 1, strlen( $opt ) )];
  }
  
  protected function find_files ( callable $fn ) {
    $find = new FindWithPrintf( '.', $this->base_path );
    !empty( $this->find_size ) && $find->findSize( ...$this->find_size );
    $find->run( $fn );
  }
  
  public function getFileStat ( $relative_name ) {
    if ( $this->isMatchIgnore( $relative_name ) ) {
      return [];
    }
    try {
      $stat = static::fileStat( $relative_name, $this->base_path, $this->find_size ?? null );
    } catch (\BadFunctionCallException $e) {
      return null;
    }
    return $stat;
  }
  
  public static function fileStat ( $filename, $base_path, $opt_size = null ) {
    $filename = !str_contains( $filename, $base_path ) ? $filename : static::relative_filename( $filename, $base_path );
    $filename = str_starts_with( $filename, './' ) ? $filename : './'.$filename;
    $stat = FStat::fromFindCmd( $filename, $base_path, $opt_size );
    return $stat;
  }
}
