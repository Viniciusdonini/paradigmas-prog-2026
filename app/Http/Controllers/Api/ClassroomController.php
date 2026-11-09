<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassroomRequest;
use App\Http\Requests\ClassroomUserRequest;
use App\Http\Resources\ClassroomResource;
use App\Http\Services\ClassroomService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ClassroomController extends Controller
{
    public function __construct(private ClassroomService $classroomService) {}

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/classrooms',
        summary: 'Lista as turmas',
        security: [['sanctum' => []]],
        tags: ['Turmas'],
        parameters: [
            new OA\Parameter(name: 'name', in: 'query', required: false, description: 'Filtra pelo nome (busca parcial)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'course_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de turmas',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Classroom'))])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        return ClassroomResource::collection($this->classroomService->getWithFilters($request->all()));
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/classrooms',
        summary: 'Cadastra uma turma',
        security: [['sanctum' => []]],
        tags: ['Turmas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'vacancies', 'course_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 500, example: 'Turma A'),
                    new OA\Property(property: 'vacancies', type: 'integer', minimum: 1, maximum: 500, example: 30),
                    new OA\Property(property: 'course_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Turma criada', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Classroom')])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
    public function store(ClassroomRequest $request)
    {
        return new ClassroomResource($this->classroomService->store($request->validated()));
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/classrooms/{id}',
        summary: 'Exibe uma turma',
        security: [['sanctum' => []]],
        tags: ['Turmas'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Turma encontrada', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Classroom')])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id)
    {
        return new ClassroomResource($this->classroomService->show($id));
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/classrooms/{id}',
        summary: 'Atualiza uma turma',
        security: [['sanctum' => []]],
        tags: ['Turmas'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'vacancies', 'course_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 500, example: 'Turma A'),
                    new OA\Property(property: 'vacancies', type: 'integer', minimum: 1, maximum: 500, example: 30),
                    new OA\Property(property: 'course_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Turma atualizada', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Classroom')])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
    public function update(ClassroomRequest $request, string $id)
    {
        return new ClassroomResource($this->classroomService->update($request->validated(), $id));
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/classrooms/{id}',
        summary: 'Remove uma turma',
        security: [['sanctum' => []]],
        tags: ['Turmas'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Turma removida'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function destroy(string $id)
    {
        $this->classroomService->destroy($id);

        return response()->noContent();
    }

    #[OA\Post(
        path: '/classrooms/{id}/users',
        summary: 'Inclui ou remove um usuário da turma',
        description: 'attach inclui sem duplicar vínculos; detach remove somente o usuário informado. Repetir a operação é seguro.',
        security: [['sanctum' => []]],
        tags: ['Turmas'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['user_id', 'action'],
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                new OA\Property(property: 'action', type: 'string', enum: ['attach', 'detach'], example: 'attach'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Turma e usuários após a operação', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Classroom')])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
    public function manageUser(ClassroomUserRequest $request, string $id)
    {
        return new ClassroomResource($this->classroomService->manageUser($id, $request->validated()));
    }
}
