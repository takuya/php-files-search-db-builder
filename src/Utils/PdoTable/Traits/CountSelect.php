<?php

namespace Takuya\Utils\PdoTable\Traits;

use PDO;

trait CountSelect {
  public function count (...$select_args): int {
    /** @var \PDOStatement $st */
    $pdo = $this->pdo;
    $count_sql = 'select count(*) as count from (%s)';
    if(empty($select_args)){
      $st = $pdo->prepare( sprintf($count_sql,$this->table));
      $st->execute();
    }else{
      [$st,$binds] =  $this->select_statment(...$select_args);
      $st = $pdo->prepare( sprintf($count_sql,$st->queryString));
      $st->execute($binds);
    }
    return $st->fetch( PDO::FETCH_OBJ )->count;
  }
  
  
}