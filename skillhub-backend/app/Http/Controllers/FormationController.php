<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogService;

class FormationController extends Controller
{
    public function index(Request $request)
    {
        $query = Formation::with('formateur:id,nom,prenom');

        if ($request->has('search')) {
            $query->where('titre', 'like', '%' . $request->search . '%');
        }

        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->has('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        $formations = $query->withCount('enrollments')->get();

        return response()->json($formations);
    }

    public function show(Request $request, $id)
    {
        $formation = Formation::with([
            'formateur:id,nom,prenom',
            'modules' => fn($q) => $q->orderBy('ordre')
        ])->withCount('enrollments')->find($id);

        if (!$formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $formation->increment('nombre_vues');

        (new ActivityLogService())->log('course_view', [
            'course_id' => (int) $id,
            'user_id' => $request->auth_user_id ?? null,
        ]);

        return response()->json($formation);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre'       => 'required|string|max:255',
            'description' => 'required|string',
            'categorie'   => 'required|string',
            'niveau'      => 'required|in:Débutant,Intermédiaire,Avancé',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

       $user = (object)[
    'id' => $request->auth_user_id,
    'role' => $request->auth_user_role
];

        $formation = Formation::create([
            'titre'        => $request->titre,
            'description'  => $request->description,
            'categorie'    => $request->categorie,
            'niveau'       => $request->niveau,
            'formateur_id' => $user->id,
        ]);

        (new ActivityLogService())->log('course_created', [
            'course_id'  => $formation->id,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message'   => 'Formation créée avec succès',
            'formation' => $formation
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $formation = Formation::find($id);

        if (!$formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

       $user = (object)[
    'id' => $request->auth_user_id,
    'role' => $request->auth_user_role
];

        if ($formation->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez modifier que vos propres formations'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'categorie'   => 'sometimes|string',
            'niveau'      => 'sometimes|in:Débutant,Intermédiaire,Avancé',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $formation->only(['titre', 'description', 'categorie', 'niveau']);
        $formation->update($request->only(['titre', 'description', 'categorie', 'niveau']));
        $newValues = $formation->only(['titre', 'description', 'categorie', 'niveau']);

        (new ActivityLogService())->log('course_updated', [
            'course_id'  => (int) $id,
            'updated_by' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);

        return response()->json([
            'message'   => 'Formation mise à jour',
            'formation' => $formation
        ]);
    }

   public function destroy(Request $request, $id)
    {
        $formation = Formation::find($id);

        if (!$formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $user = (object)[
    'id' => $request->auth_user_id,
    'role' => $request->auth_user_role
];

        if ($formation->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez supprimer que vos propres formations'
            ], 403);
        }

        (new ActivityLogService())->log('course_deleted', [
            'course_id'  => (int) $id,
            'deleted_by' => $user->id,
            'titre'      => $formation->titre,
        ]);
        
        $formation->delete();

        return response()->json(['message' => 'Formation supprimée']);
    }

  public function mesFormations(Request $request)
    {
        $user = (object)[
    'id' => $request->auth_user_id,
    'role' => $request->auth_user_role
];

        $formations = Formation::where('formateur_id', $user->id)
            ->withCount(['enrollments', 'modules'])
            ->get();

        return response()->json($formations);
    }
}
