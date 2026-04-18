<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;

use App\Models\Formation;

use Illuminate\Http\Request;

use App\Services\ActivityLogService;
 
class EnrollmentController extends Controller

{

    public function store(Request $request, $formationId)

    {

        $formation = Formation::find($formationId);

        if (!$formation) {

            return response()->json(['message' => 'Formation introuvable'], 404);

        }
 
        $userId = $request->auth_user_id;
 
        $dejInscrit = Enrollment::where('utilisateur_id', $userId)

            ->where('formation_id', $formationId)

            ->exists();

        if ($dejInscrit) {

            return response()->json(['message' => 'Vous êtes déjà inscrit à cette formation'], 409);

        }
 
        $enrollment = Enrollment::create([

            'utilisateur_id' => $userId,

            'formation_id'   => $formationId,

            'progression'    => 0,

        ]);
 
        (new ActivityLogService())->log('course_enrollment', [

            'user_id'   => $userId,

            'course_id' => (int) $formationId,

        ]);
 
        return response()->json([

            'message'    => 'Inscription réussie',

            'enrollment' => $enrollment

        ], 201);

    }
 
    public function destroy(Request $request, $formationId)

    {

        $userId = $request->auth_user_id;
 
        $enrollment = Enrollment::where('utilisateur_id', $userId)

            ->where('formation_id', $formationId)

            ->first();

        if (!$enrollment) {

            return response()->json(['message' => 'Vous n\'êtes pas inscrit à cette formation'], 404);

        }
 
        $enrollment->delete();

        return response()->json(['message' => 'Désinscription réussie']);

    }
 
    public function mesFormations(Request $request)

    {

        $userId = $request->auth_user_id;
 
        $formations = Enrollment::where('utilisateur_id', $userId)

            ->with('formation.formateur:id,nom,prenom')

            ->get()

            ->map(function ($enrollment) {

                return [

                    'enrollment_id'    => $enrollment->id,

                    'progression'      => $enrollment->progression,

                    'date_inscription' => $enrollment->date_inscription,

                    'formation'        => $enrollment->formation,

                ];

            });
 
        return response()->json($formations);

    }
 
    public function updateProgression(Request $request, $formationId)

    {

        $userId = $request->auth_user_id;
 
        $enrollment = Enrollment::where('utilisateur_id', $userId)

            ->where('formation_id', $formationId)

            ->first();

        if (!$enrollment) {

            return response()->json(['message' => 'Vous n\'êtes pas inscrit à cette formation'], 404);

        }
 
        $request->validate([

            'progression' => 'required|integer|min:0|max:100'

        ]);
 
        $enrollment->update(['progression' => $request->progression]);
 
        return response()->json([

            'message'    => 'Progression mise à jour',

            'enrollment' => $enrollment

        ]);

    }

}
 