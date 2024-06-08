
CREATE TABLE `wg_customer_vr_employee_head_staging` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `customer_id` bigint(20) DEFAULT NULL,
  `document_type` varchar(15) DEFAULT NULL,
  `document_number` varchar(15) DEFAULT NULL,
  `first_names` varchar(100) DEFAULT NULL,
  `last_names` varchar(100) DEFAULT NULL,
  `genre` varchar(15) DEFAULT NULL,
  `session_id` varchar(200) DEFAULT NULL,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ix_id` (`id`) USING BTREE,
  KEY `ix_customer_id` (`customer_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

DROP PROCEDURE IF EXISTS TL_VR_Employee_Head;

DELIMITER ;;
CREATE PROCEDURE `TL_VR_Employee_Head`(IN `customerId` bigint,IN `sessionId` varchar(255))
BEGIN
			INSERT INTO wg_employee ( documentType, documentNumber, firstName, lastName, fullName, gender, isActive, createdBy, updatedBy, created_at, updated_at)
			SELECT DT.value
					, O.document_number
					, O.first_names
					, O.last_names		
					, CONCAT(O.first_names, ' ', O.last_names) as full_name
					, O.genre					
					, 1 as isActive
					, O.created_by
					, O.created_by
					, O.created_at
					, O.created_at
			FROM 	wg_customer_vr_employee_head_staging O
			JOIN ( 
                SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
                FROM system_parameters
                WHERE
                namespace = 'wgroup' AND `group` = 'employee_document_type'
            ) DT ON O.document_type = DT.item
			LEFT JOIN wg_employee D ON O.document_number = D.documentNumber AND DT.value = D.documentType
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND D.id IS NULL AND O.document_type IS NOT NULL
				AND O.document_number IS NOT NULL;
			
			INSERT INTO wg_customer_employee (customer_id, employee_id, isActive, createdBy, updatedBy, created_at, updated_at)
			SELECT O.customer_id
					, E.id
					, 1 as isActive
					, O.created_by
					, O.created_by
					, O.created_at
					, O.created_at
			FROM wg_customer_vr_employee_head_staging O
			JOIN ( 
                SELECT item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
                FROM system_parameters
                WHERE
                namespace = 'wgroup' AND `group` = 'employee_document_type'
            ) DT ON O.document_type = DT.item
			JOIN wg_employee E ON O.document_number = E.documentNumber AND DT.value = E.documentType
			LEFT JOIN wg_customer_employee CE ON E.id = CE.employee_id AND CE.customer_id = O.customer_id
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND O.document_type IS NOT NULL AND O.document_number IS NOT NULL
				AND CE.id IS NULL;

			
END;;
DELIMITER ;