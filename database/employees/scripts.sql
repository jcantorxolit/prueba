-- 11/04/2021 11:41 pm
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_employee_type_rh', 'A+', 'A+', 'RHA+');

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_employee_type_rh', 'A-', 'A-', 'RHA-');

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_employee_type_rh', 'B+', 'B+', 'RHB+');

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_employee_type_rh', 'B-', 'B-', 'RHB-');

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_employee_type_rh', 'O+', 'O+', 'RHO+');

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_employee_type_rh', 'O-', 'O-', 'RHO-');

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_employee_type_rh', 'AB+', 'AB+', 'RHAB+');

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_employee_type_rh', 'AB-', 'AB-', 'RHAB-');

-- 12/04/2021 14:53
insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'empleado_documento_approved_revised', 'empleado_documento_approved_revised', now(), now());

insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'empleado_documento_reviewed_denied', 'empleado_documento_reviewed_denied', now(), now());

-- 14/04/2021
ALTER TABLE wg_employee_staging
ADD rh VARCHAR(50) DEFAULT null AFTER city_id;

ALTER TABLE wg_employee_staging
ADD riskLevel VARCHAR(50) DEFAULT null AFTER rh;

ALTER TABLE wg_employee_staging
ADD isAuthorized tinyint(1) default 1 AFTER riskLevel;

-- 14/04/2021 14:30
insert into jbonnydev_userpermissions_permissions (name, code, created_at, updated_at) values (
	'empleado_template_approval', 'empleado_template_approval', now(), now());


-- 15/04/2021 13:10

ALTER TABLE wg_employee_staging
ADD isValid tinyint(1) default 1 AFTER isAuthorized,
ADD `index` tinyint(1) default 1 AFTER isValid,
ADD `errors` text default null AFTER `index`;

ALTER TABLE wg_employee_staging
CHANGE createdBy created_by VARCHAR(10);

ALTER TABLE wg_employee_staging
ADD updated_by varchar(10) default null after created_at,
ADD updated_at datetime default null after updated_by;

-- 14/04/2021 18:07
DROP PROCEDURE IF EXISTS TL_Employee;

DELIMITER ;;
CREATE PROCEDURE TL_Employee(IN `customerId` bigint,IN `sessionId` varchar(255))
BEGIN
			INSERT INTO wg_employee
			SELECT NULL id
					, O.documentType
					, O.documentNumber, O.expeditionPlace, O.expeditionDate
					, O.firstName		
					, O.lastName		
					, O.fullName
					, O.birthdate
					, O.gender					
					, O.profession 
					, O.eps 
					, O.afp 
					, O.arl 
					, (SELECT MIN(id) FROM rainlab_user_countries where `name` COLLATE utf8_general_ci = O.country_id) country_id
					, (SELECT MIN(id) FROM rainlab_user_states where `name` COLLATE utf8_general_ci = O.state_id) state_id
					, (SELECT MIN(id) FROM wg_towns where `name` = O.city_id) city_id
					, O.neighborhood
					, O.observation
					, O.isActive isActive		
					, NULL averageIncome
					, NULL typeHousing
					, NULL antiquityCompany
					, NULL antiquityJob
					, 0 hasPeopleInCharge
					, 0 qtyPeopleInCharge
					, 0 hasChildren
					, 0 isPracticeSports
					, NULL frequencyPracticeSports
					, 0 isDrinkAlcoholic
					, NULL frequencyDrinkAlcoholic
					, 0 isSmokes
					, NULL frequencySmokes
					, 0 isDiagnosedDisease
					, NULL stratum
					, NULL civilStatus
					, NULL scholarship
					, NULL race
					, NULL workingHoursPerDay
					, NULL workArea
					, 0 age
					, O.rh
					, O.riskLevel
					, O.created_by
					, O.created_by updatedBy
					, O.created_at
					, O.created_at updated_at
			FROM 	wg_employee_staging O
			LEFT JOIN wg_employee D ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND D.documentNumber IS NULL AND O.isValid = 1;


		UPDATE wg_customer_employee D
		INNER JOIN wg_employee E on D.employee_id = E.id
		INNER JOIN 	wg_employee_staging O ON O.documentNumber = E.documentNumber AND O.documentType = E.documentType  AND D.customer_id = O.customer_id		
		SET D.contractType = O.contractType 
			,D.occupation = O.occupation	
			,D.job = O.job 
			,D.workPlace = O.workPlace
			,D.salary = O.salary		
			,D.isActive = O.isActive		
			,D.updated_at = O.created_at
			,D.updatedBy = O.created_by
		WHERE O.session_id = sessionId AND O.customer_id = customerId AND O.isValid = 1;

			INSERT INTO wg_customer_employee
			SELECT NULL id
			, O.customer_id
			, D.id employee_id
						, O.contractType 
						
						, O.occupation
						, O.job
						, O.workPlace
						, O.salary
						, 'employee' type	
						, O.isActive isActive
						, 0 isAuthorized
						, null primary_email
						, null primary_cellphone
					  , null `location_id`
						, null `department_id`
						, null `area_id`
						, null `turn_id`
						, O.created_by
						, O.created_by updatedBy
						, O.created_at
						, O.created_at updated_at
			FROM
				(
					SELECT MAX(id) id, documentType, documentNumber FROM wg_employee					
					GROUP BY documentType, documentNumber
				) D
			INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType 
			LEFT JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND CE.customer_id IS NULL AND O.isValid = 1;

			
			INSERT INTO wg_employee_info_detail
			SELECT 
					NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'dir',  O.address `value`
			FROM
					wg_employee D
			INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType 
			INNER JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
			LEFT JOIN (
									SELECT wg_employee_info_detail.* FROM wg_employee_info_detail									
									INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee_info_detail.entityId
									where wg_customer_employee.customer_id = customerId AND entityName = 'Wgroup\\Employee\\Employee' and wg_employee_info_detail.type = 'dir'
								) I ON I.`value` = O.address AND I.entityId = D.id
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND I.id IS NULL AND O.address <> '' AND O.isValid = 1;

			
			INSERT INTO wg_employee_info_detail
			SELECT 
					NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'tel',  O.telephone `value`
			FROM
					wg_employee D
			INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType 
			INNER JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
			LEFT JOIN (
									SELECT wg_employee_info_detail.* FROM wg_employee_info_detail									
									INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee_info_detail.entityId
									where wg_customer_employee.customer_id = customerId AND entityName = 'Wgroup\\Employee\\Employee' and wg_employee_info_detail.type = 'tel'
								) I ON I.`value` = O.telephone AND I.entityId = D.id
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND I.id IS NULL AND O.telephone <> '' AND O.isValid = 1;

			
			INSERT INTO wg_employee_info_detail
			SELECT 
					NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'cel',  O.mobil `value`
			FROM
					wg_employee D
			INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType 
			INNER JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
			LEFT JOIN (
									SELECT wg_employee_info_detail.* FROM wg_employee_info_detail									
									INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee_info_detail.entityId
									where wg_customer_employee.customer_id = customerId AND entityName = 'Wgroup\\Employee\\Employee' and wg_employee_info_detail.type = 'cel'
								) I ON I.`value` = O.mobil AND I.entityId = D.id
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND I.id IS NULL AND O.mobil <> '' AND O.isValid = 1;

			
			INSERT INTO wg_employee_info_detail
			SELECT 
					NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'email',  O.email `value`
			FROM
					wg_employee D
			INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType 
			INNER JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
			LEFT JOIN (
									SELECT wg_employee_info_detail.* FROM wg_employee_info_detail									
									INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee_info_detail.entityId
									where wg_customer_employee.customer_id = customerId AND entityName = 'Wgroup\\Employee\\Employee' and wg_employee_info_detail.type = 'email'
								) I ON I.`value` = O.email AND I.entityId = D.id
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND I.id IS NULL AND O.email <> '' AND O.isValid = 1;

			UPDATE wg_customer_employee ce
			INNER JOIN (
				SELECT
					ce.id,
					i.`value`,
					i.id itemId
				FROM
					wg_employee e
				INNER JOIN wg_customer_employee ce ON e.id = ce.employee_id
				INNER JOIN (
						SELECT 
							`wg_employee_info_detail`.`id` AS `id`,
							min(
								`wg_employee_info_detail`.`value`
							) AS `value`,
							`wg_employee_info_detail`.`entityId` AS `entityId`,
							`wg_employee_info_detail`.`entityName` AS `entityName`,
							`wg_employee_info_detail`.`type` AS `type`
						FROM wg_employee_info_detail									
						INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee_info_detail.entityId
						WHERE wg_customer_employee.customer_id = customerId AND 
									entityName = 'Wgroup\\Employee\\Employee' and wg_employee_info_detail.type = 'email'
						GROUP BY
							`wg_employee_info_detail`.`entityId`,
							`wg_employee_info_detail`.`entityName`,
							`wg_employee_info_detail`.`type`
				) i ON e.id = i.entityId
				AND i.type = 'email' 
			) i ON ce.id = i.id
			SET primary_email = i.itemId
			WHERE primary_email IS NULL OR primary_email = '' OR primary_email <> i.itemId;

			UPDATE wg_customer_employee ce
			INNER JOIN (
				SELECT
					ce.id,
					i.`value`,
					i.id itemId
				FROM
					wg_employee e
				INNER JOIN wg_customer_employee ce ON e.id = ce.employee_id
				INNER JOIN (
						SELECT 
							`wg_employee_info_detail`.`id` AS `id`,
							min(
								`wg_employee_info_detail`.`value`
							) AS `value`,
							`wg_employee_info_detail`.`entityId` AS `entityId`,
							`wg_employee_info_detail`.`entityName` AS `entityName`,
							`wg_employee_info_detail`.`type` AS `type`
						FROM wg_employee_info_detail									
						INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee_info_detail.entityId
						WHERE wg_customer_employee.customer_id = customerId AND 
									entityName = 'Wgroup\\Employee\\Employee' and wg_employee_info_detail.type = 'cel'
						GROUP BY
							`wg_employee_info_detail`.`entityId`,
							`wg_employee_info_detail`.`entityName`,
							`wg_employee_info_detail`.`type`
				) i ON e.id = i.entityId
				AND i.type = 'cel' 
			) i ON ce.id = i.id
			SET primary_cellphone = i.itemId
			WHERE primary_cellphone IS NULL OR primary_cellphone = '' OR primary_cellphone <> i.itemId;

END;;
DELIMITER ;


-- 15/04/2021 08:00

DROP PROCEDURE IF EXISTS `TL_Employee_Template`;

DELIMITER ;;
CREATE PROCEDURE `TL_Employee_Template`(IN `customerId` bigint,IN `sessionId` varchar(255))
BEGIN
	

		UPDATE wg_employee D
		INNER JOIN 	wg_customer_employee ce ON ce.employee_id = D.id
		INNER JOIN wg_employee_staging O ON O.customer_employee_id = ce.id AND ce.customer_id = O.customer_id
		SET 
			D.expeditionPlace = O.expeditionPlace
			,D.expeditionDate = O.expeditionDate
			,D.firstName = O.firstName		
			,D.lastName = O.lastName		
			,D.fullName = O.fullName
			,D.birthdate = O.birthdate
			,D.gender = O.gender
			,D.profession = O.profession 
			,D.eps = O.eps 
			,D.afp = O.afp 
			,D.arl = O.arl 
			,D.country_id = (SELECT MIN(id) FROM rainlab_user_countries where `name` COLLATE utf8_general_ci = O.country_id)
			,D.state_id = (SELECT MIN(id) FROM rainlab_user_states where `name` COLLATE utf8_general_ci = O.state_id)
			,D.city_id = (SELECT MIN(id) FROM wg_towns where `name` = O.city_id)
			,D.neighborhood = O.neighborhood
			,D.observation = O.observation
			,D.isActive = O.isActive
			,D.rh = O.rh
			,D.riskLevel = O.riskLevel						
			,D.updated_at = O.created_at
			,D.updatedBy = O.created_by
		WHERE O.session_id = sessionId AND O.customer_id = customerId AND O.isValid = 1;

		-- ACTUALIZAR LOS DATOS DEL EMPLEADO DESDE PLANTILLA DE AUTORIZACION
		UPDATE wg_customer_employee D
		INNER JOIN wg_employee E on D.employee_id = E.id
		INNER JOIN 	wg_employee_staging O ON O.customer_employee_id = D.id AND D.customer_id = O.customer_id		
		SET D.contractType = O.contractType 
			
			,D.occupation = O.occupation	
			,D.job = O.job
			,D.workPlace = O.workPlace
			,D.salary = O.salary		
			,D.isActive = O.isActive
			,D.isAuthorized = O.isAuthorized		
			,D.updated_at = O.created_at
			,D.updatedBy = O.created_by
		WHERE O.session_id = sessionId AND O.customer_id = customerId and O.isAuthorized != 2 AND O.isValid = 1;
		
		-- ACTUALIZAR LOS DATOS DEL EMPLEADO DESDE PLANTILLA NORMAL
		UPDATE wg_customer_employee D
		INNER JOIN wg_employee E on D.employee_id = E.id
		INNER JOIN 	wg_employee_staging O ON O.customer_employee_id = D.id AND D.customer_id = O.customer_id		
		SET D.contractType = O.contractType 
			
			,D.occupation = O.occupation	
			,D.job = O.job
			,D.workPlace = O.workPlace
			,D.salary = O.salary		
			,D.isActive = O.isActive
			,D.updated_at = O.created_at
			,D.updatedBy = O.created_by
		WHERE O.session_id = sessionId AND O.customer_id = customerId and O.isAuthorized = 2 AND O.isValid = 1;

			
			UPDATE
				wg_employee_info_detail D 
			INNER JOIN wg_employee e on e.id = D.entityId
			INNER JOIN wg_customer_employee ce ON e.id = ce.employee_id
			INNER JOIN wg_employee_staging O ON O.customer_employee_id = ce.id AND ce.customer_id = O.customer_id
			INNER JOIN  (
					SELECT
						`wg_employee_info_detail`.`id` AS `id`,
						MIN(
							`wg_employee_info_detail`.`value`
						) AS `value`,
						`wg_employee_info_detail`.`entityId` AS `entityId`,
						`wg_employee_info_detail`.`entityName` AS `entityName`,
						`wg_employee_info_detail`.`type` AS `type`
					FROM
						wg_employee_info_detail
					INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
					INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
					WHERE wg_customer_employee.customer_id = customerId AND wg_employee_info_detail.type = 'cel'
					GROUP BY
						`wg_employee_info_detail`.`entityId`,
						`wg_employee_info_detail`.`entityName`,
						`wg_employee_info_detail`.`type`
			) employee_info_detail ON employee_info_detail.id = D.id 
			SET D.`value` = O.mobil
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND O.isValid = 1;
			
			
			UPDATE
				wg_employee_info_detail D 
			INNER JOIN wg_employee e on e.id = D.entityId
			INNER JOIN wg_customer_employee ce ON e.id = ce.employee_id
			INNER JOIN wg_employee_staging O ON O.customer_employee_id = ce.id AND ce.customer_id = O.customer_id
			
			INNER JOIN  (
					SELECT
						`wg_employee_info_detail`.`id` AS `id`,
						MIN(
							`wg_employee_info_detail`.`value`
						) AS `value`,
						`wg_employee_info_detail`.`entityId` AS `entityId`,
						`wg_employee_info_detail`.`entityName` AS `entityName`,
						`wg_employee_info_detail`.`type` AS `type`
					FROM
						wg_employee_info_detail
					INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
					INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
					WHERE wg_customer_employee.customer_id = customerId AND wg_employee_info_detail.type = 'dir'
					GROUP BY
						`wg_employee_info_detail`.`entityId`,
						`wg_employee_info_detail`.`entityName`,
						`wg_employee_info_detail`.`type`
			) employee_info_detail ON employee_info_detail.id = D.id 
			SET D.`value` = O.address
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND O.isValid = 1;

			
			UPDATE
				wg_employee_info_detail D 
			INNER JOIN wg_employee e on e.id = D.entityId
			INNER JOIN wg_customer_employee ce ON e.id = ce.employee_id
			INNER JOIN wg_employee_staging O ON O.customer_employee_id = ce.id AND ce.customer_id = O.customer_id
			
			INNER JOIN  (
					SELECT
						`wg_employee_info_detail`.`id` AS `id`,
						MIN(
							`wg_employee_info_detail`.`value`
						) AS `value`,
						`wg_employee_info_detail`.`entityId` AS `entityId`,
						`wg_employee_info_detail`.`entityName` AS `entityName`,
						`wg_employee_info_detail`.`type` AS `type`
					FROM
						wg_employee_info_detail
					INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
					INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
					WHERE wg_customer_employee.customer_id = customerId AND wg_employee_info_detail.type = 'tel'
					GROUP BY
						`wg_employee_info_detail`.`entityId`,
						`wg_employee_info_detail`.`entityName`,
						`wg_employee_info_detail`.`type`
			) employee_info_detail ON employee_info_detail.id = D.id 
			SET D.`value` = O.telephone
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND O.isValid = 1;

			
			UPDATE
				wg_employee_info_detail D 
			INNER JOIN wg_employee e on e.id = D.entityId
			INNER JOIN wg_customer_employee ce ON e.id = ce.employee_id
			INNER JOIN wg_employee_staging O ON O.customer_employee_id = ce.id AND ce.customer_id = O.customer_id
			
			INNER JOIN  (
					SELECT
						`wg_employee_info_detail`.`id` AS `id`,
						MIN(
							`wg_employee_info_detail`.`value`
						) AS `value`,
						`wg_employee_info_detail`.`entityId` AS `entityId`,
						`wg_employee_info_detail`.`entityName` AS `entityName`,
						`wg_employee_info_detail`.`type` AS `type`
					FROM
						wg_employee_info_detail
					INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
					INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
					WHERE wg_customer_employee.customer_id = customerId AND wg_employee_info_detail.type = 'email'
					GROUP BY
						`wg_employee_info_detail`.`entityId`,
						`wg_employee_info_detail`.`entityName`,
						`wg_employee_info_detail`.`type`
			) employee_info_detail ON employee_info_detail.id = D.id 
			SET D.`value` = O.email
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND O.isValid = 1;


			
			INSERT INTO wg_employee_info_detail
			SELECT 
					NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'cel',  O.mobil `value`
			FROM
					wg_employee D
			INNER JOIN 	wg_customer_employee CE ON CE.employee_id = D.id
			INNER JOIN wg_employee_staging O ON O.customer_employee_id = CE.id AND CE.customer_id = O.customer_id
			
			LEFT JOIN  (
					SELECT
						`wg_employee_info_detail`.`id` AS `id`,
						MIN(
							`wg_employee_info_detail`.`value`
						) AS `value`,
						`wg_employee_info_detail`.`entityId` AS `entityId`,
						`wg_employee_info_detail`.`entityName` AS `entityName`,
						`wg_employee_info_detail`.`type` AS `type`
					FROM
						wg_employee_info_detail
					INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
					INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
					WHERE wg_customer_employee.customer_id = customerId AND wg_employee_info_detail.type = 'cel'
					GROUP BY
						`wg_employee_info_detail`.`entityId`,
						`wg_employee_info_detail`.`entityName`,
						`wg_employee_info_detail`.`type`
			) employee_info_detail ON employee_info_detail.entityId = D.id 
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND employee_info_detail.id IS NULL AND O.mobil <> '' AND O.isValid = 1;			


			
			INSERT INTO wg_employee_info_detail
			SELECT 
					NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'dir',  O.address `value`
			FROM
					wg_employee D
			INNER JOIN 	wg_customer_employee CE ON CE.employee_id = D.id
			INNER JOIN wg_employee_staging O ON O.customer_employee_id = CE.id AND CE.customer_id = O.customer_id
			
			LEFT JOIN  (
					SELECT
						`wg_employee_info_detail`.`id` AS `id`,
						MIN(
							`wg_employee_info_detail`.`value`
						) AS `value`,
						`wg_employee_info_detail`.`entityId` AS `entityId`,
						`wg_employee_info_detail`.`entityName` AS `entityName`,
						`wg_employee_info_detail`.`type` AS `type`
					FROM
						wg_employee_info_detail
					INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
					INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
					WHERE wg_customer_employee.customer_id = customerId AND wg_employee_info_detail.type = 'dir'
					GROUP BY
						`wg_employee_info_detail`.`entityId`,
						`wg_employee_info_detail`.`entityName`,
						`wg_employee_info_detail`.`type`
			) employee_info_detail ON employee_info_detail.entityId = D.id 
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND employee_info_detail.id IS NULL AND O.address <> '' AND O.isValid = 1;			


			
			INSERT INTO wg_employee_info_detail
			SELECT 
					NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'tel',  O.telephone `value`
			FROM
					wg_employee D
			INNER JOIN 	wg_customer_employee CE ON CE.employee_id = D.id
			INNER JOIN wg_employee_staging O ON O.customer_employee_id = CE.id AND CE.customer_id = O.customer_id
			
			LEFT JOIN  (
					SELECT
						`wg_employee_info_detail`.`id` AS `id`,
						MIN(
							`wg_employee_info_detail`.`value`
						) AS `value`,
						`wg_employee_info_detail`.`entityId` AS `entityId`,
						`wg_employee_info_detail`.`entityName` AS `entityName`,
						`wg_employee_info_detail`.`type` AS `type`
					FROM
						wg_employee_info_detail
					INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
					INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
					WHERE wg_customer_employee.customer_id = customerId AND wg_employee_info_detail.type = 'tel'
					GROUP BY
						`wg_employee_info_detail`.`entityId`,
						`wg_employee_info_detail`.`entityName`,
						`wg_employee_info_detail`.`type`
			) employee_info_detail ON employee_info_detail.entityId = D.id 
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND employee_info_detail.id IS NULL AND O.telephone <> '' AND O.isValid = 1;


			
			INSERT INTO wg_employee_info_detail
			SELECT 
					NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'email',  O.email `value`
			FROM
					wg_employee D
			INNER JOIN 	wg_customer_employee CE ON CE.employee_id = D.id
			INNER JOIN wg_employee_staging O ON O.customer_employee_id = CE.id AND CE.customer_id = O.customer_id
			
			LEFT JOIN  (
					SELECT
						`wg_employee_info_detail`.`id` AS `id`,
						MIN(
							`wg_employee_info_detail`.`value`
						) AS `value`,
						`wg_employee_info_detail`.`entityId` AS `entityId`,
						`wg_employee_info_detail`.`entityName` AS `entityName`,
						`wg_employee_info_detail`.`type` AS `type`
					FROM
						wg_employee_info_detail
					INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
					INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
					WHERE wg_customer_employee.customer_id = customerId AND wg_employee_info_detail.type = 'email'
					GROUP BY
						`wg_employee_info_detail`.`entityId`,
						`wg_employee_info_detail`.`entityName`,
						`wg_employee_info_detail`.`type`
			) employee_info_detail ON employee_info_detail.entityId = D.id 
			WHERE O.session_id = sessionId AND O.customer_id = customerId AND employee_info_detail.id IS NULL AND O.email <> '' AND O.isValid = 1;

END;;
DELIMITER ;

-- 15/04/2021 13:10

ALTER TABLE wg_employee_staging
ADD isValid tinyint(1) default 1 AFTER isAuthorized,
ADD `index` tinyint(1) default 1 AFTER isAuthorized;

-- 16/04/2021 10:00
CREATE TABLE wg_customer_work_medicine_staging (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`customer_id` bigint(20) DEFAULT NULL,
`document_number` varchar(16) DEFAULT NULL,
`examinationType` varchar(200) DEFAULT NULL,
`examinationDate` date DEFAULT NULL,
`occupationalConclusion` varchar(200) DEFAULT NULL,
`occupationalBehavior` varchar(200) DEFAULT NULL,
`generalRecommendation` varchar(200) DEFAULT NULL,
`medicalConcept` varchar(200) DEFAULT NULL,
`complementaryTest` varchar(200) DEFAULT NULL,
`result` varchar(200) DEFAULT NULL,
`interpretacion` varchar(200) DEFAULT NULL,
`typeSve` varchar(200) DEFAULT NULL,
`isActiveSve` varchar(200) DEFAULT NULL,
`typeTracking` varchar(200) DEFAULT NULL,
`dateTracking` date DEFAULT NULL,
`observation` varchar(200) DEFAULT NULL,
`session_id` varchar(255) DEFAULT NULL,
`created_by` varchar(10) DEFAULT NULL,
`created_at` datetime DEFAULT NULL,
`updated_by` varchar(10) DEFAULT NULL,
`updated_at` datetime DEFAULT null,
PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8
COLLATE=utf8_general_ci;

INSERT INTO `system_parameters` (`namespace`, `group`, `item`, `value`, `code`)
VALUES
	( 'wgroup', 'employee_disavow', '0 20 * * *', 'crontab', NULL);

INSERT INTO `system_parameters` (`namespace`, `group`, `item`, `value`, `code`)
VALUES
	( 'wgroup', 'employee_document_denied', '0 20 * * *', 'crontab', NULL);

INSERT INTO `system_parameters` (`namespace`, `group`, `item`, `value`, `code`)
VALUES
	( 'wgroup', 'customer_employee_inactive', '0 20 * * *', 'crontab', NULL);

-- 16/04/2021
CREATE PROCEDURE `TL_CUSTOMER_WORK_MEDICINE_STAGING`(IN `customerId` bigint,IN `sessionId` varchar(255))
BEGIN
UPDATE wg_customer_work_medicine_staging D
LEFT JOIN (
	SELECT 
		wg_customer_work_medicine_staging.id,
		wg_customer_work_medicine_staging.customer_id
	FROM wg_customer_work_medicine_staging			
) O ON O.id = D.id			
SET D.customer_id = O.customer_id
WHERE D.session_id = sessionId AND D.customer_id = customerId;
END

ALTER TABLE wg_customer_work_medicine
ADD staging_id bigint(20);

CREATE PROCEDURE `TL_CUSTOMER_WORK_MEDICINE`(IN `customerId` bigint,IN `sessionId` varchar(255))
begin
	/*Examenes medicos*/
	  	insert into wg_customer_work_medicine (customer_employee_id, examinationType, examinationDate, occupationalConclusion, occupationalBehavior, generalRecommendation,
                                          medicalConcept,staging_id, createdBy, created_at)
        select wce.id,O.examinationType,O.examinationDate,O.occupationalConclusion,O.occupationalBehavior,O.generalRecommendation,O.medicalConcept,O.id,O.created_by,O.created_at
		from wg_customer_work_medicine_staging O
		inner join wg_customer_employee wce on wce.customer_id = O.customer_id
		inner join wg_employee we on wce.employee_id = we.id
		left join wg_customer_work_medicine d on d.customer_employee_id = we.id and d.staging_id = O.id
		where O.session_id = sessionId and we.documentNumber = O.document_number AND d.id is null;
	
	/* Examenes complementarios*/
		insert into wg_customer_work_medicine_complementary_test (customer_work_medicine_id, complementaryTest,result, interpretation, createdBy, created_at)
        select d.id,O.complementaryTest,sp.id,O.interpretacion,O.created_by,O.created_at
		from wg_customer_work_medicine_staging O
		inner join wg_customer_work_medicine d on O.id = d.staging_id
  		inner join (select `id`, `namespace`, `group`, `value`, `item` collate utf8_general_ci as `item`, `code` collate utf8_general_ci as `code` from `system_parameters` where `namespace` = 'wgroup' and `group` = 'work_medicine_complementary_test_result'
    	) sp on `sp`.`code` = `O`.`complementaryTest` AND sp.item = O.result		
		WHERE O.session_id = sessionId and d.staging_id = O.id;
	
	/*SVE*/
		insert into wg_customer_work_medicine_sve (customer_work_medicine_id,type,isActive,createdBy,created_at)
		select d.id,O.typeSve,O.isActiveSve,O.created_by,O.created_at
		from wg_customer_work_medicine_staging O
		inner join wg_customer_work_medicine d on O.id = d.staging_id
		WHERE O.session_id = sessionId and d.staging_id = O.id;
	
	/*Medicine tracking*/
		insert into wg_customer_work_medicine_tracking (customer_work_medicine_id,type,dateOf,observation,createdBy,created_at)
		select d.id,O.typeTracking,O.dateTracking,O.observation,O.created_by,O.created_at
		from wg_customer_work_medicine_staging O
		inner join wg_customer_work_medicine d on O.id = d.staging_id
		WHERE O.session_id = sessionId and d.staging_id = O.id;
END;

INSERT INTO `wg_report` (`id`, `collection_id`, `name`, `description`, `isActive`, `allowAgent`, `allowCustomer`, `collection_chart_id`, `chartType`, `isQueue`, `requireFilter`, `code`, `isAutomatic`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)
VALUES
	(80, 70, 'EMPLEADOS DESAUTORIZADOS AUTOMATICAMENTE', 'EMPLEADOS DESAUTORIZADOS AUTOMATICAMENTE', 1, 1, 0, NULL, NULL, 0, 0, NULL, 0, 2, 687, '2020-05-03 11:31:04', '2020-05-04 19:06:12');


INSERT INTO `wg_collection_data` (`id`, `name`, `description`, `isActive`, `viewName`, `run_before`, `run_after`, `type`, `module`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)
VALUES
	(70, 'EMPLEADOS DESAUTORIZADOS AUTOMATICAMENTE', 'Coleccion de datos de la auditoria en el vencimiento de documentos de los empleados autorizados.', 1, 'SELECT\r\n	wg_customers.id AS customer_id,\r\n	wg_customers.businessName,\r\n	wg_employee.documentNumber,\r\n	wg_employee.fullName,\r\n	wg_customer_employee_audit.date,\r\n	wg_customer_employee_audit.observation\r\nFROM\r\n	wg_customer_employee_audit\r\nINNER JOIN wg_customer_employee ON wg_customer_employee.id = wg_customer_employee_audit.customer_employee_id\r\nINNER JOIN wg_employee ON wg_employee.id = wg_customer_employee.employee_id\r\nINNER JOIN wg_customers ON wg_customers.id = wg_customer_employee.customer_id\r\nWHERE\r\n	user_type = \'cronjob\'\r\nAND action = \'Desautorizar\'', NULL, NULL, 'report', 'customer', 1, 1, '2020-05-03 11:31:03', '2020-05-03 11:31:03');

INSERT INTO `wg_collection_data_field` (`id`, `collection_data_id`, `table`, `name`, `alias`, `dataType`, `visible`, `isActive`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)
VALUES
	(805, 70, 'p', 'businessName', 'Razon Social', 'varchar', 1, 1, 2, 2, '2020-05-03 11:31:03', '2020-05-03 11:31:03'),
	(806, 70, 'p', 'documentNumber', 'Número Identificación Empleado', 'varchar', 1, 1, 2, 2, '2020-05-03 11:31:03', '2020-05-03 11:31:03'),
	(807, 70, 'p', 'fullName', 'Nombre Empleado', 'varchar', 1, 1, 2, 2, '2020-05-03 11:31:03', '2020-05-03 11:31:03'),
	(808, 70, 'p', 'observation', 'Observación', 'varchar', 1, 1, 2, 2, '2020-05-03 11:31:03', '2020-05-03 11:31:03'),
	(809, 70, 'p', 'date', 'Fecha', 'varchar', 1, 1, 2, 2, '2020-05-03 11:31:04', '2020-05-03 11:31:04');

	INSERT INTO `wg_report_collection_data_field` ( `report_id`, `collection_data_field_id`, `isActive`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)
VALUES
	( 80, 805, '1', 687, 687, '2020-05-04 19:05:56', '2020-05-04 19:06:12'),
	( 80, 806, '1', 687, 687, '2020-05-04 19:05:56', '2020-05-04 19:06:12'),
	( 80, 807, '1', 687, 687, '2020-05-04 19:05:56', '2020-05-04 19:06:12'),
	( 80, 808, '1', 687, 687, '2020-05-04 19:05:56', '2020-05-04 19:06:12'),
	( 80, 809, '1', 687, 687, '2020-05-04 19:05:56', '2020-05-04 19:06:12');



-- update wg_customer_employee
-- set job = '0' where trim(coalesce(job, '')) = '';
-- 
-- update wg_customer_employee
-- set job = '0' where trim(job) = '';
-- 
-- update wg_customer_employee
-- set job = '0' where job like '%a%' ;
-- 
-- update wg_customer_employee
-- set job = '0' where job like '%e%' ;
-- 
-- update wg_customer_employee
-- set job = '0' where job like '%i%' ;
-- 
-- update wg_customer_employee
-- set job = '0' where job like '%o%' ;
-- 
-- update wg_customer_employee
-- set job = '0' where job like '%u%' ;
-- 
-- 
-- ALTER TABLE wg_customer_employee 
-- MODIFY COLUMN job integer(10);