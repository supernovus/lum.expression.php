<?php
// Test with the older operator definitions

namespace Lum\Test;

require_once 'vendor/autoload.php';
require_once 'test/inc/common.php';

use Lum\Expression\Precedence as LP;

$t = new \Lum\Test();
$t->plan(COMMON_PLAN);

$operators =
[
  'eq'   => ['precedence'=>LP::C_REG, 'evaluate'=>true],
  'gt'   => ['precedence'=>LP::C_REG, 'evaluate'=>true],
  'and'  => ['precedence'=>LP::L_ALT, 'evaluate'=>true],
  'not'  => ['precedence'=>LP::L_INV, 'unary'=>true, 'evaluate'=>true],
  'add'  => ['precedence'=>LP::M_AS, 'evaluate'=>true],
  'mult' => ['precedence'=>LP::M_MD, 'evaluate'=>true],
  // An operator using a built-in evaluator but with a custom name.
  'negate' => ['precedence'=>LP::M_NEG, 'unary'=>true, 'evaluate'=>'neg'],
  // A custom operator.
  'sqrt'   => ['precedence'=>LP::M_MD, 'unary'=>true, 
    'evaluate'=>fn($items) => sqrt($items[0])
  ],
  // One more custom operator that only works in prefix or postfix.
  // There's currently no way to support operands > 2 in infix.
  'ifelse' => ['precedence'=>LP::L_ALT, 'operands'=>3, 'evaluate'=>
    function ($items)
    {
      return ($items[0] ? $items[1] : $items[2]);
    }
  ],
];

runTests($t, ['operators'=>$operators]);

echo $t->tap();
return $t;
