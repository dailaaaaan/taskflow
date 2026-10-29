<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The GitHub account exposes no verified email, so it can be neither linked
 * to an existing account nor used to create a new one.
 */
final class GitHubEmailUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The GitHub account has no verified email address.');
    }
}
