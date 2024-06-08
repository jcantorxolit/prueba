<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class AgentDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_agent_document';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->type, "agent_document_type");
    }

    public function  getClassification()
    {
        return $this->getParameterByValue($this->classification, "agent_document_classification");
    }

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "agent_document_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
