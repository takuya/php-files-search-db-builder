<?php

namespace Tests\SearchFiles\Units\DbUpdate;


use Takuya\SearchFiles\FindDbBuilder;
use Tests\SearchFiles\TestCase;
use function Takuya\Helpers\temp_dir;
use function Takuya\Helpers\str_rand;

class   FindSubdirFileUpdatesTest extends TestCase {
  
  public function setUp (): void {
    $this->dir = $dir = temp_dir();
    $filename = str_rand( 8 ).'.txt';
    $fullpath = "{$dir}/".$filename;
    file_put_contents( $fullpath, random_bytes( 128 ) );
    $builder = new FindDbBuilder( 'sqlite::memory:', $dir );
    $builder->build();
    $ret = $builder->select_one( "./{$filename}" );
    $this->assertEquals( str_replace( $dir, '.', $fullpath ), $ret->filename );
    $this->builder = $builder;
  }
  
  public function tearDown (): void {
  }
  
  public function test_update_make_subdir_and_create_file_in_subdir () {
    [$dir, $builder] = [$this->dir, $this->builder];
    $subdir = $dir.'/sub_'.strtolower( str_rand( 6 ) );
    mkdir( $subdir );
    file_put_contents( $filename = $subdir.'/'.str_rand( 5 ).'.txt', random_bytes( $fsize = rand( 1, 1000 ) ) );
    $ret[] = $builder->insert( $stat = FindDbBuilder::FileStat( $filename, $dir ) );
    $ret[] = $builder->select_one( $filename );
    $ret[] = $builder->select( $filename );
    $this->assertEquals( 2, $ret[0] );
    $this->assertEquals( $fsize, $ret[1]->size );
    $this->assertEquals( $stat['filename'], $ret[1]->filename );
    $this->assertEquals( $stat['filename'], $ret[2][0]->filename );
  }
  
  public function test_update_make_subdir_and_create_and_update_file_in_subdir () {
    [$dir, $builder] = [$this->dir, $this->builder];
    $subdir = $dir.'/sub_'.strtolower( str_rand( 6 ) );
    mkdir( $subdir );
    file_put_contents( $filename = $subdir.'/'.str_rand( 5 ).'.txt', random_bytes( $fsize = rand( 1, 1000 ) ) );
    $builder->insert( FindDbBuilder::FileStat( $filename, $dir ) );
    //
    unlink( $filename );
    $ret[] = $builder->updateEntry( $filename );
    $ret[] = $builder->select_one( $filename.'aaaaaaaaa' );
    $ret[] = $builder->select( $filename );
    //
    $this->assertEquals( true, $ret[0] );
    $this->assertEquals( null, $ret[1] );
    $this->assertEquals( [], $ret[2] );
  }
  
  
}

