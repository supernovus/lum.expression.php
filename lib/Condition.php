<?php

namespace Lum\Expression;

class Condition
{
  public Operator $op;
  public array $items;

  public function __construct (Operator $op, array $items)
  {
    $this->op = $op;
    $this->items = $items;
  }

  public function evaluate ()
  {
    return $this->op->evaluate($this->items);
  }
}

