<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Knowledge;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    public function __construct() {}

    /**
     * @OA\Get(
     *     path="/app/get-users",
     *     summary="Ricerca utenti filtrabili per nome, cognome, titolo profilo, conoscenze e livelli relativi",
     *     tags={"Search"},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nome o cognome dell'utente",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="title_profile",
     *         in="query",
     *         description="Titolo del profilo",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="knowledge",
     *         in="query",
     *         description="Conoscenza specifica da cercare (es. CSS)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="level_knowledge",
     *         in="query",
     *         description="Livello della conoscenza (es. 1=Base, 2=Intermedio, 3=Avanzato)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ricerca effettuata con successo"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errore di validazione dati"
     *     )
     * )
     */


    public function getUsers(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => ['string'],
                    'title_profile' => ['string'],
                    'knowledge' => ['string'],
                    'level_knowledge' => ['int']
                ]
            );

            if ($validator->fails()) {
                throw new Exception("Ricerca fallita: {$validator->errors()}", 422);
            }

            $users = User::when(!empty($request->name), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('firstname', 'like', "%{$request->name}%")
                        ->orWhere('lastname', 'like', "%{$request->name}%");
                });
            })->when(!empty($request->knowledge), function ($query) use ($request) {
                $query->whereHas('profile.category.knowledge', function ($query) use ($request) {
                    $query->where('description', 'like', "%{$request->knowledge}%")
                        ->where(function ($query) use ($request) {
                            $query->when(!empty($request->level_knowledge), function ($query) use ($request) {
                                $query->where('level_id', $request->level_knowledge);
                            });
                        });
                });
            })
                ->when(!empty($request->title_profile), function ($query) use ($request) {
                    $query->whereHas('profile', function ($query) use ($request) {
                        $query->where('title', 'like', "%{$request->title_profile}%");
                    });
                })
                ->with('profile.category.knowledge')
                ->get();

            return response($users);
        } catch (Exception $ex) {
            return response($ex->getMessage(), $ex->getCode());
        }
    }

    
    /**
     * @OA\Get(
     *     path="/app/get-categories",
     *     summary="Ricerca categoria per descrizione della stessa",
     *     tags={"Search"},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Nome categoria",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ricerca effettuata con successo"
     *     ),
     *    @OA\Response(
     *         response=422,
     *         description="Errore di validazione dati"
     *     )
     * )
     */

    public function getCategories(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'keyword' => ['string']
                ]
            );

            if ($validator->fails()) {
                throw new Exception("Ricerca fallita: {$validator->errors()}", 422);
            }

            $categories = Category::when(!empty($request->keyword), function ($query) use ($request) {
                $query->where('description', 'like', "%{$request->keyword}%");
            })->with('knowledge')
                ->get();

            return response($categories);
        } catch (Exception $ex) {
            return response($ex->getMessage(), $ex->getCode());
        }
    }


        /**
     * @OA\Get(
     *     path="/app/get-knowledges",
     *     summary="Ricerca conoscenza/skills contenuta nel databse",
     *     tags={"Search"},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Nome skills",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ricerca effettuata con successo"
     *     ),
     *    @OA\Response(
     *         response=422,
     *         description="Errore di validazione dati"
     *     )
     * )
     */

    public function getKnowledges(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'keyword' => ['string']
                ]
            );

            if ($validator->fails()) {
                throw new Exception("Ricerca fallita: {$validator->errors()}", 422);
            }

            $knowledges = Knowledge::when(!empty($request->keyword), function ($query) use ($request) {
                $query->where('description', 'like', "%{$request->keyword}%");
            })->get();

            return response($knowledges);
        } catch (Exception $ex) {
            return response($ex->getMessage(), $ex->getCode());
        }
    }
}
