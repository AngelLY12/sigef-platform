<?php

namespace Tests\Stubs\Repositories\Command;

use App\Core\Domain\Repositories\Command\Auth\AccessTokenRepInterface;

class AccessTokenRepStub implements AccessTokenRepInterface
{
    private bool $simulateTokenNotFound = false;
    private bool $throwDatabaseError = false;
    private int $expiredTokensCount = 3;
    private int $tokensWithoutExpiration = 0;
    private array $tokens = [];

    public function __construct()
    {
        // Inicializar algunos tokens de prueba
        $this->initializeTestTokens();
    }

    private function initializeTestTokens(): void
    {
        // Tokens de prueba
        $this->tokens = [
            '1' => ['id' => 1, 'tokenable_id' => 1, 'name' => 'Test Token', 'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))],
            '2' => ['id' => 2, 'tokenable_id' => 2, 'name' => 'Another Token', 'expires_at' => date('Y-m-d H:i:s', strtotime('+2 days'))],
        ];
    }

    public function revokeToken(int $tokenId): bool
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (empty($tokenId)) {
            return false;
        }

        if ($this->simulateTokenNotFound || !isset($this->tokens[$tokenId])) {
            return false;
        }

        // Simular eliminación
        unset($this->tokens[$tokenId]);
        return true;
    }

    public function deletionInvalidTokens(): int
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        return $this->expiredTokensCount;

    }

    // Métodos de configuración para pruebas

    public function shouldSimulateTokenNotFound(bool $simulate = true): self
    {
        $this->simulateTokenNotFound = $simulate;
        return $this;
    }

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function setExpiredTokensCount(int $count): self
    {
        $this->expiredTokensCount = $count;
        return $this;
    }

    public function setTokensWithoutExpiration(int $count): self
    {
        $this->tokensWithoutExpiration = $count;
        return $this;
    }

    public function addToken(string $id, array $data): self
    {
        $this->tokens[$id] = $data;
        return $this;
    }

    public function getToken(string $id): ?array
    {
        return $this->tokens[$id] ?? null;
    }

    public function getTokensCount(): int
    {
        return count($this->tokens);
    }
}
