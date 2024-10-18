<?php

namespace Lum\Expression;

const K_PREC = "precedence";
const K_EVAL = "evaluate";
const K_UNRY = "unary";

/**
 * Built-in Operator definitions (singleton class)
 */
class Builtins 
{ // It's not as simple and clean as the JS version, but it works!

  protected $defs = [];
  protected static ?Builtins $instance;

  protected function __construct()
  {
    $this->defs['not'] =
    [
      K_PREC => Precedence::L_INV, 
      K_EVAL => fn($items) => !$items[0],
      K_UNRY => true
    ];
  
    $this->defs['eq'] = 
    [
      K_PREC => Precedence::C_REG,
      K_EVAL => fn($items) => $items[0] == $items[1]
    ];
  
    $this->defs['ne'] = 
    [
      K_PREC => Precedence::C_REG,
      K_EVAL => fn($items) => $items[0] != $items[1]
    ];
  
    $this->defs['gt'] = 
    [
      K_PREC => Precedence::C_REG,
      K_EVAL => fn($items) => $items[0] > $items[1]
    ];
  
    $this->defs['lt'] = 
    [
      K_PREC => Precedence::C_REG,
      K_EVAL => fn($items) => $items[0] < $items[1]
    ];
  
    $this->defs['gte'] = 
    [
      K_PREC => Precedence::C_REG,
      K_EVAL => fn($items) => $items[0] >= $items[1]
    ];
  
    $this->defs['lte'] = 
    [
      K_PREC => Precedence::C_REG,
      fn($items) => $items[0] <= $items[1]
    ];
  
    $this->defs['and'] = 
    [
      K_PREC => Precedence::L_ALT,
      K_EVAL => fn($items) => $items[0] && $items[1]
    ];
  
    $this->defs['or'] = 
    [
      K_PREC => Precedence::L_ALT,
      K_EVAL => fn($items) => $items[0] || $items[1]
    ];
  
    $this->defs['xor'] = 
    [
      K_PREC => Precedence::L_ALT,
      K_EVAL => fn($items) => ($items[0] xor $items[1])
    ];
  
    $this->defs['add'] = 
    [
      K_PREC => Precedence::M_AS,
      K_EVAL => fn($items) => $items[0] + $items[1]
    ];
  
    $this->defs['sub'] = 
    [
      K_PREC => Precedence::M_AS,
      K_EVAL => fn($items) => $items[0] - $items[1]
    ];
  
    $this->defs['mult'] = 
    [
      K_PREC => Precedence::M_MD,
      K_EVAL => fn($items) => $items[0] * $items[1]
    ];
  
    $this->defs['div'] = 
    [
      K_PREC => Precedence::M_MD,
      K_EVAL => fn($items) => $items[0] / $items[1]
    ];
  
    $this->defs['neg'] = 
    [
      K_PREC => Precedence::M_NEG,
      K_EVAL => fn($items) => $items[0] * -1,
      K_UNRY => true
    ];

  } // __construct()

  public function has(string $op)
  {
    return isset($this->defs[$op]);
  }

  public function getDef(string $op)
  {
    return $this->defs[$op];
  }

  public function getEval(string $op)
  {
    return $this->defs[$op][K_EVAL];
  }

  public static function getInstance()
  {
    if (!isset(static::$instance))
    {
      static::$instance = new static();
    }
    return static::$instance;
  }

} // Builtins
