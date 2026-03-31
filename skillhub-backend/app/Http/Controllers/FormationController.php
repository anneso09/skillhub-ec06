<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    public function show($id)
    {
        $formation = Formation::with([
            'formateur:id,nom,prenom',
            'modules' => fn($q) => $q->orderBy('ordre')
        ])->withCount('enrollments')->find($id);

        if (!$formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $formation->increment('nombre_vues');

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

        $user = JWTAuth::user();

        $formation = Formation::create([
            'titre'        => $request->titre,
            'description'  => $request->description,
            'categorie'    => $request->categorie,
            'niveau'       => $request->niveau,
            'formateur_id' => $user->id,
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

        $user = JWTAuth::user();

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

        $formation->update($request->only(['titre', 'description', 'categorie', 'niveau']));

        return response()->json([
            'message'   => 'Formation mise à jour',
            'formation' => $formation
        ]);
    }

    public function destroy($id)
    {
        $formation = Formation::find($id);

        if (!$formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $user = JWTAuth::user();

        if ($formation->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez supprimer que vos propres formations'
            ], 403);
        }

        $formation->delete();

        return response()->json(['message' => 'Formation supprimée']);
    }

    public function mesFormations()
    {
        $user = JWTAuth::user();

        $formations = Formation::where('formateur_id', $user->id)
            ->withCount('enrollments')
            ->get();

        return response()->json($formations);
    }
}