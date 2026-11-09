<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Http\Services\CourseService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CourseInput',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 255, example: 'Sistemas de Informação'),
        new OA\Property(property: 'description', type: 'string', maxLength: 5000, nullable: true),
    ]
)]
class CourseController extends Controller
{
    public function __construct(private CourseService $courseService) {}

    #[OA\Get(
        path: '/courses',
        summary: 'Lista cursos e suas turmas',
        security: [['sanctum' => []]],
        tags: ['Cursos'],
        parameters: [new OA\Parameter(name: 'name', in: 'query', schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Lista de cursos', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Course')),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        return CourseResource::collection($this->courseService->getWithFilters($request->all()));
    }

    #[OA\Post(
        path: '/courses',
        summary: 'Cadastra um curso',
        security: [['sanctum' => []]],
        tags: ['Cursos'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CourseInput')),
        responses: [
            new OA\Response(response: 201, description: 'Curso criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Course')])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
    public function store(CourseRequest $request)
    {
        return new CourseResource($this->courseService->store($request->validated()));
    }

    #[OA\Get(
        path: '/courses/{id}',
        summary: 'Exibe um curso e suas turmas',
        security: [['sanctum' => []]],
        tags: ['Cursos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Curso encontrado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Course')])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $id)
    {
        return new CourseResource($this->courseService->show($id));
    }

    #[OA\Put(
        path: '/courses/{id}',
        summary: 'Atualiza um curso (campos opcionais)',
        security: [['sanctum' => []]],
        tags: ['Cursos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 255),
            new OA\Property(property: 'description', type: 'string', maxLength: 5000, nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Curso atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Course')])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
    #[OA\Patch(
        path: '/courses/{id}',
        summary: 'Atualiza parcialmente um curso',
        security: [['sanctum' => []]],
        tags: ['Cursos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 255),
            new OA\Property(property: 'description', type: 'string', maxLength: 5000, nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Curso atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Course')])),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
    public function update(CourseRequest $request, string $id)
    {
        return new CourseResource($this->courseService->update($request->validated(), $id));
    }

    #[OA\Delete(
        path: '/courses/{id}',
        summary: 'Exclui um curso sem turmas',
        security: [['sanctum' => []]],
        tags: ['Cursos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Curso excluído'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, description: 'O curso ainda possui turmas'),
        ]
    )]
    public function destroy(string $id)
    {
        $this->courseService->destroy($id);

        return response()->noContent();
    }
}
