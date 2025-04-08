<?php

namespace Takuya\Utils\PdoTable\Traits;

trait TransactionBlock {
  protected int $transaction_level;
  
  public function beginTranaction () {
    $this->transaction_level ??= 0;
    $this->transaction_level++;
    $this->pdo->beginTransaction();
  }
  
  public function commitTranscation () {
    $this->pdo->commit();
    $this->transaction_level--;
  }
  
  public function rollBackTransaction () {
    $this->pdo->rollBack();
    $this->transaction_level--;
  }
  
  public function commit ( $fn, $with_transaction = false ) {
    return $with_transaction ? $this->transaction( $fn ) : call_user_func( $fn );
  }
  
  public function transaction ( callable $fn ) {
    try {
      $this->beginTranaction();
      $ret = call_user_func( $fn );
      $this->commitTranscation();
      return $ret;
    } catch (\Exception $e) {
      $this->rollBackTransaction();
      throw $e;
    }
  }
  
}