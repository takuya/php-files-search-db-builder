<?php

namespace Tests\SearchFiles\Units;

use Tests\SearchFiles\TestCase;
use Takuya\SearchFiles\FindWithPrintf;

class FindCallbackPerLineTest extends TestCase {
  
  public function setUp (): void {}
  
  public function tearDown (): void {
  }
  
  public function test_run_find_command_parse () {
    $find = new FindWithPrintf('.',__DIR__);
    $find->run(function($stat){
      $this->assertObjectHasProperty('mtime',$stat);
      $this->assertObjectHasProperty('ctime',$stat);
      $this->assertObjectHasProperty('size',$stat);
      $this->assertObjectHasProperty('filename',$stat);
    });
  }
  public function test_run_find_fails_to_file () {
    $this->expectException(\RuntimeException::class);
    $find = new FindWithPrintf(__FILE__);
    $find->run(function($stat){});
  }
  public function test_run_at_no_exists() {
    $this->expectException(\RuntimeException::class);
    $find = new FindWithPrintf(__FILE__.bin2hex(random_bytes(12)));
    $find->run(function($stat){});
  }
}

