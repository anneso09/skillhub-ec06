<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ModuleController extends Controller
{
    public function index($formationId)
    {
        $formation = Formation::find($formationId);

        if (!$formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $modules = Module::where('formation_id', $formationId)
            ->orderBy('ordre')
            ->get();

        return response()->json($modules);
    }

    public function store(Request $request, $formationId)
    {
        $formation = Formation::find($formationId);

        if (!$formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $user = JWTAuth::user();

        if ($formation->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez gérer que les modules de vos propres formations'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre'   => 'required|string|max:255',
            'contenu' => 'required|string',
            'ordre'   => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ordre = $request->ordre ?? Module::where('formation_id', $formationId)->max('ordre') + 1;

        $module = Module::create([
            'titre'        => $request->titre,
            'contenu'      => $request->contenu,
            'formation_id' => $formationId,
            'ordre'        => $ordre,
        ]);

        return response()->json([
            'message' => 'Module créé avec succès',
            'module'  => $module
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $module = Module::find($id);

        if (!$module) {
            return response()->json(['message' => 'Module introuvable'], 404);
        }

        $user = JWTAuth::user();

        if ($module->formation->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez modifier que vos propres modules'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre'   => 'sometimes|string|max:255',
            'contenu' => 'sometimes|string',
            'ordre'   => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $module->update($request->only(['titre', 'contenu', 'ordre']));

        return response()->json([
            'message' => 'Module mis à jour',
            'module'  => $module
        ]);
    }

    public function destroy($id)
    {
        $module = Module::find($id);

        if (!$module) {
            return response()->json(['message' => 'Module introuvable'], 404);
        }

        $user = JWTAuth::user();

        if ($module->formation->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez supprimer que vos propres modules'
            ], 403);
        }

        $module->delete();

        return response()->json(['message' => 'Module supprimé']);
    }
}