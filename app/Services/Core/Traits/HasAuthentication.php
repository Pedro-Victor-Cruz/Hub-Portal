<?php

namespace App\Services\Core\Traits;

use App\Contracts\Auth\AuthHandlerInterface;

trait HasAuthentication
{
    protected ?AuthHandlerInterface $authHandler = null;

    /**
     * Obtém o handler de autenticação
     * @throws \Exception
     */
    public function getAuthHandler(): AuthHandlerInterface
    {
        if (!$this->authHandler) {
            $this->authHandler = $this->createAuthHandler();
        }
        return $this->authHandler;
    }

    /**
     * Autentica usando o handler apropriado
     */
    public function authenticate(): bool
    {
        return $this->getAuthHandler()->authenticate();
    }

    /**
     * Verifica se está autenticado
     */
    public function isAuthenticated(): bool
    {
        return $this->getAuthHandler()->isAuthenticated();
    }

    /**
     * Obtém o token de autenticação
     */
    public function getAuthToken(): ?string
    {
        return $this->getAuthHandler()->getAuthToken();
    }

    /**
     * Faz logout
     */
    public function logout(): bool
    {
        return $this->getAuthHandler()->logout();
    }

    /**
     * Cria o handler de autenticação apropriado
     * Deve ser implementado pela integração
     */
    abstract protected function createAuthHandler(): AuthHandlerInterface;
}