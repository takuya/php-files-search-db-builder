<?php

namespace Tests\SearchFiles\Units;

use Tests\SearchFiles\TestCase;
use Takuya\FsNotifyWrapper\LocateWrap;
use Takuya\FsNotifyWrapper\LocateDbBuilder;
use function Takuya\Helpers\str_rand;
use Takuya\FsNotifyWrapper\FsNotifyWrap;
use Takuya\FsNotifyWrapper\FsEventObserver;
use Takuya\FsNotifyWrapper\Events\FsNotifyCreate;
use Takuya\FsNotifyWrapper\FsEventEmitter;
use Takuya\FsNotifyWrapper\Events\FanEvent;
use Takuya\FsNotifyWrapper\FsEventEnum;
use Takuya\SearchFiles\FindWithPrintf;
use PDO;
use Takuya\SearchFiles\FindDbBuilder;
use Takuya\Utils\DateTimeConvert;
use function Takuya\Helpers\temp_dir;

class FindBuildDbTest extends TestCase {
  
  public function setUp (): void {
  }
  
  public function tearDown (): void {
  }
  
  public function test_find_build_db_init_table () {
    $builder = new FindDbBuilder( 'sqlite::memory:', __DIR__ );
    $method = new \ReflectionMethod( $builder, 'table_exists' );
    $result = $method->invoke( $builder );
    $this->assertEquals( true, $result );
  }
  
  public function test_find_build_db_insert_table () {
    $builder = new FindDbBuilder( 'sqlite::memory:', __DIR__ );
    $stat = array_intersect_key( stat( __FILE__ ), array_flip( explode( ',', 'mtime,ctime,size' ) ) );
    $ret = $builder->insert( __FILE__, ...$stat );
    $this->assertEquals( true, $ret );
    //
    //
    $ret = $builder->select_one( __FILE__ );
    //
    $this->assertEquals( $stat['size'], $ret->size );
    $this->assertEquals( __FILE__, $ret->filename );
    $mtime = (int)DateTimeConvert::parse_format_c( $ret->mtime )->format( 'U' );
    $this->assertEquals( $stat['mtime'], $mtime );
  }
  
  public function test_build_locate_sqlite_db () {
    $dir = realpath( __DIR__.'/..' );
    $builder = new FindDbBuilder( 'sqlite::memory:', $dir );
    $builder->locates_build();
    $ret = $builder->select_one( '%'.basename( __FILE__ ).'%' );
    
    //
    $stat = array_intersect_key( stat( __FILE__ ), array_flip( explode( ',', 'mtime,ctime,size' ) ) );
    $this->assertEquals( $stat['size'], $ret->size );
    $this->assertEquals( basename( __FILE__ ), basename( $ret->filename ) );
    $mtime = (int)DateTimeConvert::parse_format_c( $ret->mtime )->format( 'U' );
    $this->assertEquals( $stat['mtime'], $mtime );
    //
    $cnt = sizeof( glob( "{{$dir}/*.php,{$dir}/**/*.php}", GLOB_BRACE ) );
    $this->assertEquals( $cnt, $builder->count() );
  }
  public function test_update_entry_in_locate_sqlite_database(){
    $dir = temp_dir();
    $filename= str_rand(8).'.txt';
    $fullpath = "{$dir}/".$filename;
    file_put_contents($fullpath,random_bytes(128));
    $builder = new FindDbBuilder( 'sqlite::memory:',$dir );
    $builder->locates_build();
    $ret[]=$builder->select_one( "./{$filename}" );
    /// update file
    sleep(1);
    file_put_contents($fullpath,random_bytes(512));
    $builder->update($fullpath);
    //
    $ret[]=$builder->select_one( "./{$filename}" );
    $this->assertEquals(512,$ret[1]->size);
    // assert equals to stat().
    $stat = (object)FindDbBuilder::fileStat($filename,$dir);
    $this->assertEquals($stat->mtime,$ret[1]->mtime);
    $this->assertEquals($stat->ctime,$ret[1]->ctime);
    $this->assertEquals($stat->size,$ret[1]->size);
    // assert changed.
    $this->assertNotEquals($ret[0]->size,$ret[1]->size);
    $this->assertNotEquals($ret[0]->mtime,$ret[1]->mtime);
    
  }
  public function test_delete_entry_in_locate_sqlite_database(){
    $dir = temp_dir();
    $filename= str_rand(8).'.txt';
    $fullpath = "{$dir}/".$filename;
    file_put_contents($fullpath,random_bytes(128));
    $builder = new FindDbBuilder( 'sqlite::memory:',$dir );
    $builder->locates_build();
    $ret[]=$builder->select_one( "./{$filename}" );
    // delete file
    unlink($fullpath);
    $builder->update($fullpath);
    // detect deleted.
    $ret[]=$builder->select_one( "./{$filename}" );
    //
    $this->assertNotFalse($ret[0]);
    $this->assertNull($ret[1]);
  }
  public function test_build_locate_files_using_ignore_list () {
    $dir = realpath( __DIR__.'/../..' );
    $builder = new FindDbBuilder( 'sqlite::memory:', $dir );
    $builder->addIgnore('\.git');
    $builder->addIgnore('vendor\/');
    $builder->addIgnore('composer\..+');
    $builder->locates_build();
    $ret[] = $builder->select( '%.git%' );
    $ret[] = $builder->select( '%vendor/%' );
    $ret[] = $builder->select( '%composer.%' );
    
    foreach ( $ret as $r ) {
      $this->assertEmpty($r);
    }
  }
  
  
}

