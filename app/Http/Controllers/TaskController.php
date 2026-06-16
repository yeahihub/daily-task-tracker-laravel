<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Actions\Task\ListTasks;
use App\Actions\Task\CreateTask;
use App\Actions\Task\DeleteTask;
use App\Actions\Task\UpdateTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use App\Actions\Category\GetCategories;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Actions\Task\ToggleTaskCompletion;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Routing\ResponseFactory;

readonly class TaskController
{
    public function __construct(
        private GetCategories $getCategories,
        private Factory $view,
        private Redirector $redirector,
        private UrlGenerator $url,
        private ResponseFactory $responseFactory
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ListTasks $listTasks): View
    {
        $user    = $request->user();
        $filters = $request->only(['status', 'category_id', 'date_from', 'date_to']);

        $tasks = $listTasks->execute($user, $filters);

        return $this->view->make(
            'tasks.index',
            [
                'tasks'      => $tasks->toResourceCollection()->resolve(),
                'links'      => fn() => $tasks->links(),
                'categories' => $this->getCategories->execute($user->id),
                'filters'    => $filters,
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        return $this->view->make(
            'tasks.create',
            [
                'categories' => $this->getCategories->execute($request->user()->id),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request, CreateTask $createTask): RedirectResponse
    {
        $createTask->execute($request->validated(), $request->user());

        return $this->redirector->to($this->url->route('tasks.index'))
            ->with('success', 'Task created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Task $task): View
    {
        $task->load('category');

        return $this->view->make(
            'tasks.edit',
            [
                'task'       => $task->toResource()->resolve(),
                'categories' => $this->getCategories->execute($request->user()->id),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task, UpdateTask $updateTask): RedirectResponse
    {
        $updateTask->execute($task, $request->validated(), $request->user());

        return $this->redirector->to($this->url->route('tasks.index'))
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task, DeleteTask $deleteTask): Response
    {
        $deleteTask->execute($task);

        return $this->responseFactory->noContent();
    }

    /**
     * Toggle the completion status of the task.
     */
    public function toggleCompletion(Task $task, ToggleTaskCompletion $toggleTaskCompletion): JsonResponse
    {
        $completed = $toggleTaskCompletion->execute($task);

        return $this->responseFactory->json(
            [
                'completed' => $completed,
            ]
        );
    }
}
