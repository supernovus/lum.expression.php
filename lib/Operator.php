<?php

namespace Lum\Expression;

class Operator
{
  const ASSOC_NONE  = 0;
  const ASSOC_LEFT  = 1;
  const ASSOC_RIGHT = 2;

  public $name;
  public $operands   = 2;
  public $precedence = 1;
  public $assoc      = self::ASSOC_LEFT;

  protected $evaluator;

  public function __construct ($name, $opts=[])
  {
    $this->name = $name;
    if (isset($opts['unary']) && $opts['unary'])
    {
      $this->operands = 1;
      $this->assoc = self::ASSOC_RIGHT;
    }

    if (isset($opts['operands']) && is_int($opts['operands']))
    {
      $this->operands = $opts['operands'];
    }

    if (isset($opts['precedence']) && is_numeric($opts['precedence']))
    {
      $this->precedence = $opts['precedence'];
    }

    if (isset($opts['assoc']))
    {
      if (is_numeric($opts['assoc']))
      {
        $this->assoc = $opts['assoc'];
      }
      elseif (is_string($opts['assoc']))
      {
        $assocStr = strtolower(substr($opts['assoc'], 0, 1));
        if ($assocStr == 'l')
        {
          $this->assoc = self::ASSOC_LEFT;
        }
        elseif ($assocStr == 'r')
        {
          $this->assoc = self::ASSOC_RIGHT;
        }
        else
        {
          $this->assoc = self::ASSOC_NONE;
        }
      }
      elseif ($opts['assoc'] === false)
      {
        $this->assoc = self::ASSOC_NONE;
      }
    }

    if (isset($opts['evaluate']))
    {
      if (is_callable($opts['evaluate']))
      { // Using a custom evaluator.
        $this->evaluator = $opts['evaluate'];
      }
      elseif (is_string($opts['evaluate']))
      { // Using a built-in evaluator.
        $this->setEvaluator($opts['evaluate']);
      }
      elseif ($opts['evaluate'] === true)
      { // Use the name as a built-in evaluator.
        $this->setEvaluator($name);
      }
      else
      {
        throw new \Exception("Invalid evaluator".$opts['evaluate']);
      }
    }
  }

  public function setEvaluator ($evaluator)
  {
    if (is_string($evaluator))
    {
      $evaluator = [$this, 'eval_'.strtolower($evaluator)];
    }
    if (is_callable($evaluator))
    {
      $this->evaluator = $evaluator;
    }
    else
    {
      throw new \Exception("Invalid evaluator passed to setEvaluator()");
    }
  }

  public function evaluate ($items)
  {
    if (!isset($this->evaluator))
    {
      throw new \Exception("Attempt to evaluate an operator without a handler.");
    }
    if (count($items) != $this->operands)
    {
      throw new \Exception("Invalid number of operands in operator evaluation.");
    }
    // Now make sure the items are scalar values, not objects.
    for ($i = 0; $i < count($items); $i++)
    {
      $item = $items[$i];
      if (is_object($item))
      { // It's a Condition, or a custom object.
        if (is_callable([$item, 'evaluate']))
        { // Get the value by evaluating it.
          $items[$i] = $item->evaluate();
        }
      }
    }
    // Okay, our items are all scalars now, let's do this.
    return call_user_func($this->evaluator, $items);
  }

  protected function eval_not ($items)
  { // Unary operator, only one item is used.
    return !($items[0]);
  }

  protected function eval_eq ($items)
  {
    return ($items[0] == $items[1]);
  }

  protected function eval_ne ($items)
  {
    return ($items[0] != $items[1]);
  }

  protected function eval_gt ($items)
  {
    return ($items[0] > $items[1]);
  }

  protected function eval_lt ($items)
  {
    return ($items[0] < $items[1]);
  }

  protected function eval_gte ($items)
  {
    return ($items[0] >= $items[1]);
  }

  protected function eval_lte ($items)
  {
    return ($items[0] <= $items[1]);
  }

  protected function eval_and ($items)
  {
    return ($items[0] and $items[1]);
  }

  protected function eval_or ($items)
  {
    return ($items[0] or $items[1]);
  }

  protected function eval_xor ($items)
  {
    return ($items[0] xor $items[1]);
  }

  protected function eval_add ($items)
  {
    return ($items[0] + $items[1]);
  }

  protected function eval_sub ($items)
  {
    return ($items[0] - $items[1]);
  }

  protected function eval_mult ($items)
  {
    return ($items[0] * $items[1]);
  }

  protected function eval_div ($items)
  {
    return ($items[0] / $items[1]);
  }

  protected function eval_neg ($items)
  { // Unary operator, only one item is used.
    return ($items[0] * -1);
  }

  public function leftAssoc ()
  {
    return $this->assoc === self::ASSOC_LEFT;
  }

  public function rightAssoc ()
  {
    return $this->assoc === self::ASSOC_RIGHT;
  }

  public function noAssoc ()
  {
    return $this->assoc === self::ASSOC_NONE;
  }
}

