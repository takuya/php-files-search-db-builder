<?php

namespace Takuya\Utils\PdoTable\Traits;

trait TransactionBlock {
  protected int $transaction_level;
  public function begin () {
    $this->transaction_level++;
    $this->pdo->beginTransaction();
  }
  
  public function commit () {
    $this->pdo->commit();
    $this->transaction_level--;
  }
  
  public function rollBack () {
    $this->pdo->rollBack();
    $this->transaction_level--;
  }
  public function transaction(callable $fn){
    try {
      $this->begin();
      $ret = call_user_func($fn);
      $this->commit();
      return $ret;
    } catch(\Exception $e){
      $this->rollBack();
      throw $e;
    }
  }
  
}