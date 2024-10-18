<?php
// Test with newer built-in operator definitions

namespace Lum\Test;

require_once 'vendor/autoload.php';
require_once 'test/inc/common.php';

use Lum\Expression\Precedence as LP;

$t = new \Lum\Test();
$t->plan(COMMON_PLAN);

$operators =
[
  'eq'   => true,
  'gt'   => true,
  'and'  => true,
  'not'  => true,
  'add'  => true,
  'mult' => true,
  // An operator using a built-in evaluator but with a custom name.
  'negate' => 'neg',
  // A custom operator.
  'sqrt'   => 
  [
    'precedence'=>LP::M_MD, 
    'unary'=>true, 
    'evaluate' =>
    function($items)
    {
      return sqrt($items[0]);
    }
  ],
  // One more custom operator that only works in prefix or postfix.
  // There's currently no way to support operands > 2 in infix.
  'ifelse' => ['precedence'=>LP::L_ALT, 'operands'=>3, 
    'evaluate'=> fn($items) => ($items[0] ? $items[1] : $items[2])
  ],
];

runTests($t, ['operators'=>$operators]);

echo $t->tap();
return $t;
