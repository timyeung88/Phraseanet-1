<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

namespace Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Enum extends Type
{
  const ENUM = 'enum';

  public function getName()
  {
    return static::ENUM;
  }

  public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
  {
    return $platform->getDoctrineTypeMapping('ENUM');
  }

}