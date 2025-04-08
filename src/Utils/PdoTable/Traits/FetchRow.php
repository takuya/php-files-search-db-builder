<?php

namespace Takuya\Utils\PdoTable\Traits;

use PDO;

trait FetchRow {
  public string $mapClass;
  public function fetch(\PDOStatement $st ){
    // fetchObject に関する注意
    // コンストラクタは呼ばれないので注意すること
    // キャストするだけ。Dynamicなプロパティ代入になる。
    return $st->fetchObject($this->mapClass??null);
  }
  public function readRows(\PDOStatement $st,callable $fn){
    while($obj = $this->fetch($st)){
      call_user_func($fn,$obj);
    }
  }
}