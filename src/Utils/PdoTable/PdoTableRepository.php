<?php

namespace Takuya\Utils\PdoTable;

use PDO;
use Takuya\Utils\PdoTable\Traits\TransactionBlock;
use function PHPUnit\Framework\matches;
use Takuya\Utils\PdoTable\Traits\GenericCRUD;

class PdoTableRepository {
  use TransactionBlock;
  use GenericCRUD;
  
  public function __construct (
    public PDO       $pdo,
    protected string $table
  ) {
    $this->transaction_level=0;
  }
  
  public function select_by_id ( int $id ) {
    return $this->select_one( 'id', $id, '=' );
  }
  
  public function count (): int {
    $pdo = $this->pdo;
    $smt = $pdo->prepare( 'select count(*) as count from '.$this->table );
    $smt->execute();
    return $smt->fetch( PDO::FETCH_OBJ )->count;
  }
  
  public function delete_by_id ( int $id ) {
    return $this->delete( 'id', $id );
  }
  
  
}