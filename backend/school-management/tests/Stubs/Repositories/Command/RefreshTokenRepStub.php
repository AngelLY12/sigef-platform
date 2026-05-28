<?php

namespace Tests\Stubs\Repositories\Command;

use App\Core\Domain\Entities\RefreshToken;
use App\Core\Domain\Repositories\Command\Auth\RefreshTokenRepInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RefreshTokenRepStub implements RefreshTokenRepInterface
{
    private bool $simulateTokenNotFound = false;
    private bool $throwModelNotFound = false;
    private bool $throwDatabaseError = false;
    private bool $simulateNotRevoked = true;
    private bool $enableTokenHashing = false;
    private int $invalidTokensCount = 3;
    private array $tokens = [];
    private array $tokenMap = []; // Mapa token_hash -> token_id
    private int $nextTokenId = 1;

    public function __construct()
    {
        $this->initializeTestTokens();
    }

    private function initializeTestTokens(): void
    {
        // Tokens de prueba
        $tokensData = [
            [
                'id' => 1,
                'user_id' => 1,
                'token' => 'valid_refresh_token',
                'expires_at' => CarbonImmutable::now()->addDays(1),
                'revoked' => false
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'token' => 'another_valid_token',
                'expires_at' => CarbonImmutable::now()->addDays(2),
                'revoked' => false
            ],
            [
                'id' => 3,
                'user_id' => 1,
                'token' => 'expired_token',
                'expires_at' => CarbonImmutable::now()->subDays(1),
                'revoked' => false
            ],
            [
                'id' => 4,
                'user_id' => 3,
                'token' => 'revoked_token',
                'expires_at' => CarbonImmutable::now()->addDays(1),
                'revoked' => true
            ],
        ];

        foreach ($tokensData as $tokenData) {
            $token = new RefreshToken(
                $tokenData['id'],
                $tokenData['user_id'],
                $tokenData['token'],
                $tokenData['expires_at'],
                $tokenData['revoked']
            );

            $this->tokens[$tokenData['id']] = $token;
            $this->tokenMap[$tokenData['token']] = $tokenData['id'];
        }
    }

    public function findByToken(string $token): ?RefreshToken
    {
        if ($this->throwModelNotFound) {
            throw new ModelNotFoundException('El token no fue encontrado');
        }

        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if ($this->simulateTokenNotFound) {
            return null;
        }

        $searchToken = $this->enableTokenHashing ? hash('sha256', $token) : $token;

        foreach ($this->tokens as $tokenObj) {
            $tokenValue = $this->enableTokenHashing ? hash('sha256', $tokenObj->token) : $tokenObj->token;

            if ($tokenValue === $searchToken) {
                return $tokenObj;
            }
        }

        return null;
    }

    public function revokeRefreshToken(string $refreshTokenValue): bool
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!$this->simulateNotRevoked) {
            return false;
        }

        $searchToken = $this->enableTokenHashing ? hash('sha256', $refreshTokenValue) : $refreshTokenValue;

        foreach ($this->tokens as $tokenId => $tokenObj) {
            $tokenValue = $this->enableTokenHashing ? hash('sha256', $tokenObj->token) : $tokenObj->token;

            if ($tokenValue === $searchToken && !$tokenObj->revoked) {
                // Simular revocación actualizando el token
                $this->tokens[$tokenId] = new RefreshToken(
                    $tokenObj->id,
                    $tokenObj->user_id,
                    $tokenObj->token,
                    $tokenObj->expiresAt,
                    true
                );
                return true;
            }
        }

        return false;
    }

    public function update(int $tokenId, array $fields): RefreshToken
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->tokens[$tokenId])) {
            throw new ModelNotFoundException('Token not found');
        }

        $existingToken = $this->tokens[$tokenId];

        // Crear un nuevo token con los campos actualizados
        // Nota: como las propiedades son readonly, creamos una nueva instancia
        $updatedToken = new RefreshToken(
            $existingToken->id,
            $existingToken->user_id,
            $existingToken->token,
            $fields['expires_at'] ?? $existingToken->expiresAt,
            $fields['revoked'] ?? $existingToken->revoked
        );

        $this->tokens[$tokenId] = $updatedToken;

        return $updatedToken;
    }

    public function delete(int $tokenId): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->tokens[$tokenId])) {
            throw new ModelNotFoundException('Token not found');
        }

        // Eliminar del mapa de tokens
        $token = $this->tokens[$tokenId];
        $tokenKey = array_search($tokenId, $this->tokenMap);
        if ($tokenKey !== false) {
            unset($this->tokenMap[$tokenKey]);
        }

        unset($this->tokens[$tokenId]);
    }

    public function deletionInvalidTokens(): int
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        // Simular eliminación de tokens inválidos
        $deleted = 0;
        $now = CarbonImmutable::now();

        foreach ($this->tokens as $id => $token) {
            if ($token->revoked || $token->isExpired()) {
                $deleted++;
                unset($this->tokens[$id]);

                // Eliminar del mapa
                $tokenKey = array_search($id, $this->tokenMap);
                if ($tokenKey !== false) {
                    unset($this->tokenMap[$tokenKey]);
                }
            }
        }

        // Retornar el contador configurado (para pruebas controladas)
        return $this->invalidTokensCount;
    }

    // Métodos de configuración para pruebas

    public function shouldSimulateTokenNotFound(bool $simulate = true): self
    {
        $this->simulateTokenNotFound = $simulate;
        return $this;
    }

    public function shouldThrowModelNotFound(bool $throw = true): self
    {
        $this->throwModelNotFound = $throw;
        return $this;
    }

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function shouldSimulateNotRevoked(bool $simulate = true): self
    {
        $this->simulateNotRevoked = $simulate;
        return $this;
    }

    public function enableTokenHashing(bool $enable = true): self
    {
        $this->enableTokenHashing = $enable;
        return $this;
    }

    public function setInvalidTokensCount(int $count): self
    {
        $this->invalidTokensCount = $count;
        return $this;
    }

    public function addToken(RefreshToken $token): self
    {
        $this->tokens[$token->id] = $token;
        $this->tokenMap[$token->token] = $token->id;
        return $this;
    }

    public function getToken(int $id): ?RefreshToken
    {
        return $this->tokens[$id] ?? null;
    }

    public function getTokensCount(): int
    {
        return count($this->tokens);
    }

    public function clearTokens(): self
    {
        $this->tokens = [];
        $this->tokenMap = [];
        return $this;
    }
}
