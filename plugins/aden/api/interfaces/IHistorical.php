<?php

namespace AdeN\Api\Interfaces;


/**
 * Declare an interface to manage the changes of the model vs entity
 */
interface IHistorical
{
    public function getParentId();    
    public function getModelName();    
    public function getModelId();    
    public function getChanges();
    public function getIsDirty($field);    
    public function getOriginalValue($field);
    public function getModel();
}