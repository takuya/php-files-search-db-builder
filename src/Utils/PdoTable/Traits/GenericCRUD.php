<?php

namespace Takuya\Utils\PdoTable\Traits;

use PDO;

trait GenericCRUD {
  public function insert ( array $col_val, bool $use_transaction = false ) {
    $fn = function() use ( $col_val ) {
      $sql = 'INSERT INTO '
        .$this->table.' '
        .'( '.implode( ',', array_keys( $col_val ) ).' ) '.
        'VALUES ('.implode( ',', array_map( fn( $k ) => ":{$k}", array_keys( $col_val ) ) ).')';;
      $pdo = $this->pdo;
      return $pdo->prepare( $sql )->execute( $col_val ) ? $pdo->lastInsertId() : false;
    };
    return $use_transaction ? $this->transaction( $fn ) : call_user_func( $fn );
  }
  
  protected function select_statment ( string $col, string $cond_or_val, string $val = null, $limit = null ) {
    [$cond, $val] = preg_match( "/=|<>|!=|>=|>|<|<=|is|like|match/i", $cond_or_val ) ? [$cond_or_val, $val] : ['=', $cond_or_val];
    $bindValues = [$col => $val];
    $pdo = $this->pdo;
    $sql = "select * from {$this->table} where {$col} {$cond} :{$col} ".( $limit ? ' limit :limit' : '' );
    $st = $pdo->prepare( $sql );
    $st->bindValue( $col, $val );
    //
    if ( !empty( $limit ) ) {
      $st->bindValue( 'limit', $limit );
      $bindValues['limit'] = $limit;
    }
    return [$st, $bindValues];
  }
  
  public function select ( string $col, string $cond_or_val, string $val = null, $limit = null ) {
    [$st, $vals] = $this->select_statment( ...func_get_args() );
    $st->execute();
    while ( $row = $st->fetch( PDO::FETCH_OBJ ) ) {
      yield $row;
    }
  }
  
  public function selectAll () {
    $st = $this->pdo->prepare( 'select * from '.$this->table.';' );
    $st->execute();
    while ( $row = $st->fetch( PDO::FETCH_OBJ ) ) {
      yield $row;
    }
  }
  
  public function select_one ( ...$args ): ?object {
    $limit = 1;
    $ret = $this->select( ...[...$args, $limit] );
    return $ret->valid() ? $ret->current() : null;
  }
  
  public function update ( array $key_value, int|array $target = null, int $limit = 1, bool $use_transaction = false ) {
    $fn = function() use ( $key_value, $target, $limit ) {
      $sets = implode( ', ', array_map( fn( $k ) => "{$k}=:{$k}", array_keys( $key_value ) ) );
      $row = match ( true ) {
        is_int( $target ) => ['col' => 'id', 'val' => $target],
        is_array( $target ) => ['col' => array_keys( $target )[0], 'val' => array_values( $target )[0]],
        default => null
      };
      $params = array_merge( $key_value, ( $row ? [$row['col'] => $row['val']] : [] ), ['limit' => $limit] );
      $sql = 'update '.$this->table
        .' SET '.$sets.' '
        .( $row ? "where {$row['col']} = :{$row['col']} " : '' )
        .' limit :limit';
      $pdo = $this->pdo;
      $st = $pdo->prepare( $sql );
      return $st->execute( $params );
    };
    return $use_transaction ? $this->transaction( $fn ) : call_user_func( $fn );
  }
  
  public function delete ( string $col, mixed $val, string $cond = '=', int $limit = 1,
                           bool   $use_transaction = false ) {
    $fn = function() use ( $col, $val, $cond, $limit ) {
      $pdo = $this->pdo;
      $cond = preg_match( '/like|=|>|<|match|is/i', $cond ) ? $cond : '=';
      $sql = 'DELETE from '.$this->table." where {$col}{$cond}:{$col} limit :limit";
      $stmt = $pdo->prepare( $sql );
      $stmt->bindValue( $col, $val );
      $stmt->bindValue( 'limit', $limit );
      return $stmt->execute();
    };
    return $use_transaction ? $this->transaction( $fn ) : call_user_func( $fn );
  }
  
  
}