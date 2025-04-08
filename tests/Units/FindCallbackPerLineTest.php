<?php

namespace Tests\SearchFiles\Units;

use Tests\SearchFiles\TestCase;
use Takuya\SearchFiles\FindWithPrintf;
use Takuya\SearchFiles\FStat;
use Takuya\Utils\DateTimeConvert;

class FindCallbackPerLineTest extends TestCase {
  
  public function setUp (): void {}
  
  public function tearDown (): void {
  }
  public function test_fstat_from_filename(){
    $obj = FStat::stat(__FILE__);
    $stat = \stat(__FILE__);
    $this->assertEquals(__FILE__,$obj->filename);
    $this->assertEquals($stat['size'],$obj->size);
    $this->assertEquals($stat['ctime'],DateTimeConvert::parse_format_c($obj->ctime)->format('U'));
    $this->assertEquals($stat['mtime'],DateTimeConvert::parse_format_c($obj->mtime)->format('U'));
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

