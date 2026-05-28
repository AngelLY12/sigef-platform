<?php

namespace Tests\Stubs\Repositories\Command;
use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Models\User as ModelsUser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserRepositoryStub implements UserRepInterface
{
    private array $users = [];
    private array $tokens = [];
    private array $refreshTokens = [];
    private int $nextId = 1;

    public function __construct()
    {
        $this->seedInitialData();
    }

    private function seedInitialData(): void
    {
        // Usuario 1 - Activo con rol ADMIN
        $this->users[1] = new User(
            curp: 'GAPA950101HDFRRN09',
            name: 'Admin',
            last_name: 'Sistema',
            email: 'admin@test.com',
            password: Hash::make('password123'),
            phone_number: '+5215512345678',
            status: UserStatus::ACTIVO,
            registration_date: Carbon::now()->subDays(30),
            id: 1,
            birthdate: Carbon::create(1995, 1, 1),
            gender: \App\Core\Domain\Enum\User\UserGender::HOMBRE
        );

        // Usuario 2 - Eliminado (para pruebas de eliminación)
        $this->users[2] = new User(
            curp: 'PEPJ950423HDFRRL09',
            name: 'Juan',
            last_name: 'Pérez',
            email: 'juan@test.com',
            password: Hash::make('password123'),
            phone_number: '+5215512345679',
            status: UserStatus::ELIMINADO,
            registration_date: Carbon::now()->subDays(40), // Más de 30 días
            id: 2,
            birthdate: Carbon::create(1995, 4, 23),
            gender: \App\Core\Domain\Enum\User\UserGender::HOMBRE
        );

        // Usuario 3 - Activo con rol STUDENT
        $this->users[3] = new User(
            curp: 'ROML950615HDFRZN09',
            name: 'María',
            last_name: 'Rodríguez',
            email: 'maria@test.com',
            password: Hash::make('password123'),
            phone_number: '+5215512345680',
            status: UserStatus::ACTIVO,
            registration_date: Carbon::now()->subDays(10),
            id: 3,
            birthdate: Carbon::create(1995, 6, 15),
            gender: \App\Core\Domain\Enum\User\UserGender::MUJER
        );

        // Usuario 4 - Baja temporal
        $this->users[4] = new User(
            curp: 'LOGI950828HDFRSN09',
            name: 'Luis',
            last_name: 'Gómez',
            email: 'luis@test.com',
            password: Hash::make('password123'),
            phone_number: '+5215512345681',
            status: UserStatus::BAJA_TEMPORAL,
            registration_date: Carbon::now()->subDays(20),
            id: 4
        );
    }

    public function create(CreateUserDTO $user): \App\Models\User
    {
        $mappedData = UserMapper::toPersistence($user);

        $newUser = \App\Models\User::create($mappedData);

        $this->users[$newUser->id] = $newUser;
        return $newUser;
    }

    public function update(int $userId, array $fields): User
    {
        if (!isset($this->users[$userId])) {
            throw new \RuntimeException("Usuario no encontrado: $userId");
        }

        $user = $this->users[$userId];

        // Actualizar campos usando reflection
        $reflection = new \ReflectionClass($user);
        foreach ($fields as $field => $value) {
            if ($reflection->hasProperty($field)) {
                $property = $reflection->getProperty($field);
                $property->setAccessible(true);

                // Convertir string a enum si es necesario
                if ($property->getType()?->getName() === UserStatus::class && is_string($value)) {
                    $value = UserStatus::from($value);
                }

                $property->setValue($user, $value);
            }
        }

        return $user;
    }

    public function changeStatus(array $userIds, string $status): UserChangedStatusResponse
    {
        if (empty($userIds)) {
            return new UserChangedStatusResponse($status, 0);
        }

        $affected = 0;
        $statusEnum = UserStatus::from($status);

        foreach ($userIds as $userId) {
            if (isset($this->users[$userId]) && $this->users[$userId]->status !== $statusEnum) {
                $this->users[$userId]->status = $statusEnum;
                $affected++;
            }
        }

        return new UserChangedStatusResponse($status, $affected);
    }

    public function insertManyUsers(array $usersData): Collection
    {
        $inserted = collect();

        foreach ($usersData as $userData) {
            $userId = $this->nextId++;
            $user = new User(
                curp: $userData['curp'] ?? 'CURP' . $userId,
                name: $userData['name'] ?? 'Usuario ' . $userId,
                last_name: $userData['last_name'] ?? 'Apellido',
                email: $userData['email'] ?? "user{$userId}@test.com",
                password: $userData['password'] ?? Hash::make('password'),
                phone_number: $userData['phone_number'] ?? '+5215512345000',
                status: isset($userData['status']) ? UserStatus::from($userData['status']) : UserStatus::ACTIVO,
                registration_date: isset($userData['registration_date'])
                    ? Carbon::parse($userData['registration_date'])
                    : Carbon::now(),
                id: $userId,
                birthdate: isset($userData['birthdate']) ? Carbon::parse($userData['birthdate']) : null,
                gender: isset($userData['gender'])
                    ? \App\Core\Domain\Enum\User\UserGender::from($userData['gender'])
                    : null,
                address: $userData['address'] ?? null,
                blood_type: isset($userData['blood_type'])
                    ? \App\Core\Domain\Enum\User\UserBloodType::from($userData['blood_type'])
                    : null
            );

            $this->users[$userId] = $user;
            $inserted->push($this->createEloquentModelFromUser($user));
        }

        return $inserted;
    }

    public function insertSingleUser(array $userData): \App\Models\User
    {
        $userId = $this->nextId++;
        $user = \App\Models\User::factory()->create($userData);

        $this->users[$userId] = $user;
        return $user;
    }

    public function deletionEliminateUsers(): int
    {
        $thresholdDate = Carbon::now()->subDays(30);
        $deletedCount = 0;

        foreach ($this->users as $userId => $user) {
            if ($user->status === UserStatus::ELIMINADO &&
                $user->registration_date < $thresholdDate) {
                unset($this->users[$userId]);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    public function createToken(int $userId, string $name): string
    {
        if (!isset($this->users[$userId])) {
            throw new \RuntimeException("Usuario no encontrado: $userId");
        }

        $token = bin2hex(random_bytes(32));
        $this->tokens[$token] = [
            'user_id' => $userId,
            'name' => $name,
            'created_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMinutes(30)
        ];

        return $token;
    }

    public function assignRole(int $userId, string $role): bool
    {
        if (!isset($this->users[$userId])) {
            return false;
        }

        // Simular asignación de rol
        $user = $this->users[$userId];

        // En un stub real, aquí manejaríamos la lógica de roles
        // Por ahora solo retornamos true
        return true;
    }

    public function createRefreshToken(int $userId, string $name): string
    {
        if (!isset($this->users[$userId])) {
            throw new \RuntimeException("Usuario no encontrado: $userId");
        }

        $refreshToken = bin2hex(random_bytes(64));
        $this->refreshTokens[$refreshToken] = [
            'user_id' => $userId,
            'name' => $name,
            'created_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(7) // TTL por defecto
        ];

        return $refreshToken;
    }

    // Métodos auxiliares para testing
    public function getUser(int $userId): ?User
    {
        return $this->users[$userId] ?? null;
    }

    public function getUserCount(): int
    {
        return count($this->users);
    }

    public function getToken(string $token): ?array
    {
        return $this->tokens[$token] ?? null;
    }

    public function getRefreshToken(string $token): ?array
    {
        return $this->refreshTokens[$token] ?? null;
    }

    public function clear(): void
    {
        $this->users = [];
        $this->tokens = [];
        $this->refreshTokens = [];
        $this->nextId = 1;
        $this->seedInitialData();
    }

    private function createEloquentModelFromUser(User $user): ModelsUser
    {
        $model = new ModelsUser();
        $model->id = $user->id;
        $model->name = $user->name;
        $model->last_name = $user->last_name;
        $model->email = $user->email;
        $model->curp = $user->curp;
        $model->phone_number = $user->phone_number;
        $model->status = $user->status->value;
        $model->registration_date = $user->registration_date;
        $model->birthdate = $user->birthdate;
        $model->gender = $user->gender?->value;
        $model->address = $user->address;
        $model->blood_type = $user->blood_type?->value;

        return $model;
    }
}
