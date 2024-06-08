<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Mail;
use DB;
use RainLab\User\Components\Account;
/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class AgentDTO
{

    function __construct($model = null)
    {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1")
    {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getInfoBasic($model);
        }
    }

    private function getInfoBasic($model)
    {
        $this->id = $model->id;

        $this->fullName = $model->name;

        $this->firstName = $model->firstName;

        $this->lastName = $model->lastName;

        $this->documentType = $model->getDocumentType();

        $this->documentNumber = $model->documentNumber;

        $this->gender = $model->getGender();

        $this->type = $model->getType();

        $this->legalType = $model->getLegalType();

        $this->active = $model->active;

        $this->availability = $model->availability;

        $this->userId = $model->user_id;

        $this->rh = $model->rh;

        $this->emergencyContactName = $model->emergencyContactName;

        $this->emergencyContactPhone = $model->emergencyContactPhone;

        $this->emergencyContactKinship = $model->emergencyContactKinship;

        $this->contacts = InfoDetailDto::parse($model->getInfoDetail());

        $this->occupations = AgentOccupationDTO::parse($model->occupations);

        $this->skills = AgentSkillDTO::parse($model->skills);

        //$this->created_at = $model->created_at->format('d/m/Y');
        //$this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
        $this->logo = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo);
        $this->signature = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->signature);
        $this->signatureText = $model->signatureText;

        $user = User::find($model->user_id);

        $this->email = $user ? $user->email : $model->email;
        $this->role = $model->getRole();
        $this->isActive = $model->isUserActive;
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        $relatedUser = self::findOrCreateRelatedUser($object);

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Agent::find($object->id))) {
                // No existe
                $model = new Agent();
                $isEdit = false;
            }
        } else {
            $model = new Agent();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->name = $object->firstName.' '.$object->lastName;

        $model->firstName = $object->firstName;

        $model->lastName = $object->lastName;

        $model->documentType = $object->documentType->value == "-S-" ? null : $object->documentType->value;

        $model->documentNumber = $object->documentNumber;

        $model->gender = $object->gender->value == "-S-" ? null : $object->gender->value;

        $model->type = $object->type->value == "-S-" ? null : $object->type->value;

        $model->legalType = $object->legalType == null ? null : $object->legalType->value;

        $model->active = 1;

        $model->rh = $object->rh;

        $model->emergencyContactName = $object->emergencyContactName;

        $model->emergencyContactPhone = $object->emergencyContactPhone;

        $model->emergencyContactKinship = $object->emergencyContactKinship;

        $model->availability = $object->availability;

        $model->signatureText = $object->signatureText;

        $model->email = $object->email;
        $model->user_id = $relatedUser ? $relatedUser->id : null;
        $model->role = $object->role ? $object->role->value : null;
        $model->isUserActive = $object->isActive;

        $oldRoleId = $model->getOriginal('role');
        $newRoleId = $model->role;

        if ($oldRoleId != $newRoleId) {
            self::assignRole($model->user_id, $newRoleId, $oldRoleId);
        }
        //$model->userId = $object->userId;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // limpiar la informacion de contacto
        foreach ($model->getInfoDetail() as $infoDetail) {
            $infoDetail->delete();
        }

        if ($object->contacts) {
            foreach ($object->contacts as $contactInfo) {
                if (isset($contactInfo->id) && isset($contactInfo->value) && $contactInfo->value != "-S-") {
                    $infoDetail = new InfoDetail();
                    $infoDetail->entityId = $model->id;
                    $infoDetail->entityName = get_class($model);
                    $infoDetail->type = ($contactInfo->type) ? $contactInfo->type->value : null;
                    $infoDetail->value = $contactInfo->value;
                    $infoDetail->save();
                }
            }
        }

        // limpiar la informacion de contacto
        foreach ($model->skills as $skill) {
            $skill->delete();
        }

        if ($object->skills) {
            foreach ($object->skills as $skill) {
                if (isset($skill->id) && isset($skill->skill) && $skill->skill->value != "-S-") {
                    $agentSkill = new AgentSkill();
                    $agentSkill->agent_id = $model->id;
                    $agentSkill->skill = $skill->skill->value == "-S-" ? null : $skill->skill->value;
                    $agentSkill->createdBy = $userAdmn->id;
                    $agentSkill->updatedBy = $userAdmn->id;
                    $agentSkill->save();
                }
            }
        }

        // limpiar la informacion de contacto
        foreach ($model->occupations as $occupation) {
            $occupation->delete();
        }

        if ($object->occupations) {
            foreach ($object->occupations as $occupation) {
                if (isset($occupation->id) && isset($occupation->type) &&$occupation->type->value != "-S-") {
                    $agentOccupation = new AgentOccupation();
                    $agentOccupation->agent_id = $model->id;
                    $agentOccupation->type = $occupation->type->value == "-S-" ? null : $occupation->type->value;
                    $agentOccupation->description = $occupation->description;
                    $agentOccupation->license = $occupation->license;
                    $agentOccupation->status = 'Activo';
                    $agentOccupation->createdBy = $userAdmn->id;
                    $agentOccupation->updatedBy = $userAdmn->id;
                    $agentOccupation->save();
                }
            }
        }

        return Agent::find($model->id);
    }

    public static function assignRole($userId, $newRoleId, $oldRoleId)
    {
        $newRole = DB::table('user_groups')->where('code', $newRoleId)->first();
        $oldRole = DB::table('user_groups')->where('code', $oldRoleId)->first();

        DB::table('users_groups')
            ->where('user_id', $userId)
            ->where('user_group_id', $oldRole ? $oldRole->id : -1)
            ->delete();

        $query = DB::table('user_groups')
            ->select(
                DB::raw('? AS user_id'),
                'user_groups.id as user_group_id'
            )
            ->addBinding($userId, "select")
            ->where('id', $newRole ? $newRole->id : -1);

        $sql = 'INSERT INTO users_groups (`user_id`, `user_group_id`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public static function canCreate($entity)
    {
        if ($entity->email == null || trim($entity->email) == '') {
            return false;
        }

        if ($entity->id == 0) {
            $userModel = User::findByEmail($entity->email);

            return $userModel == null;
        } else {
            $currentUser = User::find($entity->userId);
            $emailUser = User::findByEmail($entity->email);

            if ($emailUser != null) {
                return ($emailUser->email == $currentUser->email) && ($emailUser->id == $currentUser->id);
            }

            return true;
        }
    }

    public static function findOrCreateRelatedUser($entity)
    {
        $userData = [
            'name' => $entity->firstName,
            'surname' => $entity->lastName,
            'email' => $entity->email,
            'wg_type' => 'agent'
        ];

        if (empty($entity->userId) || ($entity->userId == null || $entity->userId == 0)) {
            $password = substr($entity->email, 0, strpos($entity->email, '@'));
            $userData['password'] = $password;
            $userData['password_confirmation'] = $password;
            $user = Account::onCreate($userData);
            $user->is_activated = $entity->isActive;
            $user->save();

            $entity->userId = $user->id;
            self::sendCreationAccountMail($user, $password);

            return $user;
        }

        $user = User::find($entity->userId);
        $user->fill($userData);
        $user->save();
        return $user;
    }

    private static function sendCreationAccountMail($user, $password)
    {
        try {
            $data = [
                "name" => "{$user->name} {$user->surname}",
                "user" => $user->email,
                "password" => $password,
            ];

            $template = 'rainlab.user:mail.cliente_usuario_welcome';

            Mail::send($template, $data, function ($message) use ($data) {
                $message->to($data['user'], $data['name']);
            });
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }


    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {
        if ($fmt_response != "1") {
            // parse model
            switch ($fmt_response) {
                case "1":
                    $this->getInfoBasic($model);
                    break;
                default:
            }
        } else {
            // parse model
            if ($model) {
                $this->setInfo($model, $fmt_response);
            }
        }
        return $this;

    }

    public static function parse($info, $fmt_response = "1")
    {
        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof Agent) {
                    $parsed[] = (new AgentDto())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new AgentDto())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof Agent) {
            return (new AgentDto())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new AgentDto();
            }
        }
    }

    private function parseArray($model, $fmt_response = "1")
    {
        switch ($fmt_response) {
            case "1":

                break;
            default:
        }

        return $this;
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;


        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }


}
