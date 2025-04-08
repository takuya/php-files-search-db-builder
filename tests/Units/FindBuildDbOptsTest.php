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
use Composer\Composer;

class FindBuildDbOptsTest extends TestCase {
  
  public function setUp (): void {
    $this->dir = $this->root_path();
  }
  
  public function tearDown (): void {
  }
  
  public function test_find_buid_db_add_ignores() {
    $builder = new FindDbBuilder( 'sqlite::memory:', $this->dir );
    $builder->addIgnore('.+\.php');
    $builder->addIgnore('\.git\/');
    $builder->addIgnore('vendor\/');
    $builder->addIgnore('.idea\/');
    $builder->build();
    $ret = $builder->select( "%.json" );
    $last = array_pop($ret);
    $this->assertEquals('./composer.json',$last->filename);
  }
  public function test_find_buid_db_keep_ignore_on_update_entry() {
    $builder = new FindDbBuilder( 'sqlite::memory:', $this->dir );
    $builder->addIgnore('.+\.php');
    $builder->addIgnore('\.git\/');
    $builder->addIgnore('vendor\/');
    $builder->addIgnore('.idea\/');
    $builder->build();
    $filenames_should_be_ignored =[
      (new \ReflectionClass(TestCase::class))->getFileName(),
      \Composer\InstalledVersions::getInstallPath('phpunit/phpunit').'/composer.json',
      \Composer\InstalledVersions::getInstallPath('phpunit/phpunit').'/README.md',
      $this->root_path().'/.git/config',
    ];
    foreach ( $filenames_should_be_ignored as $fname ) {
      $builder->updateEntry($fname);
      $ret = $builder->select($fname);
      $this->assertEmpty($ret);
    }
    $filenames_included =[
      $this->root_path().'/composer.json',
      $this->root_path().'/.gitignore',
    ];
    foreach ( $filenames_included as $fname ) {
      touch($fname);
      $builder->updateEntry($fname);
      $ret = $builder->select($fname);
      $this->assertNotEmpty($ret);
    }
  }
  
  
}

