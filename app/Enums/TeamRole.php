<?php

namespace App\Enums;

/**
 * Role of a user inside a team. Persisted as its string value in
 * memberships.role; authorization rules live in the policies.
 */
enum TeamRole: string
{
    case Admin = 'admin';
    case Member = 'member';
}
