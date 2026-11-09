<?php

namespace App\Http\Repositories;

use App\Models\Course;

class CourseRepository extends BaseRepository
{
    public function __construct(private Course $model)
    {
        parent::__construct($model);
    }

    public function getWithFilters(array $data)
    {
        $builder = $this->model->query()->with('classrooms');

        if (data_get($data, 'name')) {
            $builder->where('name', 'like', '%'.$data['name'].'%');
        }

        return $this->index($builder);
    }

    public function show(string $id)
    {
        return parent::show($id)->load('classrooms');
    }

    public function findForUpdate(string $id): Course
    {
        return $this->model->query()->lockForUpdate()->findOrFail($id);
    }

    public function hasClassrooms(Course $course): bool
    {
        return $course->classrooms()->exists();
    }
}
