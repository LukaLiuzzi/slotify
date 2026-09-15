<?php

namespace App\Config;

class Security {
    public const MAX_LOGIN_ATTEMPTS = 5;
    public const LOCKOUT_MINUTES = 15;
    public const SESSION_LIFETIME = 7200;
    public const PASSWORD_MIN_LENGTH = 8;
    public const RESET_TOKEN_LIFETIME_HOURS = 2;
    public const OPENING_HOUR = 8;
    public const CLOSING_HOUR = 23;
    public const SLOT_DURATION_MINUTES = 60;
}
