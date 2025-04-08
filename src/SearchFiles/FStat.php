<?php

namespace Takuya\SearchFiles;

use Takuya\Utils\DateTimeConvert;

class FStat {
  public function __construct (
    public string          $filename,
    public string|int      $size,
    public string|int      $ctime,
    public string|int|null $mtime
  ) {
    $this->mtime ??= $this->ctime;
    foreach ( ['mtime', 'ctime'] as $name ) {
      $this->{$name} = is_numeric( $this->{$name} ) ? DateTimeConvert::ctime_jst( $this->{$name} ) : $this->{$name};
    }
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