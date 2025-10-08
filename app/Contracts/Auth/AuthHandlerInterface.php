<?php

namespace App\Contracts\Auth;

interface AuthHandlerInterface
{
    public function authenticate(): bool;
    public function isAuthenticated(): bool;
    public function getAuthType(): string;
    public function logout(): bool;
    public function getAuthToken(): mixed;
}