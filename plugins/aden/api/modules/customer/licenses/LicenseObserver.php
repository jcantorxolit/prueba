<?php

namespace AdeN\Api\Modules\Customer\Licenses;

use Carbon\Carbon;

use AdeN\Api\Modules\Customer\Licenses\LicenseModel;

class LicenseObserver {

    protected $model;

    public function created(LicenseModel $license) {
        $this->model = $license;

        $this->saveLog('Fecha Registro', null, $license->createdAt->format('Y-m-d'));
    }

    public function updated(LicenseModel $license)
    {
        $this->model = $license;

        if ($license->license <> $license->getOriginal('license')) {
            $this->saveLog('Licencia', $license->getOriginal('license'), $license->license);
        }

        if ($license->startDate instanceof Carbon && $license->startDate->format('Y-m-d') <> $license->getOriginal('start_date')) {
            $this->saveLog('Fecha Inicio', $license->getOriginal('start_date'), $license->startDate->format('Y-m-d'));
        }

        if ($license->endDate instanceof Carbon && $license->endDate->format('Y-m-d') <> $license->getOriginal('end_date')) {
            $this->saveLog('Fecha Fin', $license->getOriginal('end_date'), $license->endDate->format('Y-m-d'));
        }

        if ($license->value <> $license->getOriginal('value')) {
            $this->saveLog('Valor Licencia', $license->getOriginal('value'), $license->value);
        }

        if ($license->state <> $license->getOriginal('state')) {
            $this->saveLog('Estado', $license->getOriginal('state'), $license->state);
        }

        if ($license->agentId <> $license->getOriginal('agent_id')) {
            $this->saveLog('Comercial Asignado', $license->getOriginal('agent_id'), $license->agentId);
        }

    }

    private function saveLog($field, $beforeValue, $afterValue) {
        $log = new LicenseLogModel();
        $log->licenseId = $this->model->id;
        $log->field = $field;
        $log->beforeValue = $beforeValue;
        $log->afterValue = $afterValue;
        $log->userId = $this->model->updatedBy ?? $this->model->createdBy;
        $log->reason = $this->model->reason;
        $log->createdAt = Carbon::now('America/Bogota');
        $log->updatedAt = Carbon::now('America/Bogota');
        $log->save();
    }

}