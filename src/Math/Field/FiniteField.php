<?php

namespace SmBc\Math\Field;

use SmBc\Math\BigInteger;

interface FiniteField
{
    public function getCharacteristic(): BigInteger;
    public function getDimension(): int;
}
