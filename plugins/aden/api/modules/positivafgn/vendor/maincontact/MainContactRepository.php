<?php


namespace AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

use function GuzzleHttp\Promise\all;

class MainContactRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new MainContactModel());
    }

    public function insertOrUpdate($entity)
    {
        foreach($entity->maincontacts as $key => $contact) {
            $MainContactModel = MainContactModel::findOrNew($contact->id);
            $MainContactModel->id = $contact->id;
            $MainContactModel->vendorId = $entity->vendorId;
            $MainContactModel->contactType = $contact->contactType->value;
            $MainContactModel->firstLastName = $contact->firstLastName;
            $MainContactModel->secondLastName = $contact->secondLastName;
            $MainContactModel->name = $contact->name;
            $MainContactModel->save();
            
            $contact->info = $this->saveInfo($MainContactModel->id, $contact->info);
            $entity->maincontacts[$key]->id = $MainContactModel->id;
        }

        return $entity;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();
        InfoModel::whereMainContactId($id)->delete();
    }

    public function deleteInfo($id)
    {
        InfoModel::find($id)->delete();
    }

    public function saveInfo($mainContactId, $contactInfo)
    {
        if($contactInfo) {
            foreach($contactInfo as $key => $info) {
                if(!empty($info->type->value)) {
                    $InfoModel = InfoModel::findOrNew($info->id);
                    $InfoModel->id = $info->id;
                    $InfoModel->mainContactId = $mainContactId;
                    $InfoModel->type = $info->type->value;
                    $InfoModel->value = $info->value;
                    $InfoModel->save();
                    $contactInfo[$key]->id = $InfoModel->id;
                }
            }
        }

        return $contactInfo;
    }

    public function parseModelWithRelations($vendorId)
    {
        $mainContacts = MainContactModel::whereVendorId($vendorId)->get();
        $mainContacts->each(function($mainContact){
            $mainContact->contactType = $mainContact->getContactType();
            $mainContact->info = $mainContact->info->each(function($info) {
                $info->type = $info->getType();
            });
        });

        return $mainContacts;

    }


}
