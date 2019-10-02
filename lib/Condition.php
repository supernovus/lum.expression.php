<?php

namespace Lum\Expression;

class Condition
{
  public $op;
  public $items;

  public function __construct (Operator $op, $items)
  {
    $this->op = $op;
    $this->items = $items;
  }

  public function evaluate ()
  {
    return $this->op->evaluate($this->items);
  }
}

