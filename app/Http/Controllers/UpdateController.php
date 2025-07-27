<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Knowledge;
use App\Models\KnowledgeCategory;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateController extends Controller
{

    public function __construct() {}


    /**
     * @OA\Put(
     *     path="/app/update-user/{user_id}",
     *     summary="Aggiorna i dati di un utente esistente (query string o body)",
     *     tags={"Update"},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="ID dell'utente da aggiornare",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="firstname",
     *         in="query",
     *         required=false,
     *         description="Nome (come ?firstname=...)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="lastname",
     *         in="query",
     *         required=false,
     *         description="Cognome",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="birthday",
     *         in="query",
     *         required=false,
     *         description="Data di nascita (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="title_profile",
     *         in="query",
     *         required=false,
     *         description="Titolo profilo",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="level_profile_id",
     *         in="query",
     *         required=false,
     *         description="Livello profilo (1-3)",
     *         @OA\Schema(type="integer")
     *     ),
     *    @OA\Parameter(
     *         name="knowledges",
     *         in="query",
     *         required=false,
     *         style="deepObject",
     *         explode=true,
     *         description="Array di conoscenze, es: knowledges[0][id], knowledges[0][name], knowledges[0][level_id]",
     *         @OA\Schema(
     *             type="object",
     *             @OA\Property(
     *                 property="0",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="PHP"),
     *                 @OA\Property(property="level_id", type="integer", example=2)
     *             )
     *         )
     *     ),

     *     @OA\Response(
     *         response=200,
     *         description="Utente aggiornato con successo"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errore di validazione"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utente non trovato"
     *     )
     * )
     */



    public function updateUser(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'firstname' => ['string'],
                    'lastname' => ['string'],
                    'birthday' => ['date'],
                    'title_profile' => ['string'],
                    'level_profile_id' => ['int'],
                    'knowledges' => ['array'],
                    'knowledges.*.id' => ['int'],
                    'knowledges.*.name' => ['string'],
                    'knowledges.*.level_id' => ['int', 'between:1,3'],
                ]
            );

            if ($validator->fails()) {
                throw new Exception("Modifica dati utente fallita: {$validator->errors()}", 422);
            }

            $user = User::where('id', $request->user_id)->with('profile.category')->first();

            if (!$user) {
                throw new Exception('L\'Utente su cui vuoi apportare le modifiche non è presente nel database', 404);
            }

            DB::beginTransaction();

            if (!empty($request->firstname)) {
                $user->firstname = $request->firstname;
            }

            if (!empty($request->lastname)) {
                $user->lastname = $request->lastname;
            }

            if (!empty($request->birthday)) {
                $user->birthday = $request->birthday;
            }

            if (!empty($request->title_profile)) {
                $user->profile->title = $request->title_profile;

                if (!empty($request->level_profile_id)) {
                    $user->profile->level_id = $request->level_profile_id;
                }
            }

            $category_id = $user->profile->category->id;
            if (!empty($request->knowledges)) {

                foreach ($request->knowledges as $knowledge) {

                    if (!empty($knowledge['id'])) {
                        $kno_delete = KnowledgeCategory::where('knowledge_id', $knowledge['id'])->where('category_id', $category_id)->first();
                        if (!$kno_delete) {
                            throw new Exception('Non è possibie effettuare le modifiche sul seguente profilo utente.', 404);
                        }
                        $kno_delete->delete();
                    } else {
                        if (empty(($knowledge['name'])) && !empty(($knowledge['level_id']))) {
                            throw new Exception("Per aggiungere una nuova conoscenza inserire sia il tipo che il livello della stessa.", 422);
                        }
                    }

                    $knowledge_update = Knowledge::when($knowledge['name'], function ($query) use ($knowledge) {
                        $query->where('description', $knowledge['name'])->where('level_id', $knowledge['level_id']);
                    })->first();

                    if (!$knowledge_update) {
                        $knowledge_update = new Knowledge();
                    };

                    if (!empty(($knowledge['name']))) {
                        $knowledge_update->description = $knowledge['name'];
                    }

                    if (!empty(($knowledge['level_id']))) {
                        $knowledge_update->level_id = $knowledge['level_id'];
                    }
                    $knowledge_update->save();

                    $kno_category = new KnowledgeCategory();
                    $kno_category->knowledge_id = $knowledge_update->id;
                    $kno_category->category_id = $user->profile->category->id;
                    $kno_category->save();
                }
            }

            $user->save();

            DB::commit();

            $user_new = User::where('id', $request->user_id)->with('profile.category.knowledge')->first();
            return response($user_new);
        } catch (Exception $ex) {
            DB::rollBack();
            return response($ex->getMessage(), 300 ?? $ex->getCode());
        }
    }
}
