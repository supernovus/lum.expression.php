<?php 
// Common testing code used by multiple tests

namespace Lum\Test;

const COMMON_PLAN = 26;

function runTests($t, $expConf)
{
  $expy = new \Lum\Expression\Parser($expConf);

  $in =
  [
    'postfix' =>
    [
      1,
      2,
      'gt',
      'not',
      4,
      4,
      'eq',
      'and',
    ],
    'prefix' =>
    [
      'and',
      'not',
      'gt',
      1,
      2,
      'eq',
      4,
      4,
    ],
    'infix' =>
    [
      '(',
        '(',
          'not',
          '(',
            1,
            'gt',
            2,
          ')',
        ')',
        'and',
        '(',
          4,
          'eq',
          4,
        ')',
      ')'
    ],
  ];
  
  // A 'loose' infix expression, depending on precedence rules.
  $loose_infix = ['not', 1, 'gt', 2, 'and', 4, 'eq', 4];

  $to_types = array_keys($in);
  
  $want_parsed = '[{"op":{"name":"and","operands":2,"precedence":10,"assoc":1},"items":[{"op":{"name":"not","operands":1,"precedence":20,"assoc":2},"items":[{"op":{"name":"gt","operands":2,"precedence":40,"assoc":1},"items":[1,2]}]},{"op":{"name":"eq","operands":2,"precedence":40,"assoc":1},"items":[4,4]}]}]';
  
  // First, let's do the 12 automated conversion tests.
  foreach ($in as $type => $exp_in)
  {
    $meth = 'load'.ucfirst($type);
    $expy->$meth($exp_in);
    $parsed_obj = $expy->getData();
    $parsed_json = json_encode($parsed_obj);
    $t->is($parsed_json, $want_parsed, "parsed $type");
    $exp_val = $expy->evaluate();
    $t->is($exp_val, true, "evaluated $type");
    foreach ($to_types as $to_type)
    {
      $tometh = 'save'.ucfirst($to_type);
      $saved = $expy->$tometh();
      $t->is($saved, $in[$to_type], "$type to $to_type");
    }
  }
  
  // Now let's try the loose infix parsing.
  $expy->loadInfix($loose_infix);
  $parsed_obj = $expy->getData();
  $parsed_json = json_encode($parsed_obj);
  $t->is($parsed_json, $want_parsed, "parsed loose infix");
  $exp_val = $expy->evaluate();
  $t->is($exp_val, true, "evaluated loose infix");
  foreach ($to_types as $to_type)
  {
    $tometh = 'save'.ucfirst($to_type);
    $saved = $expy->$tometh();
    $t->is($saved, $in[$to_type], "loose infix to $to_type");
  }
  
  $false_exp = [1,2,'gt'];
  $expy->loadPostfix($false_exp);
  $false_val = $expy->evaluate();
  $t->is($false_val, false, 'false expression evaluated');
  
  $num_exp = [1,2,'add',3, 'mult'];
  $expy->loadPostfix($num_exp);
  $num_val = $expy->evaluate();
  $t->is($num_val, 9, 'numeric expression evaluated');
  
  $neg_exp = [100,'negate'];
  $expy->loadPostfix($neg_exp);
  $neg_val = $expy->evaluate();
  $t->is($neg_val, -100, 'explicitly defined negation operator evaluated');
  
  $sqrt_exp = [9, 'sqrt'];
  $expy->loadPostfix($sqrt_exp);
  $sqrt_val = $expy->evaluate();
  $t->is($sqrt_val, 3.0, 'custom operator evaluated');
  
  $ifelse_exp_1 = [2,1,'gt','first','second','ifelse'];
  $expy->loadPostfix($ifelse_exp_1);
  $ifelse_val = $expy->evaluate();
  $t->is($ifelse_val, 'first', 'ternary operator with true');
  
  $ifelse_exp_2 = [1,2,'gt','first','second','ifelse'];
  $expy->loadPostfix($ifelse_exp_2);
  $ifelse_val = $expy->evaluate();
  $t->is($ifelse_val, 'second', 'ternary operator with false');
  
} // runTests()
