<?php

namespace Takuya\Utils\PdoTable\Traits;

use PDO;

trait TableInfo {
  public function tables () {
    $pdo = $this->pdo;
    $st = $pdo->prepare( "SELECT * FROM sqlite_master WHERE type='table'" );
    $st->execute();
    return $st->fetchAll( PDO::FETCH_ASSOC );
  }
  
  public function table_exists () {
    $pdo = $this->pdo;
    $st = $pdo->prepare( "SELECT count(*) as count FROM sqlite_master WHERE type='table' and name = :tb" );
    //$st = $pdo->prepare( "SELECT * FROM sqlite_master WHERE type='table'" );
    $st->bindParam( 'tb', $this->table );
    $st->execute();
    $ret = $st->fetch( PDO::FETCH_ASSOC );
    $this->transaction_level = 0;
    return $ret && ( $ret['count'] ?? 0 ) > 0;
  }
}