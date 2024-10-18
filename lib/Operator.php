<?php

namespace Lum\Expression;

class Operator
{
  const ASSOC_NONE  = 0;
  const ASSOC_LEFT  = 1;
  const ASSOC_RIGHT = 2;

  public string $name;
  public int $operands   = 2;
  public int $precedence = 1;
  public int $assoc      = self::ASSOC_LEFT;

  protected $evaluator;

  public function __construct (string $name, array|bool|string $opts=[])
  {
    $builtins = Builtins::getInstance();

    $this->name = $name;

    if ($opts === true && $builtins->has($name))
    {
      $opts = $builtins->getDef($name);
    }
    else if (is_string($opts) && $builtins->has($opts))
    {
      $opts = $builtins->getDef($opts);
    }
    else if (!is_array($opts))
    {
      throw new \Exception("invalid operator definition: ".serialize($opts));
    }

    if (isset($opts['precedence']) && is_numeric($opts['precedence']))
    {
      $this->precedence = $opts['precedence'];
    }

    if (isset($opts['unary']) && $opts['unary'])
    {
      $this->operands = 1;
      $this->assoc = self::ASSOC_RIGHT;
    }
    elseif (isset($opts['operands']) && is_int($opts['operands']))
    {
      $this->operands = $opts['operands'];
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
    $builtins = Builtins::getInstance();

    if (is_string($evaluator) && $builtins->has($evaluator))
    {
      $evaluator = $builtins->getEval($evaluator);
    }

    if (is_callable($evaluator))
    {
      $this->evaluator = $evaluator;
    }
    else
    {
      throw new \Exception("Invalid evaluator passed to setEvaluator() "
        . serialize($evaluator));
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
