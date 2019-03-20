<?php

namespace App\Extensions\SMSc;

abstract class Format
{
    const DEFAULT   = '';
    const FLASH     = 'flash=1';
    const PUSH      = 'push=1';
    const HLR       = 'hlr=1';
    const BIN       = 'bin=1';
    const BINHEX    = 'bin=2';
    const PING      = 'ping=1';
    const MMS       = 'mms=1';
    const MAIL      = 'mail=1';
    const CALL      = 'call=1';
    const VIBER     = 'viber=1';
}
