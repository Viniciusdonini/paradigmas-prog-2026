<?php

namespace App\Http\Services;

use App\Http\Repositories\ClassroomRepository;
use Illuminate\Support\Facades\DB;

class ClassroomService extends BaseService
{
    public function __construct(private ClassroomRepository $classroomRepository)
    {
        parent::__construct($classroomRepository);
    }

    public function getWithFilters(array $data)
    {
        return $this->classroomRepository->getWithFilters($data);
    }

    public function manageUser(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $classroom = $this->classroomRepository->findForUpdate($id);

            if ($data['action'] === 'attach') {
                $this->classroomRepository->attachUser($classroom, (int) $data['user_id']);
            } else {
                $this->classroomRepository->detachUser($classroom, (int) $data['user_id']);
            }

            return $this->classroomRepository->show($id);
        });
    }
}
