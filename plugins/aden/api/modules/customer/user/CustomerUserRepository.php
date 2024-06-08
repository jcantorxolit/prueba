<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\User;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Helpers\ValidatorHelper;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;
use AdeN\Api\Modules\Customer\User\Skill\CustomerUserSkillRepository;
use Carbon\Carbon;
use Log;
use Exception;
use Session;
use DB;
use Mail;
use Excel;
use Validator;
use Illuminate\Database\Eloquent\Collection;
use Wgroup\SystemParameter\SystemParameter;
use RainLab\User\Models\User;
use RainLab\User\Components\Account;
use Wgroup\Employee\EmployeeDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CustomerUserRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerUserModel());

        $this->service = new CustomerUserService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_user.id",
            "firstname" => "users.name AS firstName",
            "lastname" => "users.surname AS lastName",
            "email" => "users.email",
            "availability" => "wg_customer_user.availability",
            "profile" => "wg_customer_user_profile.item as profile",
            "customer" => "wg_customers.businessName",
            "relation" => DB::raw("'Empresa Actual' AS relation"),
            "status" => "wg_common_active_status.item AS status",
            "statusCode" => "wg_customer_user.isActive as statusCode",
            "customerId" => "wg_customer_user.customer_id AS customerId",
            "userId" => "wg_customer_user.user_id AS userId",
            "relationCode" => DB::raw("'CC' AS relationCode"),
            "module" => "wg_customer_user.module AS module"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation */
        $query->join("users", function ($join) {
            $join->on('users.id', '=', 'wg_customer_user.user_id');
            $join->on('users.company', '=', 'wg_customer_user.customer_id');
        })->join("wg_customers", function ($join) {
            $join->on('wg_customers.id', '=', 'users.company');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_user_profile')), function ($join) {
            $join->on('users.wg_type', '=', 'wg_customer_user_profile.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_common_active_status')), function ($join) {
            $join->on('wg_customer_user.isActive', '=', 'wg_common_active_status.value');
        })->leftjoin('wg_customer_employee as ce', function($join) {
            $join->on('ce.id', '=', 'wg_customer_user.customer_employee_id');
        });


        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allContractorEconomicGroup($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_user.id",
            "firstname" => "users.name AS firstName",
            "lastname" => "users.surname AS lastName",
            "email" => "users.email",
            "availability" => "wg_customer_user.availability",
            "profile" => "wg_customer_user_profile.item as profile",
            "customer" => "wg_customers.businessName",
            "relation" => "wg_customers.relation",
            "status" => "wg_common_active_status.item AS status",
            "statusCode" => "wg_customer_user.isActive as statusCode",
            "customerId" => "wg_customer_user.customer_id AS customerId",
            "userId" => "wg_customer_user.user_id AS userId",
            "relationCode" => "wg_customers.relationCode",
            "module" => "wg_customer_user.module AS module"
        ]);

        $this->parseCriteria($criteria);

        $q0 = DB::table('wg_customers')
            ->select(
                'wg_customers.id',
                'wg_customers.businessName',
                DB::raw("'Empresa Actual' AS relation"),
                DB::raw("'CC' AS relationCode")
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        $q1 = DB::table('wg_customers')
            ->join('wg_customer_contractor', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_contractor.contractor_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.businessName',
                DB::raw("'Empresa Contratista' AS relation"),
                DB::raw("'CT' AS relationCode")
            )
            ->whereRaw("wg_customers.isDeleted = 0")
            ->whereRaw("wg_customers.status = '1'")
            ->whereRaw("wg_customers.classification = 'Contratista'");

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.customer_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.businessName',
                DB::raw("'Empresa del Grupo Económico' AS relation"),
                DB::raw("'EG' AS relationCode")
            )
            ->whereRaw("wg_customers.status = '1'")
            ->whereRaw("wg_customers.isDeleted = 0");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q0->whereRaw(SqlHelper::getPreparedField('wg_customers.id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q1->whereRaw(SqlHelper::getPreparedField('wg_customer_contractor.customer_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q2->whereRaw(SqlHelper::getPreparedField('wg_customer_economic_group.parent_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $q0->union($q1)->union($q2);

        $query = $this->query();

        $query->join("users", function ($join) {
            $join->on('users.id', '=', 'wg_customer_user.user_id');
            $join->on('users.company', '=', 'wg_customer_user.customer_id');
        })->join(DB::raw("({$q0->toSql()}) as wg_customers"), function ($join) {
            $join->on('wg_customers.id', '=', 'users.company');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_user_profile')), function ($join) {
            $join->on('users.wg_type', '=', 'wg_customer_user_profile.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_common_active_status')), function ($join) {
            $join->on('wg_customer_user.isActive', '=', 'wg_common_active_status.value');
        })->mergeBindings($q0);

        $this->applyCriteria($query, $criteria, ["customerId"]);

        return $this->get($query, $criteria);
    }

    public function allCustomerAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_customers.id",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "relation" => "wg_customers.relation",
            "status" => "estado.item AS status"
        ]);

        $this->parseCriteria($criteria);

        $q0 = DB::table('wg_customers')
            ->select(
                'wg_customers.id',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                DB::raw("'Empresa Actual' AS relation"),
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        $q1 = DB::table('wg_customers')
            ->join('wg_customer_contractor', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_contractor.contractor_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                DB::raw("'Empresa Contratista' AS relation"),
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.isDeleted = 0")
            ->whereRaw("wg_customers.status = '1'")
            ->whereRaw("wg_customers.classification = 'Contratista'");

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.customer_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                DB::raw("'Empresa del Grupo Económico' AS relation"),
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.status = '1'")
            ->whereRaw("wg_customers.isDeleted = 0");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q0->whereRaw(SqlHelper::getPreparedField('wg_customers.id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q1->whereRaw(SqlHelper::getPreparedField('wg_customer_contractor.customer_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q2->whereRaw(SqlHelper::getPreparedField('wg_customer_economic_group.parent_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $relationField = CriteriaHelper::getMandatoryFilter($criteria, 'relation');

        if ($relationField && $relationField->value == 'CT') {
            $q0->union($q1);
        } else if ($relationField && $relationField->value == 'EG') {
            $q0->union($q2);
        } else {
            $q0->union($q1)->union($q2);
        }

        $query = $this->query(DB::table(DB::raw("({$q0->toSql()}) as wg_customers")));

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');
        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == "notInRaw") {
                        $query->whereNotIn('wg_customers.id', function ($query) use ($item) {
                            $query->select('customer_id')
                                ->from('wg_customer_user')
                                ->where('wg_customer_user.user_id', '=', SqlHelper::getPreparedData($item));
                        });
                    }
                }
            }
        }

        $this->applyCriteria($query, $criteria, ['customerId', 'userId', 'relation']);

        return $this->get($query, $criteria);
    }

    public function allCustomerRelated($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_user.id",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "relation" => "wg_customers.relation",
            "status" => "wg_common_active_status.item AS status",
            "statusCode" => "wg_customer_user.isActive as statusCode",
            'userId' => "wg_customer_user.user_id"
        ]);

        $this->parseCriteria($criteria);

        $q0 = DB::table('wg_customers')
            ->select(
                'wg_customers.id',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                DB::raw("'Empresa Actual' AS relation"),
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        $q1 = DB::table('wg_customers')
            ->join('wg_customer_contractor', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_contractor.contractor_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                DB::raw("'Empresa Contratista' AS relation"),
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.isDeleted = 0")
            ->whereRaw("wg_customers.status = '1'")
            ->whereRaw("wg_customers.classification = 'Contratista'");

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.customer_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                DB::raw("'Empresa del Grupo Económico' AS relation"),
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.status = '1'")
            ->whereRaw("wg_customers.isDeleted = 0");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q0->whereRaw(SqlHelper::getPreparedField('wg_customers.id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q1->whereRaw(SqlHelper::getPreparedField('wg_customer_contractor.customer_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q2->whereRaw(SqlHelper::getPreparedField('wg_customer_economic_group.parent_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $relationField = CriteriaHelper::getMandatoryFilter($criteria, 'relation');

        if ($relationField && $relationField->value == 'CT') {
            $q0->union($q1);
        } else if ($relationField && $relationField->value == 'EG') {
            $q0->union($q2);
        } else {
            $q0->union($q1)->union($q2);
        }

        $query = $this->query(DB::table(DB::raw("({$q0->toSql()}) as wg_customers")));

        $query->join("wg_customer_user", function ($join) {
            $join->on('wg_customer_user.customer_id', '=', 'wg_customers.id');
        })->join("users", function ($join) {
            $join->on('users.id', '=', 'wg_customer_user.user_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_common_active_status')), function ($join) {
            $join->on('wg_customer_user.isActive', '=', 'wg_common_active_status.value');
        })->where('wg_customers.id', '<>', DB::raw('users.company'));

        /*if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == "inRaw") {
                        $query->whereIn('wg_customers.id', function ($query) use ($item) {
                            $query->select('customer_id')
                                ->from('wg_customer_user')
                                ->where('wg_customer_user.user_id', '=', SqlHelper::getPreparedData($item));
                        });
                    }
                }
            }
        }*/

        $this->applyCriteria($query, $criteria, ['customerId', 'relation']);

        return $this->get($query, $criteria);
    }

    public function canCreate($entity)
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

    public function canDelete($id)
    {
        $messages = [];

        if ($this->service->trackingCount($id) > 0) {
            $messages[] = "Tiene seguimientos asociados.";
        }

        if ($this->service->improvementPlanCount($id) > 0) {
            $messages[] = "Tiene planes de mejoramiento asociados.";
        }

        if ($this->service->improvementPlanCount($id) > 0) {
            $messages[] = "Tiene planes de trabajo interno asociados.";
        }

        return [
            "allowed" => !count($messages),
            "message" => implode("\n", $messages)
        ];
    }

    public function insertOrUpdate($entity)
    {
        DB::transaction(function () use ($entity) {
            return $this->internalInsertOrUpdate($entity);
        });
    }

    private function internalInsertOrUpdate($entity)
    {
        $authUser = $this->getAuthUser();

        $isNewRecord = false;

        $relatedUser = $this->findOrCreateRelatedUser($entity);

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $this->deleteIfHasRelation($entity, $relatedUser);



        $entityModel->customer_id = $entity->mainCustomer ? $entity->mainCustomer->id : $entity->customerId;
        $entityModel->documentType = $entity->documentType ? $entity->documentType->value : null;
        $entityModel->documentNumber = $entity->documentNumber;
        $entityModel->gender = $entity->gender ? $entity->gender->value : null;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->availability = $entity->availability;
        $entityModel->isActive = $entity->isActive;
        $entityModel->user_id = $relatedUser ? $relatedUser->id : null;
        $entityModel->relation = $entity->mainCustomer ? $entity->mainCustomer->relationCode : 'CC';
        $entityModel->isUserApp = $entity->isUserApp;
        $entityModel->role = $entity->role ? $entity->role->value : null;
        $entityModel->profile = $entity->profile ? $entity->profile->value : null;

        $oldRoleId = $entityModel->getOriginal('role');
        $newRoleId = $entityModel->role;


        if ($entity->type->value == "01" && isset($entity->employeeId)) {
            $entityModel->customer_employee_id = $entity->employeeId;
        }

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        if ($oldRoleId != $newRoleId) {
            $this->service::assignRole($entityModel->user_id, $newRoleId, $oldRoleId);
        }


        if ($entity->type->value == "01" && isset($entity->employeeId)) {
            $employee = (new CustomerEmployeeDTO())->find($entity->employeeId, 2);
            $emptyMail = (object) ["id" => 0, "type" => [], "value" => NULL];
            $entityEmployee = (object)["details" => [], "id" => $employee->entity->id];
            $emptyMail->value = $entity->email;
            $emptyMail->type = $entity->informationList[0];
            $entityEmployee->details[] = $emptyMail;
            EmployeeDTO::employeeUpdateInfoDetail($entityEmployee);
        }

        if (isset($entity->skills)) {
            (new CustomerUserSkillRepository)->bulkInsertOrUpdate($entity->skills,  $entityModel->id);
        }

        return $entityModel;
    }

    private function deleteIfHasRelation($entity, $user)
    {
        if ($entity->mainCustomer && $entity->mainCustomer->id != $user->company) {
            $this->model->where('customer_id', $entity->mainCustomer->id)
                ->where('user_id', $entity->userId)
                ->delete();
        }
    }

    public function relateCustomer($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        $currentUser = $this->find($entity->customerUserId);

        if (!($entityModel = $this->model
            ->where('customer_id', $entity->customerId)
            ->where('user_id', $currentUser->user_id)
            ->first())) {

            $entityModel = $this->model->newInstance();

            $entityModel->customer_id = $entity->customerId;
            $entityModel->gender = $currentUser->gender;
            $entityModel->type = $currentUser->type;
            $entityModel->availability = $currentUser->availability;
            $entityModel->isActive = $currentUser->isActive;
            $entityModel->user_id = $currentUser->user_id;

            if ($isNewRecord) {
                $entityModel->createdBy = $authUser ? $authUser->id : 1;
                $entityModel->save();
            } else {
                $entityModel->updatedBy = $authUser ? $authUser->id : 1;
                $entityModel->save();
            }
        }

        return $entityModel;
    }

    public function findOrCreateRelatedUser($entity)
    {
        $userData = [
            'name' => $entity->firstName,
            'surname' => $entity->lastName,
            'email' => $entity->email,
            'company' => $entity->mainCustomer ? $entity->mainCustomer->id : $entity->customerId,
            'wg_type' => $entity->profile ? $entity->profile->value : 'customerUser',
            'iu_comment' => $entity->documentNumber
        ];

        if ($entity->userId == null || $entity->userId == 0) {
            $password = substr($entity->email, 0, strpos($entity->email, '@'));
            $userData['password'] = $password;
            $userData['password_confirmation'] = $password;
            $user = Account::onCreate($userData);
            if (!$entity->isUserApp) {
                //DB->20200701: SPRINT 12
                //REMOVE ASSIGN ROLE BASE ON CUSTOMER SIZER
                //$this->service->assignRoleCustomerSize($user);
            }
            $user->is_activated = $entity->isActive;
            $user->save();

            $this->sendCreationAccountMail($user, $password);

            return $user;
        }

        $user = User::find($entity->userId);
        $user->fill($userData);

        if ($entity->customerId == $entity->mainCustomer->id) {
            if (!$entity->isActive) {
                $this->service->inactiveInAllCustomerRelated($user->id);
            }
            $user->is_activated = $entity->isActive;
        }

        $user->save();
        return $user;
    }

    public function toggleActive($id)
    {
        $entityModel = $this->find($id);
        $entityModel->isActive = !($entityModel->isActive == 1);

        if ($entityModel->isActive) {
            $user = User::find($entityModel->user_id);
            if ($user->company != $entityModel->customer_id && $user->is_activated == 0) {
                throw new \Exception("No es posible realizar esta acción. Primero se debe activar el usuario en la empresa principal.");
            }
        }

        $entityModel->save();
    }

    public function updateRelation($id, $relation)
    {
        $entityModel = $this->find($id);

        $entityModel->relation = $relation;

        return $entityModel;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entityUser = $model->user;

            $entity->id = $model->id;
            $entity->customerId = $model->customer_id;
            $entity->firstName = $entityUser->name;
            $entity->lastName = $entityUser->surname;
            $entity->documentNumber = $model->documentNumber;
            $entity->documentType = $model->getDocumentType();
            $entity->gender = $model->getGender();
            $entity->type = $model->getType();
            $entity->availability = $model->availability;
            $entity->email = $entityUser->email;
            $entity->isActive = $model->isActive == 1;
            $entity->isUserApp = $model->isUserApp == 1;
            $entity->profile = $model->getProfile();
            $entity->userId = $entityUser->id;
            $entity->mainCustomer = $model->getCustomer();
            $entity->role = $model->getRole();
            $entity->skills = CustomerUserSkillRepository::getSkills($model->id);
            $entity->employeeId = $model->customer_employee_id;

            return $entity;
        } else {
            return null;
        }
    }

    public function getCustomerList($criteria)
    {
        return $this->service->getCustomerList($criteria);
    }

    public function hasCustomerAdmin($criteria)
    {
        return $this->service->hasCustomerAdmin($criteria);
    }

    public function findSignUpDefaultUserRole()
    {
        return $this->service->findSignUpDefaultUserRole();
    }

    public static function createFromSignUp($entity)
    {
        $newEntity = new \stdClass();
        $newEntity->id = 0;
        $newEntity->customerId = $entity->customer->id;
        $newEntity->mainCustomer = null;
        $newEntity->documentType = $entity->user->documentType;
        $newEntity->documentNumber = $entity->user->documentNumber;
        $newEntity->gender = $entity->user->gender;
        $newEntity->type = CmsHelper::parseToStdClass([
            "value" => "01" //TODO get this value from system_parameter
        ]);
        $newEntity->availability = null;
        $newEntity->isActive = true;
        $newEntity->isUserApp = false;
        $newEntity->role = (new self)->findSignUpDefaultUserRole();
        $newEntity->firstName = $entity->user->firstName;
        $newEntity->lastName = $entity->user->lastName;
        $newEntity->email = $entity->user->email;
        $newEntity->profile = CmsHelper::parseToStdClass([
            "value" => "customerAdmin"
        ]);
        $newEntity->userId = null;

        return (new self)->internalInsertOrUpdate($newEntity);
    }


    private function sendCreationAccountMail($user, $password)
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

    public function downloadTemplate()
    {
        $authUser = $this->getAuthUser();

        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/plantilla_importacion_usuarios.xlsx";

        $profileData = array_map(function ($row) {
            return [
                'NOMBRE' => $row->item
            ];
        }, $this->service->getParameterList('wg_customer_user_profile'));

        $roleList = $this->service->getParameterList('customer_user_role');

        if (($authUser && $authUser->wg_type == 'customerAdmin')) {
            $roleList = array_filter($roleList, function ($row) {
                return $row->value != 'customerAdmin';
            });
        }

        $roleData = array_map(function ($row) {
            return [
                'NOMBRE' => $row->item
            ];
        }, $roleList);

        $profile = CmsHelper::prependEmptyItemInArray($profileData);
        $role = CmsHelper::prependEmptyItemInArray($roleData);

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($profile, $role) {
            $sheet = $file->setActiveSheetIndex(1);

            $file->addNamedRange(
                new \PHPExcel_NamedRange(
                    'profile',
                    $file->getActiveSheet(),
                    'F2:F' . count($profile)
                )
            );

            $file->addNamedRange(
                new \PHPExcel_NamedRange(
                    'role',
                    $file->getActiveSheet(),
                    'J2:J' . count($role)
                )
            );

            $sheet->fromArray($profile, null, 'F1', false);
            $sheet->fromArray($role, null, 'J1', false);

            $sheet = $file->setActiveSheetIndex(0);

            $cels = [
                'G2' => ['range' => 'G2:G5000', 'formula' => 'profile'],
                'H2' => ['range' => 'H2:H5000', 'formula' => 'role'],
            ];

            foreach ($cels as $cell => $info) {
                $validation = $sheet->getCell($cell)->getDataValidation();
                $validation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Error de entrada');
                $validation->setError('El valor no está en la lista.');
                $validation->setFormula1($info['formula']);
                $sheet->setDataValidation($info['range'], $validation);
            }
        })->download('xlsx');
    }

    public function import(UploadedFile $uploadedFile, $customerId)
    {
        set_time_limit(0);
        $uploadedFileName = $uploadedFile->getFilename();

        //$uploadedFile->move(CmsHelper::getAppPath('storage/temp'), $uploadedFile->getClientOriginalName());

        //$sessionId = session()->getId();
        $sessionId = Session::getId();
        $result = [];
        $errors = new Collection();
        try {
            Excel::load($uploadedFile->getRealPath(), function ($file) use ($customerId, $sessionId, $errors) {
                //Excel::load(CmsHelper::getAppPath('storage/temp/' . $uploadedFile->getClientOriginalName()), function ($file) use ($customerId, $sessionId) {
                $now = Carbon::now('America/Bogota')->toDateTimeString();
                $results = $file->all();
                $authUser = $this->getAuthUser();

                $data = [];
                $index = 0;

                foreach ($results as $sheet) {
                    foreach ($sheet as $row) {

                        if ($this->isInvalidImportRow(CmsHelper::parseToArray($row))) {
                            continue;
                        }

                        $index++;

                        if ($validationRulesData = $this->validateRulesData($row, $index)) {
                            $errors->push($validationRulesData);
                        }

                        if ($validationRulesData) {
                            continue;
                        }

                        $data[] = [
                            'id' => null,
                            'customer_id' => $customerId,
                            'first_name' => $row->nombres,
                            'last_name' => $row->apellidos,
                            'full_name' => "{$row->nombres} {$row->apellidos}",
                            'document_type' => "CC",
                            'document_number' => $row->numero_identificacion,
                            'gender' => $this->service->getGender($row->genero),
                            'type' => $this->service->getType($row->categoria),
                            'email' => $row->e_mail,
                            'is_active' => $row->estado == 'Activo',
                            'profile' => $this->service->getProfile($row->perfil),
                            'role' => $this->service->getRole($row->rol),
                            'is_user_app' => false,
                            'index' => $index,
                            'is_valid' => true,
                            'session_id' => $sessionId,
                            'created_by' => $authUser ? $authUser->id : 0,
                            'created_at' => $now
                        ];
                    }
                    break;
                }

                if (count($data) > 0) {
                    // var_dump($data);
                    DB::table('wg_customer_user_staging')->where('customer_id', $customerId)->where('session_id', $sessionId)->delete();

                    foreach (array_chunk($data, 1000) as $t) {
                        DB::table('wg_customer_user_staging')->insert($t);
                    }

                    DB::statement("CALL TL_Customer_User_Staging($customerId, '$sessionId')");
                }
            });

            $data = $this->service->getStagingValidData($sessionId, $customerId);
            $invalidData = $this->service->getStagingInvalidData($sessionId, $customerId);

            foreach ($data as $entity) {
                if (!$this->canCreate($entity)) {
                    $errors->push("El [usuario # {$entity->index}] presenta los siguientes errores: No es posible guardar el registro. Ya existe un usuario con el E-mail ingresado.");
                } else {
                    $this->insertOrUpdate($entity);
                }
            }

            return [
                'message' => $uploadedFileName,
                'errors' => array_merge($errors->toArray(), $invalidData),
                'sessionId' => $sessionId
            ];
        } catch (Exception $ex) {
            Log::error($ex);

            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];
        }

        return $result;
    }

    private function validateRulesData($data, $index)
    {
        Log::info("validateRulesData::" . json_encode($data));
        $rules = [
            'nombres' => 'required|max:100',
            'apellidos' => 'required|max:50',
            'e_mail' => 'required|max:255|email',
            'perfil' => 'required|max:20',
            'rol' => 'required|max:255',
        ];

        $messages = ValidatorHelper::customMessages();

        $validator = Validator::make(CmsHelper::parseToArray($data), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return "[usuario # $index]: " .  implode(" | ", $errors->all());
        }

        return  null;
    }

    private function isInvalidImportRow($row)
    {
        $invalid = 0;

        foreach ($row as $key => $value) {
            if (CmsHelper::isEmptyOrNull($value)) {
                $invalid++;
            }
        }

        return $invalid >= count($row);
    }
}
