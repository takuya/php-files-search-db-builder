<?php

namespace Takuya\Utils\PdoTable;

use PDO;
use Takuya\Utils\PdoTable\Traits\TransactionBlock;
use Takuya\Utils\PdoTable\Traits\TableInfo;
use Takuya\Utils\PdoTable\Traits\GenericCRUD;
use Takuya\Utils\PdoTable\Traits\CountSelect;

class PdoTableRepository {
  use TransactionBlock;
  use TableInfo;
  use GenericCRUD;
  use CountSelect;
  
  public function __construct (
    public PDO       $pdo,
    protected string $table
  ) {
    $this->transaction_level = 0;
  }
  
  public function select_by_id ( int $id ) {
    return $this->select_one( 'id', $id, '=' );
  }
  
  
  public function delete_by_id ( int $id ) {
    return $this->delete( 'id', $id );
  }
  
  
}