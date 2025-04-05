<?php

namespace Takuya\Utils;

class DateTimeConvert {
  
  public static function fromTimeStamp ( string $timstamp, string $TZ ) :\DateTime{
    $timestamp = [
      intval( $timstamp ), round( fmod( $timstamp, 1 )*1000000 ),
    ];
    $tz = new  \DateTimeZone( $TZ );
    $date = \DateTime::createFromFormat( 'U.u', sprintf( '%d.%06d', ...$timestamp ), $tz );
    return $date;
  }
  
  public static function fromTimeStampUTC ( string $timestamp_utc ):\DateTime {
    return static::fromTimeStamp( $timestamp_utc, 'UTC' );
  }
  public static function fileTimeTz ( string $timestamp_utc, $TZ, string $format='c' ):string {
    return static::fromTimeStampUTC( $timestamp_utc )
                 ->setTimezone( new \DateTimeZone( $TZ ) )
                 ->format( $format );
  }
  
  public static function ctime_jst ( string $timestamp_utc,string $format ='c' ):string {
    return static::fileTimeTz($timestamp_utc,'Asia/Tokyo',$format);
  }
  public static function parse_format_c(string $date_string_with_tz ) :\DateTime{
    // "2025-04-05T23:27:54+09:00"
    return \DateTime::createFromFormat('Y-m-d\TH:i:sP',$date_string_with_tz);
  }
}