<?php

namespace Takuya\SearchFiles;

use Takuya\ProcessExec\ProcessExecutor;
use Takuya\ProcessExec\ExecArgStruct;
use PDO;
use Takuya\Utils\DateTimeConvert;
use PHPUnit\Util\Exception;
use Takuya\ProcOpen\ProcOpen;

class FindDbBuilder {
  public PDO $pdo;
  protected int $transaction_level;
  
  public function __construct ( string $DSN, protected string $base_path, public string $table = 'locates' ) {
    $this->pdo = new PDO( $DSN );
    $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    !$this->table_exists() && $this->createTable();
  }
  
  protected function table_exists () {
    $pdo = $this->pdo;
    $st = $pdo->prepare( "SELECT count(*) as count FROM sqlite_master WHERE type='table' and name = :tb" );
    //$st = $pdo->prepare( "SELECT * FROM sqlite_master WHERE type='table'" );
    $st->bindParam( 'tb', $this->table );
    $st->execute();
    $ret = $st->fetch( PDO::FETCH_ASSOC );
    $this->transaction_level = 0;
    return $ret && ( $ret['count'] ?? 0 ) > 0;
  }
  
  protected function createTable () {
    $pdo = $this->pdo;
    $sql = file_get_contents( __DIR__.'/create-fts-table.sql' );
    $pdo->beginTransaction();
    $pdo->exec( $sql );
    $pdo->commit();
  }
  
  public function begin () {
    $this->transaction_level++;
    $this->pdo->beginTransaction();
  }
  
  public function commit () {
    $this->pdo->commit();
    $this->transaction_level--;
  }
  
  public function rollBack () {
    $this->pdo->rollBack();
    $this->transaction_level--;
  }
  
  public function insert ( string $filename, string $mtime, string $ctime, string $size ) {
    $stat = (object)compact( 'filename', 'mtime', 'ctime', 'size' );
    $stat->mtime = is_numeric( $stat->ctime ) ? DateTimeConvert::ctime_jst( $stat->mtime ) : $stat->mtime;
    $stat->ctime = is_numeric( $stat->ctime ) ? DateTimeConvert::ctime_jst( $stat->ctime ) : $stat->ctime;
    $pdo = $this->pdo;
    $smt = $pdo->prepare( 'INSERT INTO '.$this->table.' (filename,mtime,ctime,size) VALUES (:filename,:mtime,:ctime,:size);' );
    foreach ( ['filename', 'mtime', 'ctime', 'size'] as $col ) {
      $smt->bindValue( $col, $stat->{$col} );
    }
    return $smt->execute();
  }
  
  public function select ( string $filename ) {
    $pdo = $this->pdo;
    $smt = $pdo->prepare( 'select * from '.$this->table.' where filename LIKE :filename' );
    $smt->bindValue( 'filename', $filename );
    $smt->execute();
    return $smt->fetch( PDO::FETCH_OBJ );
  }
  
  public function count () {
    $pdo = $this->pdo;
    $smt = $pdo->prepare( 'select count(*) as count from '.$this->table );
    $smt->execute();
    return $smt->fetch( PDO::FETCH_OBJ )->count;
  }
  
  public function locates_build () {
    $this->begin();
    $this->find_files( function( $stat ) {
      $this->insert( $stat->filename, $stat->mtime, $stat->ctime, $stat->size );
    } );
    $this->commit();
  }
  
  public static function fileStat ( $filename,$base_path ) {
    $filename = ltrim($filename,'./');
    $cmd = new FindWithPrintf('.', $base_path);
    $cmd->findName($filename);
    $stat = null;
    $cmd->run(function($a)use(&$stat){$stat=$a;});
    return (array)$stat;
  }
  
  public function path_to_base_dir ( $file ) {
    if ( false == str_contains( $file, $this->base_path ) ) {
      throw new \InvalidArgumentException( "filename should be in \$this->base_path" );
    }
    $file = str_replace( $this->base_path, '', $file );
    $file = ltrim( $file, '/' );
    $file = './'.$file;
    return $file;
  }
  
  public function update ( $filename ) {
    try {
      $wd = getcwd();
      chdir( $this->base_path );
      $filename = $this->path_to_base_dir( $filename );
      if ( !file_exists( $filename ) ) {
        return $this->delete( $filename );
      }
      //
      $pdo = $this->pdo;
      $this->begin();
      $smt = $pdo->prepare( $sql = 'update '.$this->table
        .' SET '
        .' size=:size, '
        .' mtime=:mtime, '
        .' ctime=:ctime '
        .' where filename =:filename limit 1 ' );
      $ret = $smt->execute( static::fileStat( $filename,$this->base_path ) );
      $this->commit();
      return $ret;
    } catch (\Exception $e) {
      $this->rollBack();
      throw $e;
    } finally {
      chdir( $wd );
    }
  }
  
  protected function delete ( $filename ) {
    try {
      $pdo = $this->pdo;
      $this->begin();
      $smt = $pdo->prepare( 'delete from '.$this->table.' where filename =:filename' );
      $smt->bindParam( 'filename', $filename );
      $ret = $smt->execute();
      $this->commit();
      return $ret;
    } catch (\Exception $e) {
      $this->rollBack();
      throw $e;
    }
  }
  
  protected function find_files ( callable $fn ) {
    $find = new FindWithPrintf( '.', $this->base_path );
    $find->findSize('<','1M');
    $find->run( $fn );
  }
}
