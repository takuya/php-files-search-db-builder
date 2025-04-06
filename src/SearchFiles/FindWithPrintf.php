<?php

namespace Takuya\SearchFiles;

use Takuya\ProcessExec\ProcessExecutor;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\Utils\DateTimeConvert;

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
    return DateTimeConvert::ctime_jst($timestamp_utc);
  }
  protected function stat($filename){
    return (object)array_intersect_key( stat( __FILE__ ), array_flip( explode( ',', 'mtime,ctime,size' ) ) );
  }
  
  protected function parsePrintedLine ( $line, $fn ) {
    $line = mb_convert_encoding($line,'utf8');
    preg_match( '/^(?<json>\{.+?})::\s*(?<file>.+)$/', $line, $m );
    [$json, $file] = [$m['json'] ?? '{}', $m['file'] ?? ''];
    $stat = json_decode( $json);
    if (empty($stat->mtime)){
      $stat = $this->stat($file);
    }
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
