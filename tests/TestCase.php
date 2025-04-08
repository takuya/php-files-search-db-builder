<?php


namespace Tests\SearchFiles;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Composer\Composer;

abstract class TestCase extends BaseTestCase {
  public function path_of_tests(){
    $ref = new \ReflectionClass(TestCase::class);
    return realpath(dirname($ref->getFileName()));
  }
  public function vendor_path(){
    $ref = new \ReflectionClass(BaseTestCase::class);
    return preg_split('/phpunit/',$ref->getFileName())[0];
  }
  public function root_path(){
    return realpath(\Composer\InstalledVersions::getRootPackage()['install_path']);
  }
}
