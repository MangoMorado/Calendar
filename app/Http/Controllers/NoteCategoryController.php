<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteCategoryStoreRequest;
use App\Http\Requests\NoteCategoryUpdateRequest;
use App\Models\NoteCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $noteCategories = $request->user()
            ->noteCategories()
            ->withCount('notes')
            ->orderBy('name')
            ->get();

        return Inertia::render('note-categories/index', [
            'noteCategories' => $noteCategories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('note-categories/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NoteCategoryStoreRequest $request): RedirectResponse
    {
        $request->user()->noteCategories()->create($request->validated());

        return redirect()
            ->route('note-categories.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(NoteCategory $noteCategory): Response
    {
        $this->authorize('view', $noteCategory);

        $noteCategory->loadCount('notes');

        return Inertia::render('note-categories/show', [
            'noteCategory' => $noteCategory,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NoteCategory $noteCategory): Response
    {
        $this->authorize('update', $noteCategory);

        return Inertia::render('note-categories/edit', [
            'noteCategory' => $noteCategory,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NoteCategoryUpdateRequest $request, NoteCategory $noteCategory): RedirectResponse
    {
        $noteCategory->update($request->validated());

        return redirect()
            ->route('note-categories.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NoteCategory $noteCategory): RedirectResponse
    {
        $this->authorize('delete', $noteCategory);

        $noteCategory->delete();

        return redirect()
            ->route('note-categories.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }
}
