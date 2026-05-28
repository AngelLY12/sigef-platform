<?php

namespace App\Http\Controllers;

use App\Core\Application\Mappers\CareerMapper;
use App\Core\Application\Services\Misc\CareerServiceFacades;
use App\Http\Requests\Career\CreateCareerRequest;
use App\Http\Requests\Career\UpdateCareerRequest;
use App\Http\Requests\General\ForceRefreshRequest;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Careers",
 *     description="Operaciones relacionadas con carreras"
 * )
 */
class CareerController extends Controller
{
    private CareerServiceFacades $service;
    public function __construct(CareerServiceFacades $service)
    {
        $this->service=$service;
    }

    public function index(ForceRefreshRequest $request)
    {
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $careers=$this->service->findAllCareers($forceRefresh);
        return Response::success(['careers' => $careers], 'Carreras encontradas.');

    }

    public function show(ForceRefreshRequest $request,int $id)
    {
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $career = $this->service->findById($id, $forceRefresh);
        return Response::success(['career' => $career], 'Carrera encontrada.');

    }

    public function store(CreateCareerRequest $request)
    {
        $career = CareerMapper::toDomain($request->validated());

        $created = $this->service->createCareer($career);
        return Response::success(['career' => $created], 'Carrera creada.', 201);

    }

    public function update(UpdateCareerRequest $request, int $id)
    {
        $updated = $this->service->updateCareer($id, $request->validated());
        return Response::success(['updated' => $updated], 'Carrera actualizada.');

    }

    public function destroy(int $id)
    {
        $this->service->deleteCareer($id);
        return Response::success(null, 'Carrera eliminada con éxito.');
    }
}
