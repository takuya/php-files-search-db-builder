<?php

namespace Takuya\Helpers;

if ( !function_exists( 'proc_fork' ) ) {
  function proc_fork ( callable $do_func_on_child, ?callable $do_func_on_parent = null ) {
    pcntl_async_signals( true );
    $pid = pcntl_fork();
    if ( $pid < 0 ) {
      throw new \RuntimeException( 'fork failed' );
    }
    if ( $pid > 0 ) {
      // do nothing
      $do_func_on_parent && $do_func_on_parent();
    } else {
      if ( $pid == 0 ) {
        call_user_func( $do_func_on_child, posix_getpid() );
        exit;
      }
    }
    
    return $pid;
  }
}

