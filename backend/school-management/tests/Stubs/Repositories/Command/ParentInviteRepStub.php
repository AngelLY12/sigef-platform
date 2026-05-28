<?php

namespace Tests\Stubs\Repositories\Command;
use App\Core\Domain\Repositories\Command\Misc\ParentInviteRepInterface;
use App\Core\Domain\Entities\ParentInvite;
use Carbon\Carbon;

class ParentInviteRepStub implements ParentInviteRepInterface
{
    private bool $throwDatabaseError = false;
    private array $invites = [];
    private int $nextId = 1;

    public function __construct()
    {
        $this->initializeTestData();
    }

    private function initializeTestData(): void
    {
        // Invitaciones de prueba iniciales
        $this->invites = [
            1 => new ParentInvite(1, 'test1@example.com', 'token1', Carbon::now()->addDays(1), 2, 1),
            2 => new ParentInvite(3, 'test2@example.com', 'token2', Carbon::now()->subDays(1), 4, 2), // Expirada
            3 => new ParentInvite(5, 'test3@example.com', 'token3', Carbon::now()->addDays(2), 6, 3, Carbon::now()->subHours(2)), // Usada
        ];
        $this->nextId = 4;
    }

    public function create(ParentInvite $invite): ParentInvite
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $id = $invite->id ?? $this->nextId++;

        // Crear nueva instancia con ID asignado
        $newInvite = new ParentInvite(
            $invite->studentId,
            $invite->email,
            $invite->token,
            $invite->expiresAt,
            $invite->createdBy,
            $id,
            $invite->usedAt
        );

        $this->invites[$id] = $newInvite;

        return $newInvite;
    }

    public function markAsUsed(int $id): bool
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->invites[$id])) {
            return false;
        }

        $existingInvite = $this->invites[$id];

        // Si ya está usado, podría retornar false (depende de implementación)
        if ($existingInvite->usedAt !== null) {
            return false;
        }

        // Crear nueva instancia con usedAt actualizado
        $updatedInvite = new ParentInvite(
            $existingInvite->studentId,
            $existingInvite->email,
            $existingInvite->token,
            $existingInvite->expiresAt,
            $existingInvite->createdBy,
            $existingInvite->id,
            Carbon::now()
        );

        $this->invites[$id] = $updatedInvite;

        return true;
    }

    public function deleteExpired(): int
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $deletedCount = 0;
        $now = Carbon::now();

        foreach ($this->invites as $id => $invite) {
            if ($invite->isExpired()) {
                unset($this->invites[$id]);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    // Métodos de configuración para pruebas

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function addInvite(ParentInvite $invite): self
    {
        $id = $invite->id ?? $this->nextId++;

        if ($invite->id === null) {
            // Crear nueva instancia con ID asignado
            $inviteWithId = new ParentInvite(
                $invite->studentId,
                $invite->email,
                $invite->token,
                $invite->expiresAt,
                $invite->createdBy,
                $id,
                $invite->usedAt
            );
            $this->invites[$id] = $inviteWithId;
        } else {
            $this->invites[$id] = $invite;
            if ($id >= $this->nextId) {
                $this->nextId = $id + 1;
            }
        }

        return $this;
    }

    public function getInvite(int $id): ?ParentInvite
    {
        return $this->invites[$id] ?? null;
    }

    public function getInvitesCount(): int
    {
        return count($this->invites);
    }

    public function clearInvites(): self
    {
        $this->invites = [];
        $this->nextId = 1;
        return $this;
    }

    public function getAllInvites(): array
    {
        return $this->invites;
    }
}
