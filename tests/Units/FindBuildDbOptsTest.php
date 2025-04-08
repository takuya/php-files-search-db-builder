<?php

namespace Tests\SearchFiles\Units;


use Tests\SearchFiles\TestCase;
use Takuya\SearchFiles\FindDbBuilder;
use function Takuya\Helpers\temp_dir;
use function Takuya\Helpers\str_rand;

class FindBuildDbOptsTest extends TestCase {
  
  public function setUp (): void {
    $this->dir = $this->root_path();
  }
  
  public function tearDown (): void {
  }
  
  public function test_find_build_db_add_ignores () {
    $builder = new FindDbBuilder( 'sqlite::memory:', $this->dir );
    $builder->addIgnore( '.+\.php' );
    $builder->addIgnore( '\.git\/' );
    $builder->addIgnore( 'vendor\/' );
    $builder->addIgnore( '.idea\/' );
    $builder->build();
    $ret = $builder->select( "%.json" );
    $last = array_pop( $ret );
    $this->assertEquals( './composer.json', $last->filename );
  }
  
  public function test_find_build_db_keep_ignore_on_update_entry () {
    $builder = new FindDbBuilder( 'sqlite::memory:', $this->dir );
    $builder->addIgnore( '.+\.php' );
    $builder->addIgnore( '\.git\/' );
    $builder->addIgnore( 'vendor\/' );
    $builder->addIgnore( '.idea\/' );
    $builder->build();
    $filenames_should_be_ignored = [
      ( new \ReflectionClass( TestCase::class ) )->getFileName(),
      \Composer\InstalledVersions::getInstallPath( 'phpunit/phpunit' ).'/composer.json',
      \Composer\InstalledVersions::getInstallPath( 'phpunit/phpunit' ).'/README.md',
      $this->root_path().'/.git/config',
    ];
    foreach ( $filenames_should_be_ignored as $fname ) {
      $builder->updateEntry( $fname );
      $ret = $builder->select( $fname );
      $this->assertEmpty( $ret );
    }
    $filenames_included = [
      $this->root_path().'/composer.json',
      $this->root_path().'/.gitignore',
    ];
    foreach ( $filenames_included as $fname ) {
      touch( $fname );
      $builder->updateEntry( $fname );
      $ret = $builder->select( $fname );
      $this->assertNotEmpty( $ret );
    }
  }
  
  public function test_find_build_db_file_size_filtering () {
    $dir = temp_dir();
    file_put_contents( $smallfile = $dir.'/'.str_rand( 10 ).'-small.txt', random_bytes( 100 ) );
    file_put_contents( $largefile = $dir.'/'.str_rand( 10 ).'-large.txt', random_bytes( 1024 + 1 ) );
    $builder = new FindDbBuilder( 'sqlite::memory:', $dir );
    $builder->findSize( '+1k' );
    $builder->build();
    $ret[] = $builder->select( "%" );
    file_put_contents( $tiny_file = $dir.'/'.str_rand( 10 ).'-tiny.txt', random_bytes( 1 ) );
    $builder->updateEntry( $tiny_file );
    $ret[] = $builder->select( $tiny_file );
    file_put_contents( $big_file = $dir.'/'.str_rand( 10 ).'-big.txt', random_bytes( 2048 ) );
    $builder->updateEntry( $big_file );
    $ret[] = $builder->select( $big_file );
    //
    $this->assertCount( 1, $ret[0] );
    $this->assertEmpty( $ret[1] );
    $this->assertCount( 1, $ret[2] );
  }
  
  
}

