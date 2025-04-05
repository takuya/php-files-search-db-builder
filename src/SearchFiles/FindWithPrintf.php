<?php

namespace Takuya\SearchFiles;

use Takuya\ProcessExec\ProcessExecutor;
use Takuya\ProcessExec\ExecArgStruct;

class FindWithPrintf {
  protected string $print_format = '{"mtime":"%T@","ctime":"%C@","size":%s }:: %p\n';
  protected ?string $size_opts;
  protected mixed $name_opts;
  
  public function __construct ( public string $dir,public ?string $cwd=null,public string $tz = 'JST' ) {
    $this->check_dir($dir);
    $this->size_opts=null;
    $this->name_opts=null;
  }
  protected function check_dir($dir){
    if(!file_exists($dir)){
      throw new \RuntimeException("dir is not found");
    }
    if(!is_dir($dir)){
      throw new \RuntimeException("args is not directory");
    }
  }
  public function findName($name){
    $this->name_opts = $name;
  }
  public function findSize(string $opr , string $size){
    $opr = match ($opr){
      '<' => '+',
      'gt' => '+',
      '>' => '-',
      'lt' => '-',
    };
    $this->size_opts = "{$opr}{$size}";
  }
  protected function parseFileTime ( string $timestamp_utc ) {
    $timestamp_utc = [
      intval( $timestamp_utc ), round( fmod( $timestamp_utc, 1 )*1000000 ),
    ];
    $tz = new  \DateTimeZone( 'UTC' );
    $date = \DateTime::createFromFormat( 'U.u', sprintf( '%d.%06d', ...$timestamp_utc ), $tz );
    $date->setTimezone( new \DateTimeZone( $this->tz ) );
    return $date->format('c');
  }
  
  protected function parsePrintedLine ( $line, $fn ) {
    preg_match( '/^(?<json>\{.+?})::\s*(?<file>.+)$/', $line, $m );
    [$json, $file] = [$m['json'] ?? '{}', $m['file'] ?? ''];
    $stat = json_decode( $json );
    $stat->filename = $file;
    $stat->mtime = $this->parseFileTime( $stat->mtime );
    $stat->ctime = $this->parseFileTime( $stat->ctime );
    call_user_func($fn,$stat);
  }
  public function run(\Closure $call_on_per_line){
    $this->find($call_on_per_line);
  }
  
  protected function find ( \Closure $on_per_line ) {
    $cmd = [
      '/usr/bin/find',
      $this->dir ?? '.',
      '-type','f',
      ...($this?->size_opts?['-size',$this->size_opts]:[] ),
      ...($this?->name_opts?['-name',$this->name_opts]:[] ),
      '-printf', $this->print_format,
    ];
    $arg = new ExecArgStruct( ...$cmd );
    $this->cwd && $arg->setCwd($this->cwd);
    $proc = new ProcessExecutor( $arg );
    $proc->watch_interval = 0.01;
    $proc->onStdout( fn( $line ) => $this->parsePrintedLine( $line,$on_per_line ) );
    $proc->start();
  }
  
  
}
