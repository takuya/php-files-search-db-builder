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
    return (new PdoTableRepository($this->pdo,$this->table))->table_exists();
  }
  
  protected function createTable () {
    $pdo = $this->pdo;
    $sql = file_get_contents( __DIR__.'/create-fts-table.sql' );
    $pdo->beginTransaction();
    $pdo->exec( $sql );
    $pdo->commit();
  }
  
  public function insert ( $stat,$use_transaction=false ) {
    return $this->commit(function()use($stat){
      return $this->table()->insert($stat);
    },$use_transaction);
  }
  
  public function select_one ( string $filename,$cond='LIKE' ) {
    return $this->table()->select_one($this->path_in_base_dir($filename),$cond);
  }
  public function select ( string $filename,$cond='LIKE' ) {
    return $this->table()->select($this->path_in_base_dir($filename),$cond);
  }
  
  public function count () {
    return  $this->table()->count();
  }
  
  public function build () {
    $this->beginTranaction();
    $this->find_files( function( $stat ) {
      if($this->isMatchIgnore($stat->filename)){
        return;
      }
      $this->dry_run || $ret=$this->insert( $stat,false );
      $this->verbose && ( fwrite(STDOUT,$ret.PHP_EOL)&& fflush(STDOUT));
    } );
    $this->commitTranscation();
  }
  
  public static function fileStat ( $filename,$base_path ) {
    if (str_contains($filename,$base_path)){
      $filename= static::relative_filename($filename,$base_path);
    }
    $filename = ltrim($filename,'./');
    $cmd = new FindWithPrintf('.', $base_path);
    $cmd->findName('./'.$filename);
    $stat = null;
    $cmd->run(function($a)use(&$stat){$stat=$a;});
    return (array)$stat??[];
  }
  public static function relative_filename( $full_path, $to_base_dir){
    if ( false == str_contains( $full_path, $to_base_dir ) ) {
      throw new \InvalidArgumentException( "filename should be in \$this->base_path" );
    }
    $file = str_replace($to_base_dir,'',$full_path);
    $file = ltrim($file,'./');
    return './'.$file;
  }
  
  public function path_in_base_dir ( $file ) {
    return str_contains( $file, $this->base_path ) ? static::relative_filename($file,$this->base_path) : $file;
  }
  
  public function updateEntry ( $filename,$use_transaction=false ) {
    if (is_dir($filename)){
      throw new \InvalidArgumentException('filename is directory.');
    }
    $found = $this->select_one($this->path_in_base_dir( $filename )) != null;
    $stat = static::fileStat( $this->path_in_base_dir( $filename ),$this->base_path );
    return (!$found && !empty($stat)) && $this->insert($stat,$use_transaction)
    || ($found && !empty($stat)) && $this->update($stat,$use_transaction)
    || ($found && empty($stat)) && $this->delete($filename,$use_transaction);
  }
  private function operator(){
    return new FindDbTable($this->pdo,$this->base_path,$this->table);
  }
  public function table(){
    return $this->operator();
  }
  protected function update($stat, $with_transaction=true){
    return $this->commit(function()use($stat){
      return $this->table()->update($stat);
    },$with_transaction);
  }
  
  protected function delete ( $filename, $with_transaction=true ) {
    return $this->commit(function()use($filename){
      return $this->table()->delete( $this->path_in_base_dir( $filename ) );
    },$with_transaction);
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
