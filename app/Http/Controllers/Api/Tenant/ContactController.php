<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Contact::with('lead')
            ->withCount(['deals', 'followups', 'invoices']);

        if ($request->filled('search'))  $query->search($request->search);
        if ($request->filled('city'))    $query->where('city', $request->city);

        $sort    = $request->get('sort', 'created_at');
        $dir     = $request->get('dir', 'desc');
        $allowed = ['name', 'created_at', 'company', 'city'];
        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $perPage  = min($request->get('per_page', 15), 100);
        $contacts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => ContactResource::collection($contacts->items()),
            'meta'    => [
                'current_page' => $contacts->currentPage(),
                'last_page'    => $contacts->lastPage(),
                'per_page'     => $contacts->perPage(),
                'total'        => $contacts->total(),
            ],
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(ContactRequest $request): JsonResponse
    {
        $data              = $request->validated();
        $data['tenant_id'] = auth()->user()?->tenant_id ?? app('tenant_id');

        $contact = Contact::create($data);
        $contact->load('lead');

        return response()->json([
            'success' => true,
            'message' => 'Contact created successfully.',
            'data'    => new ContactResource($contact),
        ], 201);
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $contact = Contact::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with(['lead', 'deals', 'followups', 'invoices'])
            ->withCount(['deals', 'followups', 'invoices'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => new ContactResource($contact),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(ContactRequest $request, int $id): JsonResponse
    {
        $contact = Contact::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $contact->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Contact updated successfully.',
            'data'    => new ContactResource($contact->fresh('lead')),
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $contact = Contact::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully.',
        ]);
    }

    // ── Search (quick lookup) ─────────────────────────────────────
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2']]);

        $contacts = Contact::search($request->q)
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email', 'company']);

        return response()->json([
            'success' => true,
            'data'    => $contacts,
        ]);
    }
}