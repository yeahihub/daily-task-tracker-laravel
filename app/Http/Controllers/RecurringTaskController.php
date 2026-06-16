<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TaskFrequency;
use Illuminate\Http\Request;
use App\Models\RecurringTask;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use App\Actions\Category\GetCategories;
use Illuminate\Contracts\Routing\UrlGenerator;
use App\Http\Requests\StoreRecurringTaskRequest;
use App\Actions\RecurringTask\ListRecurringTasks;
use App\Http\Requests\UpdateRecurringTaskRequest;
use Illuminate\Contracts\Routing\ResponseFactory;
use App\Actions\RecurringTask\CreateRecurringTask;
use App\Actions\RecurringTask\DeleteRecurringTask;
use App\Actions\RecurringTask\UpdateRecurringTask;

readonly class RecurringTaskController
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
    public function index(Request $request, ListRecurringTasks $listRecurringTasks): View
    {
        $recurringTasks = $listRecurringTasks->execute($request->user());

        return $this->view->make(
            'recurring-tasks.index',
            [
                'recurringTasks' => $recurringTasks->toResourceCollection()->resolve(),
                'links'          => fn() => $recurringTasks->links(),
                'categories'     => $this->getCategories->execute($request->user()->id),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return $this->view->make(
            'recurring-tasks.create',
            [
                'categories'  => $this->getCategories->execute($request->user()->id),
                'frequencies' => TaskFrequency::cases(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecurringTaskRequest $request, CreateRecurringTask $createRecurringTask)
    {
        $createRecurringTask->execute($request->validated(), $request->user());

        return $this->redirector->to($this->url->route('recurring-tasks.index'))
            ->with('success', 'Recurring task created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, RecurringTask $recurringTask)
    {
        $recurringTask->load('category');

        return $this->view->make(
            'recurring-tasks.edit',
            [
                'recurringTask' => $recurringTask->toResource()->resolve(),
                'categories'    => $this->getCategories->execute($request->user()->id),
                'frequencies'   => TaskFrequency::cases(),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateRecurringTaskRequest $request,
        RecurringTask $recurringTask,
        UpdateRecurringTask $updateRecurringTask
    ) {
        $updateRecurringTask->execute($recurringTask, $request->validated(), $request->user());

        return $this->redirector->to($this->url->route('recurring-tasks.index'))
            ->with('success', 'Recurring task updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecurringTask $recurringTask, DeleteRecurringTask $deleteRecurringTask): Response
    {
        $deleteRecurringTask->execute($recurringTask);

        return $this->responseFactory->noContent();
    }
}
