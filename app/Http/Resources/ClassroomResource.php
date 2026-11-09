<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Classroom',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Turma A'),
        new OA\Property(property: 'vacancies', type: 'integer', example: 30),
        new OA\Property(property: 'course_id', type: 'integer', example: 1),
        new OA\Property(property: 'course', ref: '#/components/schemas/Course'),
        new OA\Property(property: 'users', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ClassroomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vacancies' => $this->vacancies,
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
