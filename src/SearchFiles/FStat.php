<?php

namespace Takuya\SearchFiles;

use Takuya\Utils\DateTimeConvert;

class FStat implements \ArrayAccess {
  public string $filename;
  public string|int $size;
  public string|int $ctime;
  public string|int|null $mtime;
  
  public function __construct (
    string          $filename,
    string|int      $size,
    string|int      $ctime,
    string|int|null $mtime
  ) {
    $mtime = $mtime ?? $ctime;
    $this->filename = $filename;
    $this->size = $size;
    $this->ctime = $ctime;
    $this->mtime = $mtime;
    $this->mtime = is_numeric( $this->mtime ) ? DateTimeConvert::ctime_jst( $this->mtime ) : $this->mtime;
    $this->ctime = is_numeric( $this->ctime ) ? DateTimeConvert::ctime_jst( $this->ctime ) : $this->ctime;
  }
  
  public static function fromClass ( object $stat ): FStat {
    return new FStat( ...[
      $stat->filename,
      $stat->size,
      $stat->ctime,
      $stat->mtime,
    ] );
  }
  
  public static function stat ( $filename ): FStat {
    return static::fromClass( (object)array_merge(
      compact( 'filename' ), static::stat_by_func( $filename ) ) );
  }
  
  public static function stat_by_func ( $filename, $keys = ['mtime', 'ctime', 'size'] ): array {
    return array_intersect_key( stat( $filename ), array_flip( $keys ) );
  }
  
  public static function fromFindCmd ( $filename, $base_path, $opt_size = null ): FStat {
    $cmd = new FindWithPrintf( '.', $base_path );
    $cmd->findName( $filename );
    $opt_size && $cmd->findSize( ...$opt_size );
    $stat = null;
    $cmd->run( function( $a ) use ( &$stat ) { $stat = $a; } );
    if ( is_null( $stat ) ) {
      throw new \BadFunctionCallException(
        __METHOD__.'() failed, check find options and check file exists.'
        , 12345 );
    }
    return $stat;
  }
  
  #[\Override] public function offsetExists ( mixed $offset ): bool {
    return property_exists( $this, $offset );
  }
  
  #[\Override] public function offsetGet ( mixed $offset ): mixed {
    return $this->$offset;
  }
  
  #[\Override] public function offsetSet ( mixed $offset, mixed $value ): void {
    $this->$offset = $value;
  }
  
  #[\Override] public function offsetUnset ( mixed $offset ): void {
    throw new \RuntimeException( 'no support.' );
  }
  
}