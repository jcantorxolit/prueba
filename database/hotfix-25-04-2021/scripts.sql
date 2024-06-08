-- 25/04/2021
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

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_productivity_stata_person_type', 'INVESTIGADOR INTERNO', 'IN', '');

INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'wg_customer_productivity_stata_person_type', 'INVESTIGADOR EXTERNO', 'EX', '');

-- 25/04/2021 11:25
delete from system_parameters
where `group` = 'work_medicine_complementary_test_result';
/*Examenes ocupacionales*/
-- Audiometria
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Normal', '', '001');
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Con recomendacion', '', '001');
-- Visiometria
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Normal', '', '002');
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Con recomendacion', '', '002');
-- Optometria
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Normal', '', '003');
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Con recomendacion', '', '003');
-- Espirometria
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Normal', '', '004');
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Con recomendacion', '', '004');
-- Electrocardiograma
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Normal', '', '005');
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Con recomendacion', '', '005');
-- Trabajo en alturas
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Normal', '', '006');
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Con recomendacion', '', '006');
-- Psicosensometrico
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Normal', '', '007');
INSERT INTO system_parameters
(namespace, `group`, item, value, code)
VALUES('wgroup', 'work_medicine_complementary_test_result', 'Con recomendacion', '', '007');
