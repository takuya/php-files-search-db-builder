<?php

namespace Takuya\Helpers;

if( ! function_exists('temp_dir') ) {
  function temp_dir():string {
    $temp_dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'php-tmpdir-'.str_rand(8);
    mkdir($temp_dir, 0777, true);
    register_shutdown_function(fn()=>proc_open(['rm', '-rf', $temp_dir], [], $io));
    return $temp_dir;
  }
}