<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteStoreRequest;
use App\Http\Requests\NoteUpdateRequest;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $notes = Note::query()
            ->visibleToUser($userId)
            ->with(['user', 'noteCategory'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->search.'%')
                        ->orWhere('content', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('note_category_id', $request->category_id);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $noteCategories = $request->user()->noteCategories()->orderBy('name')->get();

        return Inertia::render('notes/index', [
            'notes' => $notes,
            'noteCategories' => $noteCategories,
            'filters' => $request->only(['search', 'category_id']),
            'canCreate' => $request->user()->can('create', Note::class),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Note::class);

        $noteCategories = $request->user()->noteCategories()->orderBy('name')->get();

        return Inertia::render('notes/create', [
            'noteCategories' => $noteCategories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NoteStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        Note::create($data);

        return redirect()
            ->route('notes.index')
            ->with('success', 'Nota creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Note $note): Response
    {
        $this->authorize('view', $note);

        $note->load(['user', 'noteCategory']);

        return Inertia::render('notes/show', [
            'note' => $note,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Note $note): Response
    {
        $this->authorize('update', $note);

        $noteCategories = $request->user()->noteCategories()->orderBy('name')->get();

        return Inertia::render('notes/edit', [
            'note' => $note,
            'noteCategories' => $noteCategories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NoteUpdateRequest $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        return redirect()
            ->route('notes.index')
            ->with('success', 'Nota actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return redirect()
            ->route('notes.index')
            ->with('success', 'Nota eliminada exitosamente.');
    }

    /**
     * Generate or return share token and public URL.
     */
    public function share(Request $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        if (! $note->share_token) {
            $note->generateShareToken();
        }

        $url = route('notes.public.show', ['token' => $note->share_token]);

        return response()->json([
            'share_token' => $note->share_token,
            'public_url' => $url,
        ]);
    }
}
