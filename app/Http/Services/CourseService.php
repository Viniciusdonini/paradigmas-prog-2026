<?php

namespace App\Http\Services;

use App\Http\Repositories\CourseRepository;
use Illuminate\Support\Facades\DB;

class CourseService extends BaseService
{
    public function __construct(private CourseRepository $courseRepository)
    {
        parent::__construct($courseRepository);
    }

    public function getWithFilters(array $data)
    {
        return $this->courseRepository->getWithFilters($data);
    }

    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $course = $this->courseRepository->findForUpdate($id);

            abort_if(
                $this->courseRepository->hasClassrooms($course),
                409,
                'Não é possível excluir um curso que possui turmas. Remova ou transfira as turmas primeiro.'
            );

            parent::destroy($id);
        });
    }
}
