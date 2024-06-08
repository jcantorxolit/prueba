
INSERT INTO system_parameters (namespace, `group`, item, value) VALUES
  ('wgroup', 'wg_customer_licenses_types', 'Demo', 'LT001'),
  ('wgroup', 'wg_customer_licenses_types', 'Consultoría', 'LT002'),
  ('wgroup', 'wg_customer_licenses_types', 'Intermediación', 'LT003'),

  ('wgroup', 'wg_customer_licenses_states', 'Activa', 'LS001'),
  ('wgroup', 'wg_customer_licenses_states', 'Inactiva', 'LS002'),
  ('wgroup', 'wg_customer_licenses_states', 'Finalizada', 'LS003'),

  ('wgroup', 'dashboard_commercial_users_allowed', 'emails', '["david.blandon@gmail.com"]'),
  ('wgroup', 'customer_licenses_alert_x_days_to_finish', 'days', '1,15,30,45'),
  ('wgroup', 'customer_licenses_finish', '55 23 * * *', 'crontab'),
  ('wgroup', 'customer_licenses_notify', '55 23 1 */3 *', 'crontab'),
  ('wgroup', 'customer_licenses_notify_before_x_days_to_finish', '30', '30'),

  ('wgroup', 'work_shifts', 'Tiempo Completo', 'WS001'),
  ('wgroup', 'work_shifts', 'Medio tiempo', 'WS002'),
  ('wgroup', 'work_shifts', 'Diurno', 'WS003'),
  ('wgroup', 'work_shifts', 'Nocturno', 'WS004');


CREATE TABLE wg_customer_employee_demographic_consolidate(
    id int AUTO_INCREMENT PRIMARY KEY,
    customer_id int NULL COMMENT 'Identificación del cliente',
    workplace_id int NULL COMMENT 'Identificación del centro de trabajo',
    label varchar(20) NULL COMMENT 'Etiqueta del tipo de dato',
    value varchar(200) NULL COMMENT 'Valor identificador de la etiqueta',
    total int NULL COMMENT 'Cantidad de registros agrupados para el grupo actual'
);

CREATE INDEX idx_wg_customer_employee_demographic_consolidate_customer_id
    ON wg_customer_employee_demographic_consolidate (customer_id);

CREATE INDEX idx_wg_customer_employee_demographic_consolidate_workplace_id
    ON wg_customer_employee_demographic_consolidate (workplace_id);



CREATE TABLE wg_customer_employee_status_consolidate (
    id int AUTO_INCREMENT PRIMARY KEY,
    customer_id int NULL COMMENT 'Identificación del cliente',
    workplace_id int NULL COMMENT 'Identificación del centro de trabajo',
    `period` datetime NULL COMMENT 'Periodo',
    total int NULL COMMENT 'Cantidad total de empleados',
    count_actives int NULL COMMENT 'Cantidad de empleados activos',
    count_inactives int NULL COMMENT 'Cantidad de empleados inactivos',
    count_autorized int NULL COMMENT 'Cantidad de empleados autorizados',
    count_not_autorized int NULL COMMENT 'Cantidad de empleados no autorizados'
);


CREATE INDEX idx_wg_customer_employee_status_consolidate_customer_id
    ON wg_customer_employee_status_consolidate (customer_id);

CREATE INDEX idx_wg_customer_employee_status_consolidate_workplace_id
    ON wg_customer_employee_status_consolidate (workplace_id);

CREATE INDEX idx_wg_customer_employee_status_consolidate_period
    ON wg_customer_employee_status_consolidate (period ASC);




CREATE TABLE wg_customer_employee_status_documents_consolidate (
    id int AUTO_INCREMENT PRIMARY KEY,
    customer_id int NULL COMMENT 'Identificación del cliente',
    workplace_id int NULL COMMENT 'Identificación del centro de trabajo',
    `period` datetime NULL COMMENT 'Periodo',
    total int NULL COMMENT 'Cantidad total de empleados',
    countActive int NULL COMMENT 'Cantidad de documentos vigentes',
    countAnnuled int NULL COMMENT 'Cantidad de documentos anulados',
    countExpired int NULL COMMENT 'Cantidad de documentos vencidos',
    countApproved int NULL COMMENT 'Cantidad de documentos aprovados',
    countDenied int NULL COMMENT 'Cantidad de documentos denegados',
    count_active_approved int NULL COMMENT 'Cantidad de documentos vigentes aprobados',
    count_active_denied_expired int NULL COMMENT 'Cantidad de documentos vigentes denegados + los vencidos'
);


CREATE INDEX idx_wg_customer_employee_sd_consolidate_customer_id
    ON wg_customer_employee_status_documents_consolidate (customer_id);

CREATE INDEX idx_wg_customer_employee_sd_consolidate_workplace_id
    ON wg_customer_employee_status_documents_consolidate (workplace_id);

CREATE INDEX idx_wg_customer_employee_sd_consolidate_period
    ON wg_customer_employee_status_documents_consolidate (period ASC);




create TABLE wg_customer_licenses (
  id int AUTO_INCREMENT PRIMARY KEY,
  customer_id INT,
  license varchar(10),
  start_date date,
  end_date date,
  finish_date date,
  value double(19, 4),
  state varchar(10),
  agent_id int,
  reason text,
  created_at datetime,
  created_by int,
  updated_at datetime,
  updated_by int,
  finish_by int
);


CREATE INDEX idx_wg_customer_licenses_customer_id
  ON wg_customer_licenses (customer_id);

CREATE INDEX idx_wg_customer_licenses_customer_start_date
  ON wg_customer_licenses (start_date desc);


create TABLE wg_customer_licenses_logs (
  id int AUTO_INCREMENT PRIMARY KEY,
  license_id int,
  field varchar(20),
  before_value text,
  after_value text,
  user_id int,
  reason text,
  created_at datetime,
  updated_at datetime,
  created_by int
);


CREATE INDEX idx_wg_customer_licenses_license_id
  ON wg_customer_licenses_logs (license_id);

CREATE INDEX idx_wg_customer_licenses_created_at
  ON wg_customer_licenses_logs (created_at desc);



create TABLE wg_customer_licenses_consolidate (
  id int AUTO_INCREMENT PRIMARY KEY,
  year int,
  license varchar(10),
  state varchar(10),
  agent_id int,
  total int
);


CREATE INDEX idx_wg_customer_licenses_cons_year
  ON wg_customer_licenses_consolidate (year);

CREATE INDEX idx_wg_customer_licenses_cons_license
  ON wg_customer_licenses_consolidate (license);


ALTER TABLE wg_customer_employee
  ADD work_shift varchar(10) NULL AFTER turn_id;

ALTER TABLE wg_employee_staging
  ADD work_shift varchar(10) NULL AFTER risk_level;




DROP PROCEDURE TL_Employee;

CREATE PROCEDURE TL_Employee(IN customerId bigint, IN sessionId varchar(255))
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
    ,D.work_shift = O.work_shift
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
       , O.work_shift
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

END;




DROP PROCEDURE TL_Employee_Template;

CREATE PROCEDURE TL_Employee_Template(IN customerId bigint, IN sessionId varchar(255))
BEGIN
  -- ACTUALIZAR LOS DATOS DEL EMPLEADO
  UPDATE wg_employee D
    INNER JOIN      wg_customer_employee ce ON ce.employee_id = D.id
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

  -- AUTORIZA O DESAUTORIZAR EL EMPLEADO DESDE LA PLANTILLA DE AUTORIZACION
  UPDATE wg_customer_employee D
    INNER JOIN wg_employee E on D.employee_id = E.id
    INNER JOIN      wg_employee_staging O ON O.customer_employee_id = D.id AND D.customer_id = O.customer_id
  SET D.isAuthorized = O.isAuthorized
  WHERE O.session_id = sessionId AND O.customer_id = customerId and O.isAuthorized <> 2 AND O.isValid = 1;

  -- ACTUALIZAR LOS DATOS DEL CUSTOMER EMPLEADO
  UPDATE wg_customer_employee D
    INNER JOIN wg_employee E on D.employee_id = E.id
    INNER JOIN      wg_employee_staging O ON O.customer_employee_id = D.id AND D.customer_id = O.customer_id
  SET D.contractType = O.contractType
    ,D.occupation = O.occupation
    ,D.job = O.job
    ,D.workPlace = O.workPlace
    ,D.salary = O.salary
    ,D.isActive = O.isActive
    ,D.work_shift = O.work_shift
    ,D.updated_at = O.created_at
    ,D.updatedBy = O.created_by
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
    INNER JOIN  wg_customer_employee CE ON CE.employee_id = D.id
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
    INNER JOIN  wg_customer_employee CE ON CE.employee_id = D.id
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
    INNER JOIN  wg_customer_employee CE ON CE.employee_id = D.id
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
    INNER JOIN  wg_customer_employee CE ON CE.employee_id = D.id
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

END;




DROP PROCEDURE TL_Employee_Integration;

CREATE PROCEDURE TL_Employee_Integration(IN sessionId varchar(255))
BEGIN

  UPDATE wg_employee D
    INNER JOIN 	wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType
  SET D.firstName = O.firstName
    ,D.lastName = O.lastName
    ,D.fullName = O.fullName
    ,D.birthdate = O.birthdate
    ,D.gender = O.gender
    ,D.isActive = O.isActive
    ,D.expeditionPlace = O.expeditionPlace
    ,D.expeditionDate = O.expeditionDate
    ,D.rh = O.rh
    ,D.eps = O.eps
    ,D.afp = O.afp
    ,D.arl = O.arl
    ,D.riskLevel = O.risk_level
    ,D.profession = O.profession
    ,D.neighborhood = O.neighborhood
    ,D.observation = O.observation
    ,D.country_id = O.country_id
    ,D.state_id = O.state_id
    ,D.city_id = O.city_id
    ,D.riskLevel = O.riskLevel
    ,D.updated_at = O.created_at
    ,D.updatedBy = O.created_by
  WHERE O.session_id = sessionId AND O.customer_id IS NOT NULL AND D.documentNumber IS NOT NULL;

  INSERT INTO `wg_employee` (
    `id`,
    `documentType`,
    `documentNumber`,
    `expeditionPlace`,
    `expeditionDate`,
    `firstName`,
    `lastName`,
    `fullName`,
    `birthdate`,
    `gender`,
    `profession`,
    `eps`,
    `afp`,
    `arl`,
    `country_id`,
    `state_id`,
    `city_id`,
    `neighborhood`,
    `observation`,
    `isActive`,
    `averageIncome`,
    `typeHousing`,
    `antiquityCompany`,
    `antiquityJob`,
    `hasPeopleInCharge`,
    `qtyPeopleInCharge`,
    `hasChildren`,
    `isPracticeSports`,
    `frequencyPracticeSports`,
    `isDrinkAlcoholic`,
    `frequencyDrinkAlcoholic`,
    `isSmokes`,
    `frequencySmokes`,
    `isDiagnosedDisease`,
    `stratum`,
    `civilStatus`,
    `scholarship`,
    `race`,
    `workingHoursPerDay`,
    `workArea`,
    `age`,
    `rh`,
    `riskLevel`,
    `createdBy`,
    `updatedBy`,
    `created_at`,
    `updated_at`
  )

  SELECT NULL id
       , O.documentType
       , O.documentNumber, O.expeditionPlace, O.expeditionDate
       , O.firstName
       , O.lastName
       , O.fullName
       , O.birthdate
       , O.gender
       , O.profession -- (SELECT MIN(value) FROM system_parameters where `group` = 'employee_profession' and item COLLATE utf8_general_ci = O.profession) profession
       , O.eps -- (SELECT MIN(value) FROM system_parameters where `group` = 'eps' and item COLLATE utf8_general_ci = O.eps) eps
       , O.afp -- (SELECT MIN(value) FROM system_parameters where `group` = 'afp' and item COLLATE utf8_general_ci = O.afp) afp
       , O.arl -- (SELECT MIN(value) FROM system_parameters where `group` = 'arl' and item COLLATE utf8_general_ci = O.arl) arl
       , O.country_id-- (SELECT MIN(id) FROM rainlab_user_countries where `name` COLLATE utf8_general_ci = O.country_id) country_id
       , O.state_id -- (SELECT MIN(id) FROM rainlab_user_states where `name` COLLATE utf8_general_ci = O.state_id) state_id
       , O.city_id -- (SELECT MIN(id) FROM wg_towns where `name` = O.city_id) city_id
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
       , O.risk_level
       , O.created_by
       , O.created_by updatedBy
       , O.created_at
       , O.created_at updated_at
  FROM 	wg_employee_staging O
  LEFT JOIN wg_employee D ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType
  WHERE O.session_id = sessionId AND O.customer_id IS NOT NULL AND D.documentNumber IS NULL;


  UPDATE wg_customer_employee D
    INNER JOIN wg_employee E on D.employee_id = E.id
    INNER JOIN 	wg_employee_staging O ON O.documentNumber = E.documentNumber AND O.documentType = E.documentType  AND D.customer_id = O.customer_id
  SET D.contractType = O.contractType -- (SELECT MIN(value) FROM system_parameters where `group` = 'employee_contract_type' and item COLLATE utf8_general_ci = O.contractType)
    -- ,D.occupation = (SELECT MIN(value) FROM system_parameters where `group` = 'employee_occupation' and item COLLATE utf8_general_ci = O.occupation)
    ,D.occupation = O.occupation
    ,D.job = O.job
    ,D.workPlace = O.workPlace
    ,D.salary = O.salary
    ,D.isActive = O.isActive
    ,D.isAuthorized = O.isAuthorized
    ,D.work_shift = O.work_shift
    ,D.updated_at = O.created_at
    ,D.updatedBy = O.created_by
  WHERE O.session_id = sessionId AND O.customer_id IS NOT NULL AND E.documentNumber IS NOT NULL;

  INSERT INTO `wg_customer_employee` (
    `id`,
    `customer_id`,
    `employee_id`,
    `contractType`,
    `occupation`,
    `job`,
    `workPlace`,
    `salary`,
    `type`,
    `isActive`,
    `isAuthorized`,
    `primary_email`,
    `primary_cellphone`,
    `work_shift`,
    `createdBy`,
    `updatedBy`,
    `created_at`,
    `updated_at`
  )

  SELECT NULL id
       , O.customer_id
       , D.id employee_id
       , O.contractType -- (SELECT MIN(value) FROM system_parameters where `group` = 'employee_contract_type' and item COLLATE utf8_general_ci = O.contractType) contractType
       -- , (SELECT MIN(value) FROM system_parameters where `group` = 'employee_occupation' and item COLLATE utf8_general_ci = O.occupation)	occupation
       , O.occupation
       , O.job
       , O.workPlace
       , O.salary
       , 'employee' type
       , O.isActive isActive
       , O.isAuthorized
       , null primary_email
       , null primary_cellphone
       , O.work_shift
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
  WHERE O.session_id = sessionId AND O.customer_id IS NOT NULL AND CE.customer_id IS NULL;

  -- Insert Address
  INSERT INTO wg_employee_info_detail
  SELECT
    NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'dir',  O.address `value`
  FROM
    wg_employee D
    INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType
    INNER JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
    LEFT JOIN (
                 SELECT * FROM wg_employee_info_detail
                 where entityName = 'Wgroup\\Employee\\Employee' and type = 'dir'
               ) I ON I.`value` = O.address AND I.entityId = D.id
  WHERE O.session_id = sessionId AND O.customer_id IS NOT NULL  AND I.id IS NULL AND O.address <> '';

  -- Insert Telephone
  INSERT INTO wg_employee_info_detail
  SELECT
    NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'tel',  O.telephone `value`
  FROM
    wg_employee D
    INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType
    INNER JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
    LEFT JOIN (
                 SELECT * FROM wg_employee_info_detail
                 where entityName = 'Wgroup\\Employee\\Employee' and type = 'tel'
               ) I ON I.`value` = O.telephone AND I.entityId = D.id
  WHERE O.session_id = sessionId AND O.customer_id IS NOT NULL  AND I.id IS NULL AND O.telephone <> '';

  -- Insert Mobil
  INSERT INTO wg_employee_info_detail
  SELECT
    NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'cel',  O.mobil `value`
  FROM
    wg_employee D
    INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType
    INNER JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
    LEFT JOIN (
                 SELECT * FROM wg_employee_info_detail
                 where entityName = 'Wgroup\\Employee\\Employee' and type = 'cel'
               ) I ON I.`value` = O.mobil AND I.entityId = D.id
  WHERE O.session_id = sessionId AND O.customer_id IS NOT NULL  AND I.id IS NULL AND O.mobil <> '';

  -- Insert Mobil
  INSERT INTO wg_employee_info_detail
  SELECT
    NULL id, D.id entityId, 'Wgroup\\Employee\\Employee' entityName, 'email',  O.email `value`
  FROM
    wg_employee D
    INNER JOIN wg_employee_staging O ON O.documentNumber = D.documentNumber AND O.documentType = D.documentType
    INNER JOIN wg_customer_employee CE ON CE.customer_id = O.customer_id AND CE.employee_id = D.id
    LEFT JOIN (
                 SELECT * FROM wg_employee_info_detail
                 where entityName = 'Wgroup\\Employee\\Employee' and type = 'email'
               ) I ON I.`value` = O.email AND I.entityId = D.id
  WHERE O.session_id = sessionId AND O.customer_id IS NOT NULL AND I.id IS NULL AND O.email <> '';

  UPDATE wg_customer_employee ce
    INNER JOIN (
      SELECT
        ce.id,
        i.`value`,
        i.id itemId
      FROM
        wg_employee e
        INNER JOIN wg_customer_employee ce ON e.id = ce.employee_id
        INNER JOIN view_customer_employee_info i ON e.id = i.entityId
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
        INNER JOIN view_customer_employee_info i ON e.id = i.entityId
            AND i.type = 'cel'
    ) i ON ce.id = i.id
  SET primary_cellphone = i.itemId
  WHERE primary_cellphone IS NULL OR primary_cellphone = '' OR primary_cellphone <> i.itemId;

END;


