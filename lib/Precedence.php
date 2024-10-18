<?php

namespace Lum\Expression;

/**
 * A static class providing some constants
 */
class Precedence 
{
  const L_ALT = 10;
  const L_INV = 20;
  const C_REG = 40;
  const M_AS  = 60;
  const M_MD  = 70;
  const M_NEG = 80;
}
