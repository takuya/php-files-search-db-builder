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

class FindDbBuilder {
  use TransactionBlock;
  public PDO $pdo;
  protected int $transaction_level;
  public bool $dry_run=false;
  public bool $verbose=false;
  protected array $find_size;
  protected array $ignore_pattern;
  
  public function __construct ( string $DSN, protected string $base_path, public string $table = 'locates' ) {
    $this->pdo = new PDO( $DSN );
    $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    !$this->table_exists() && $this->createTable();
  }
  public function addIgnore(string $pattern):void {
    $easy_delim_check = fn ($pattern) => preg_match('/^([\|\/#~])([^\/#~]*)\1([a-zA-Z]*)$/', $pattern);
    if($easy_delim_check($pattern)){
      throw new \InvalidArgumentException('regex with delim. remove delim.');
    }
    $this->ignore_pattern ??=[];
    $this->ignore_pattern[] = $pattern;
  }
  public function isMatchIgnore($filename){
    if(empty($this->ignore_pattern)){
      return false;
    }
    $regex = implode('|',$this->ignore_pattern);
    return preg_match("/{$regex}/",$filename);
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
  
  public function insert ( string $filename, string $mtime, string $ctime, string $size ) {
    $stat = (object)compact( 'filename', 'mtime', 'ctime', 'size' );
    $stat->mtime = is_numeric( $stat->ctime ) ? DateTimeConvert::ctime_jst( $stat->mtime ) : $stat->mtime;
    $stat->ctime = is_numeric( $stat->ctime ) ? DateTimeConvert::ctime_jst( $stat->ctime ) : $stat->ctime;
    $table = new PdoTableRepository($this->pdo,$this->table);
    return $table->insert((array)$stat);
  }
  
  public function select_one ( string $filename ) {
    return (new PdoTableRepository($this->pdo,$this->table))->select_one('filename','LIKE',$filename);
  }
  public function select ( string $filename ) {
    return (new PdoTableRepository($this->pdo,$this->table))->select('filename','LIKE',$filename);
  }
  
  public function count () {
    return  (new PdoTableRepository($this->pdo,$this->table))->count();
  }
  
  public function build () {
    $this->begin();
    $this->find_files( function( $stat ) {
      if($this->isMatchIgnore($stat->filename)){
        return;
      }
      $this->dry_run || $ret=$this->insert( $stat->filename, $stat->mtime, $stat->ctime, $stat->size );
      $this->verbose && ( fwrite(STDOUT,$ret.PHP_EOL)&& fflush(STDOUT));
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
    if ( !file_exists( $filename ) ) {
      return $this->delete( $filename );
    }
    try {
      $wd = getcwd();
      chdir( $this->base_path );
      $filename = $this->path_to_base_dir( $filename );
      //
      $this->begin();
      $ret = (new PdoTableRepository($this->pdo,$this->table))->update(static::fileStat( $filename,$this->base_path ));
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
      $this->begin();
      $ret = (new PdoTableRepository($this->pdo,$this->table))->delete('filename', $filename );
      $this->commit();
      return $ret;
    } catch (\Exception $e) {
      $this->rollBack();
      throw $e;
    }
  }
  public function findSize($opt){
    $this->find_size = [substr($opt,0,1),substr($opt,0,strlen($opt))];
  }
  
  protected function find_files ( callable $fn ) {
    $find = new FindWithPrintf( '.', $this->base_path );
    !empty($this->find_size) && $find->findSize(...$this->find_size);
    $find->run( $fn );
  }
}
