<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Knowledge;
use App\Models\KnowledgeCategory;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAdmin;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    const ADMIN = 1;
    const USER = 2;

    public function __construct() {}

    /**
     * @OA\Get(
     *     path="/app/get-roles",
     *     summary="Lista dei ruoli disponibili tra cui l'utente può scegliere in fase di registrazione",
     *     tags={"Register"},
     *     @OA\Response(response="200", 
     *                  description="Lista dei ruoli",
     *                   @OA\JsonContent(
     *                   type="array",
     *                   @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer", example=1),
     *                   @OA\Property(description="name", type="string", example="utente")
     *                   )
     *         )
     *  )
     * )
     */
    public function getRoles()
    {

        try {
            $roles = Role::get();

            return response($roles);
        } catch (Exception $e) {

            return response($e->getMessage(), $e->getCode());
        }
    }


    /**
     * @OA\Post(
     *     path="/app/create-user",
     *     summary="Crea un nuovo utente con profilo e conoscenze",
     *     tags={"Register"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="firstname", type="string", example="Mario"),
     *             @OA\Property(property="lastname", type="string", example="Rossi"),
     *             @OA\Property(property="email", type="string", format="email", example="mario.rossi@example.com"),
     *             @OA\Property(property="birthday", type="string", format="date", example="1980-01-01"),
     *             @OA\Property(property="title_profile", type="string", example="Developer"),
     *             @OA\Property(property="level_profile_id", type="integer", example=2),
     *             @OA\Property(
     *                 property="knowledges",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="PHP"),
     *                     @OA\Property(property="level_id", type="integer", example=3)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utente creato con successo"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errore di validazione dati"
     *     )
     * )
     */


    public function createUser(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'firstname' => ['required', 'string'],
                    'lastname' => ['required', 'string'],
                    'email' => ['email'],
                    'birthday' => ['nullable', 'date'],
                    'title_profile' => ['required', 'string'],
                    'level_profile_id' => ['required', 'int'],
                    'knowledges' => ['required', 'array'],
                    'knowledges.*.name' => ['required', 'string'],
                    'knowledges.*.level_id' => ['required', 'int', 'between:1,3'],
                ]
            );

            if ($validator->fails()) {
                throw new Exception("Registrazione fallita: {$validator->errors()}", 422);
            }

            $user_exist = User::where('email', $request->email)->first();

            if ($user_exist) {
                throw new Exception("Hai già un profilo registrato con la seguente email!");
            }

            DB::beginTransaction();
            $category = new Category();

            $category->description = $request->title_profile;

            $category->save();

            foreach ($request->knowledges as $knowledge) {
                $new_knowledge = new Knowledge();
                $new_knowledge->description = $knowledge['name'];
                $new_knowledge->level_id = $knowledge['level_id'];

                $new_knowledge->save();

                $knowledge_rel_category = new KnowledgeCategory();
                $knowledge_rel_category->knowledge_id = $new_knowledge->id;
                $knowledge_rel_category->category_id = $category->id;

                $knowledge_rel_category->save();
            }

            $profile = new Profile();

            $profile->title = $request->title_profile;
            $profile->category_id = $category->id;
            $profile->level_id = $request->level_profile_id;

            $profile->save();

            $user_admin = UserAdmin::where('email', $request->email)->first();

            $user = new User();

            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->birthday = new Carbon($request->birthday);
            $user->role_id = self::USER;

            if ($user_admin) {
                $user->role_id = self::ADMIN;
            }

            $user->profile_id = $profile->id;

            $user->save();

            DB::commit();

            return response(compact('user'));
        } catch (Exception $e) {

            DB::rollBack();

            return response($e->getMessage(), $e->getCode());
        }
    }

    
}
