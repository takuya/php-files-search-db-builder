<?php

namespace Takuya\SearchFiles;


use Takuya\Utils\DateTimeConvert;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;

class FindWithPrintf {
  protected string $print_format = '{"mtime":"%T@","ctime":"%C@","size":%s }:: %p\n';
  protected ?string $size_opts;
  protected mixed $name_opts;
  
  public function __construct ( public string $dir, public ?string $cwd = null, public string $tz = 'JST' ) {
    $this->check_dir( $dir );
    $this->size_opts = null;
    $this->name_opts = null;
  }
  
  protected function check_dir ( $dir ) {
    if ( !file_exists( $dir ) ) {
      throw new \RuntimeException( "dir is not found" );
    }
    if ( !is_dir( $dir ) ) {
      throw new \RuntimeException( "args is not directory" );
    }
  }
  
  public function findName ( $name ) {
    $this->name_opts = $name;
  }
  
  public function findSize ( string $opr, string $size ) {
    $opr = match ( $opr ) {
      '-' => '-',
      '+' => '+',
      '>' => '+',
      'gt' => '+',
      '<' => '-',
      'lt' => '-',
      default => false
    };
    $opr && $this->size_opts = "{$opr}{$size}";
  }
  
  
  protected function parsePrintedLine ( $line ): FStat {
    $line = mb_convert_encoding( $line, 'utf8' );
    preg_match( '/^(?<json>\{.+?})::\s*(?<file>.+)$/', $line, $m );
    [$json, $file] = [$m['json'] ?? '{}', $m['file'] ?? ''];
    $stat = json_decode( $json );
    $stat->filename = $file;
    return FStat::fromClass( $stat );
  }
  
  public function run ( \Closure $call_on_per_line ) {
    $this->find( $call_on_per_line );
  }
  
  protected function find ( \Closure $on_per_line ) {
    $cmd = [
      '/usr/bin/find',
      $this->dir ?? '.',
      '-type', 'f',
      ...( $this?->size_opts ? ['-size', $this->size_opts] : [] ),
      ...( $this?->name_opts ? ['-wholename', $this->name_opts] : [] ),
      '-printf', $this->print_format,
    ];
    //dump(join(' ',$cmd));
    $arg = new ExecArgStruct( ...$cmd );
    $this->cwd && $arg->setCwd( $this->cwd );
    $proc = new ProcessExecutor( $arg );
    $proc->watch_interval = 0.01;
    $proc->onStdout( fn( $line ) => call_user_func( $on_per_line, $this->parsePrintedLine( $line ) ) );
    $proc->start();
  }
  
  
}
