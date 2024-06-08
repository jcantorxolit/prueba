<?php

namespace AdeN\Api\Modules\Customer\DocumentSecurity;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerDocumentSecurityService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function allDocumentSecurityUsers($customerId, $documentType, $origin)
    {
        $query = "SELECT users.id AS userId, UPPER(users.`name`) `name`, users.type
        , CASE WHEN wg_customer_document_security_user.isActive = 1 THEN 1 ELSE 0 END hasPermission
        , CASE WHEN ISNULL(wg_customer_document_security_user.id) THEN 0 ELSE wg_customer_document_security_user.id END id
    FROM 
    (
            SELECT id, company customer_id, `name`, 'Cliente' type FROM users 
            WHERE (wg_type = 'customerAdmin' OR wg_type = 'customerAdmin') AND company = :customer_id_1
            
            UNION ALL
            
            SELECT u.id, ca.customer_id, u.`name`, 'Asesor' type
            FROM wg_customer_agent ca
            INNER JOIN  wg_agent a ON a.id = ca.agent_id
            INNER JOIN users u ON u.id = a.user_id
            WHERE wg_type = 'agent' AND ca.customer_id = :customer_id_2
            GROUP BY u.id	
    ) users
    LEFT JOIN (SELECT * FROM wg_customer_document_security_user WHERE customer_id = :customer_id_3 AND documentType = :document_type AND origin = :origin) wg_customer_document_security_user
            ON users.id = wg_customer_document_security_user.user_id
    ORDER BY `name`";

        return DB::select($query, [
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'customer_id_3' => $customerId,
            'document_type' => $documentType,
            'origin' => $origin
        ]);
    }
}