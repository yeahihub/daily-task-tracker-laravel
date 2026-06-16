<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use App\Actions\Category\CreateCategory;
use App\Actions\Category\DeleteCategory;
use App\Actions\Category\ListCategories;
use App\Actions\Category\UpdateCategory;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Contracts\Routing\UrlGenerator;

readonly class CategoryController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        private UrlGenerator $url,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ListCategories $listCategories): View
    {
        $categories = $listCategories->execute($request->user());

        return $this->view->make(
            'categories.index',
            [
                'categories' => $categories->toResourceCollection()->resolve(),
                'links'      => fn() => $categories->links(),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return $this->view->make('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request, CreateCategory $createCategory): RedirectResponse
    {
        $createCategory->execute($request->validated(), $request->user());

        return $this->redirector->to($this->url->route('categories.index'))
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): View
    {
        return $this->view->make(
            'categories.edit',
            [
                'category' => $category->toResource()->resolve(),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        UpdateCategory $updateCategory
    ): RedirectResponse {
        $updateCategory->execute($category, $request->validated());

        return $this->redirector->to($this->url->route('categories.index'))
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category, DeleteCategory $deleteCategory): Response
    {
        $deleteCategory->execute($category);

        return response()->noContent();
    }
}
