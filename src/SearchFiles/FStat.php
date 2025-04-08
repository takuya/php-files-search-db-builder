<?php

namespace Takuya\SearchFiles;

use Takuya\Utils\DateTimeConvert;

class FStat {
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
      compact( 'filename' ), static::stat_as_array( $filename ) ) );
  }
  
  public static function stat_as_array ( $filename, $keys = ['mtime', 'ctime', 'size'] ): array {
    return array_intersect_key( stat( $filename ), array_flip( $keys ) );
  }
  
}