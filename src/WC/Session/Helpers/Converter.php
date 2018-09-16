<?php

namespace WC\Session\Helpers;

use WC\Models\SessionModel;

final class Converter
{
    public static function toSessionValue($val): string {return serialize($val);}
    public static function fromSessionValue($val): SessionModel {return unserialize($val);}
}