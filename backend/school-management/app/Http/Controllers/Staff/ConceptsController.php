<?php

namespace App\Http\Controllers\Staff;

use App\Core\Application\DTO\Response\PaymentConcept\ConceptRelationsToDisplay;
use App\Core\Application\Mappers\PaymentConceptMapper;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper as InfraPaymentConceptMapper;
use App\Core\Application\Services\Payments\Staff\ConceptsServiceFacades;
use App\Http\Controllers\Controller;
use App\Http\Requests\General\ForceRefreshRequest;
use App\Http\Requests\General\SearchRequest;
use App\Http\Requests\Payments\Staff\ConceptsIndexRequest;
use App\Http\Requests\Payments\Staff\StorePaymentConceptRequest;
use App\Http\Requests\Payments\Staff\UpdatePaymentConceptRelationsRequest;
use App\Http\Requests\Payments\Staff\UpdatePaymentConceptRequest;
use App\Models\PaymentConcept;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Payment Concepts",
 *     description="Gestión de conceptos de pago en el sistema (creación, actualización, activación, eliminación, etc.)"
 * )
 */
class ConceptsController extends Controller
{
    protected ConceptsServiceFacades $conceptsService;

    public function __construct(ConceptsServiceFacades $conceptsService)
    {
        $this->conceptsService= $conceptsService;


    }

    public function index(ConceptsIndexRequest $request)
    {
       $validated = $request->validated();

        $paginatedData = $this->conceptsService->showConcepts(
            $validated['status'] ?? 'todos',
            $validated['perPage'] ?? 15,
            $validated['page'] ?? 1,
            $validated['forceRefresh'] ?? false
        );
        return Response::success(
            ['concepts' => $paginatedData],
            empty($paginatedData->items) ? 'No hay conceptos de pago creados' : null
        );

    }

    public function findConcept(ForceRefreshRequest $request,int $id)
    {
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $concept=$this->conceptsService->findConcept($id, $forceRefresh);
        return Response::success(['concept' => $concept], 'Concepto encontrado.');

    }

    public function findRelations(ForceRefreshRequest $request,int $id)
    {
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $concept=$this->conceptsService->findRelations($id, $forceRefresh);
        return Response::success(['relations' => $concept], 'Relaciones del concepto encontradas.');
    }

    public function findNumberControlsBySearch(SearchRequest $request)
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? '';
        $limit = $validated['limit'] ?? 15;
        $forceRefresh = $validated['forceRefresh'] ?? false;
        $search=$this->conceptsService->findNumberControlsBySearch($search, $limit ,$forceRefresh);
        return Response::success(['search' => $search], 'Búsqueda exitosa');

    }

    public function store(StorePaymentConceptRequest $request)
    {
        $data = $request->validated();
        $dto = PaymentConceptMapper::toCreateConceptDTO($data);

        $createdConcept=$this->conceptsService->createPaymentConcept($dto);

        return Response::success(
            ['concept' => $createdConcept],
            'Concepto de pago creado con éxito.',
            201
        );

    }

    public function update(UpdatePaymentConceptRequest $request, int $id)
    {
        $data = $request->validated();
        $data['id'] = $id;
        $dto = PaymentConceptMapper::toUpdateConceptDTO($data);

        $updatedConcept = $this->conceptsService->updatePaymentConcept($dto);

        return Response::success(
            ['concept' => $updatedConcept],
            'Concepto de pago actualizado correctamente.'
        );
    }

    public function updateRelations(UpdatePaymentConceptRelationsRequest $request, int $id)
    {
        $data = $request->validated();
        $data['id'] = $id;
        $dto = PaymentConceptMapper::toUpdateConceptRelationsDTO($data);

        $updatedConcept = $this->conceptsService->updatePaymentConceptRelations($dto);

        return Response::success(
            ['concept' => $updatedConcept],
            'Relaciones del Concepto de pago actualizadas correctamente.'
        );
    }

    public function finalize(PaymentConcept $concept)
    {
        $domainConcept = InfraPaymentConceptMapper::toDomain($concept);
        $finalized = $this->conceptsService->finalizePaymentConcept($domainConcept);

        return Response::success(
            ['concept' => $finalized],
            'Concepto de pago finalizado correctamente.'
        );
    }

    public function disable(PaymentConcept $concept)
    {
        $domainConcept = InfraPaymentConceptMapper::toDomain($concept);
        $disable = $this->conceptsService->disablePaymentConcept($domainConcept);

        return Response::success(
            ['concept' => $disable],
            'Concepto de pago deshabilitado correctamente.'
        );
    }

    public function activate(PaymentConcept $concept)
    {
        $domainConcept = InfraPaymentConceptMapper::toDomain($concept);
        $activate = $this->conceptsService->activatePaymentConcept($domainConcept);

        return Response::success(
            ['concept' => $activate],
            'Concepto de pago habilitado correctamente.'
        );
    }

    public function eliminate(int $id)
    {
        $this->conceptsService->eliminatePaymentConcept($id);

         return Response::success(
            null,
            'Concepto de pago eliminado correctamente.'
        );
    }

    public function eliminateLogical(PaymentConcept $concept)
    {
        $domainConcept = InfraPaymentConceptMapper::toDomain($concept);
        $eliminate = $this->conceptsService->eliminateLogicalPaymentConcept($domainConcept);

        return Response::success(
            ['concept' => $eliminate],
            'Concepto de pago eliminado correctamente.'
        );
    }
}
