<?php

namespace Zyimm\dbStructSync\constants;

class Advance
{
    const VIEW = "SELECT TABLE_NAME as Name FROM information_schema.VIEWS WHERE TABLE_SCHEMA='#'";

    const TRIGGER = "SELECT TRIGGER_NAME as Name FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='#'";

    const EVENT = "SELECT EVENT_NAME  as Name FROM information_schema.EVENTS WHERE EVENT_SCHEMA='#'";

    const _FUNCTION_ = "SHOW FUNCTION STATUS  WHERE Db='#'";

    const PROCEDURE = "show PROCEDURE STATUS WHERE Db='#'";

    public static $allow = [
        'ADD_VIEW', 'DROP_VIEW', 'ADD_TRIGGER', 'DROP_TRIGGER', 'ADD_EVENT', 'DROP_EVENT', 'ADD_FUNCTION',
        'DROP_FUNCTION', 'ADD_PROCEDURE', 'DROP_PROCEDURE'
    ];
}