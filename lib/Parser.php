<?php

namespace Lum\Expression;

class Parser
{
  protected $data;
  protected $operators = [];

  protected $lp = '(';
  protected $rp = ')';

  public function __construct ($opts=[])
  {
    if (isset($opts['operators']) && is_array($opts['operators']))
    {
      foreach ($opts['operators'] as $opname => $opopts)
      {
        $this->addOperator($opname, $opopts);
      }
    }

    if (isset($opts['lp']) && is_string($opts['lp']))
    {
      $this->lp = $opts['lp'];
    }
    if (isset($opts['rp']) && is_string($opts['rp']))
    {
      $this->rp = $opts['rp'];
    }
  }

  public function addOperator ($name, $opts=[])
  {
    if ($name instanceof Operator)
    {
      $this->operators[$name->name] = $name;
    }
    elseif (is_string($name))
    {
      $this->operators[$name] = new Operator($name, $opts);
    }
    else
    {
      throw new \Exception("addOperator must be sent a name, or an Operator instance.");
    }
  }

  public function loadInfix (Array $data)
  { // Convert to postfix using Shunting-Yard, then parse that data.
    // TODO: Handle unary operators differently.
    $this->data = [];
    $operators = [];
    $operands  = [];
    $len = count($data);
    for ($c = 0; $c < $len; $c++)
    {
      $v = $data[$c];
      if (isset($this->operators[$v]))
      { // It's an operator.
        $op = $this->operators[$v];
        $op2 = end($operators);
        while ($op2 
          && $op2 !== $this->lp 
          && (
            ($op->leftAssoc() && $op->precedence <= $op2->precedence)
            ||
            ($op->rightAssoc() && $op->precedence < $op2->precedence)
          )
        )
        {
          $operands[] = array_pop($operators)->name;
          $op2 = end($operators);
        }
        $operators[] = $op;
      }
      elseif ($v === $this->lp)
      { // It's a left paranthesis.
        $operators[] = $v;
      }
      elseif ($v === $this->rp)
      { // It's a right paranthesis.
        while (end($operators) !== $this->lp)
        {
          $operands[] = array_pop($operators)->name;
          if (!$operators)
          {
            throw new \Exception('Mismatched parenthesis');
          }
        }
        array_pop($operators);
      }
      else
      { // It's an operand.
        $operands[] = $v;
      }
    }
    while ($operators)
    {
      $op = array_pop($operators);
      if ($op === $this->lp)
      {
        throw new \Exception('Mismatched perenthesis');
      }
      $operands[] = $op->name;
    }
    //error_log("infix to postfix: ".json_encode($operands));
    return $this->loadPostfix($operands);
  }

  public function loadPrefix (Array $data)
  {
    $this->data = [];
    $len = count($data);
    for ($c = $len-1; $c >= 0; $c--)
    {
      $v = $data[$c];
      if (isset($this->operators[$v]))
      { // It's an operator, do the thing.
        $op = $this->operators[$v];
        $s = $op->operands;
        $z = count($this->data);
        if ($z < $s)
        {
          throw new \Exception("Operator $v requires $s operands, only $z found.");
        }
        $substack = array_reverse(array_splice($this->data, $z-$s, $s));
        $this->data[] = new Condition($op, $substack);
      }
      elseif ($v == $this->lp || $v == $this->rp)
      { // Parens are ignored in prefix.
        continue;
      }
      else
      { // It's an operand, add it to the stack.
        $this->data[] = $v;
      }
    }
  }

  public function loadPostfix (Array $data)
  {
    $this->data = [];
    $len = count($data);
    for ($c = 0; $c < $len; $c++)
    {
      $v = $data[$c];
      if (isset($this->operators[$v]))
      { // It's an operator, do the thing.
        $op = $this->operators[$v];
        $s = $op->operands;
        $z = count($this->data);
        if ($z < $s)
        {
          throw new \Exception("Operator $v requires $s operands, only $z found.");
        }
        $substack = array_splice($this->data, $z-$s, $s);
        $this->data[] = new Condition($op, $substack);
      }
      elseif ($v == $this->lp || $v == $this->rp)
      { // Parens are ignored in postfix.
        continue;
      }
      else
      { // It's an operand, add it to the stack.
        $this->data[] = $v;
      }
    }
  }

  public function getData ()
  {
    return $this->data;
  }

  public function saveInfix ()
  {
    $in = $this->data;
    $out = [];
    foreach ($in as $item)
    {
      $this->serialize_infix_item($item, $out);
    }
    return $out;
  }

  protected function serialize_infix_item ($item, &$out)
  {
    if ($item instanceof Condition)
    {
      $this->serialize_infix_condition($item, $out);
    }
    else
    {
      $out[] = $item;
    }
  }

  protected function serialize_infix_condition ($item, &$out)
  {
    $out[] = $this->lp;
    $opn = $item->op;
    $ops = count($item->items);
    if ($ops == 2)
    {
      $this->serialize_infix_item($item->items[0], $out);
      $out[] = $opn->name;
      $this->serialize_infix_item($item->items[1], $out);
    }
    elseif ($ops == 1)
    {
      $out[] = $opn->name;
      $this->serialize_infix_item($item->items[0], $out);
    }
    else
    {
      throw new \Exception("Operator must have only 1 or 2 operands, {$opn->name} has $ops which is invalid.");
    }
    $out[] = $this->rp;
  }

  public function savePrefix ()
  {
    $out = [];
    $this->serialize_prefix($this->data, $out);
    return $out;
  }

  protected function serialize_prefix ($in, &$out)
  {
    foreach ($in as $item)
    {
      if ($item instanceof Condition)
      { // It's an operator.
        $out[] = $item->op->name;
        $this->serialize_prefix($item->items, $out);
      }
      else
      { // It's an operand.
        $out[] = $item;
      }
    }
  }

  public function savePostfix ()
  {
    $out = [];
    $this->serialize_postfix($this->data, $out);
    return $out;
  }

  protected function serialize_postfix ($in, &$out)
  {
    foreach ($in as $item)
    {
      if ($item instanceof Condition)
      { // It's an operator.
        $this->serialize_postfix($item->items, $out);
        $out[] = $item->op->name;
      }
      else
      { // It's an operand.
        $out[] = $item;
      }
    }
  }

  public function evaluate ()
  {
    if (count($this->data) > 1)
    {
      throw new \Exception("Expression does not parse to a single top item, cannot evaluate.");
    }
    $topItem = $this->data[0];
    if ($topItem instanceof Condition)
    {
      return $topItem->evaluate();
    }
    else
    {
      return $topItem;
    }
  }

}

