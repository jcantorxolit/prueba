ALTER TABLE wg_customer_vr_employee_experience_observation
  MODIFY observation_type varchar(50) NULL;

ALTER TABLE wg_customer_vr_employee_staging
  MODIFY observation_type varchar(50) NULL;

DROP TABLE IF EXISTS wg_customer_vr_employee_experiences_progress_log;


CREATE TABLE wg_customer_vr_employee_experiences_progress_log
(
  id int AUTO_INCREMENT PRIMARY KEY,
  customer_vr_employee_id int comment 'Identificador del registro del rv',
  customer_vr_employee_experience_id int comment 'Identificador del la experiencia para esa rv',
  experience_code VARCHAR(10) COMMENT 'codigo de la experiencia',
  questions INT COMMENT 'cantidad de preguntas',
  answers INT COMMENT 'cantidad total de respuestas',
  si INT COMMENT 'cantidad de respuestas en si',
  no INT COMMENT 'cantidad de respuestas en no',
  na INT COMMENT 'cantidad de respuestas en no aplica',
  percent float COMMENT 'porcentaje entre las respondidas en si / total de respuestas',
  created_at datetime COMMENT 'fecha que se realiza el registro'
);

CREATE INDEX wg_customer_vr_employee_experiences_progress_log_customer_vr
  ON wg_customer_vr_employee_experiences_progress_log(customer_vr_employee_id);

CREATE INDEX wg_customer_vr_employee_experiences_progress_log
  ON wg_customer_vr_employee_experiences_progress_log(customer_vr_employee_experience_id);


DROP PROCEDURE IF EXISTS TL_VR_Employee_Head;

CREATE PROCEDURE TL_VR_Employee_Head(IN customerId bigint, IN sessionId varchar(255))
BEGIN
  INSERT INTO wg_employee ( documentType, documentNumber, firstName, lastName, fullName, gender, isActive, createdBy, updatedBy, created_at, updated_at)
  SELECT DISTINCT DT.value
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
  SELECT DISTINCT O.customer_id
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
END;


ALTER TABLE wg_customer_vr_satisfactions_questions
  DROP COLUMN customer_id;

ALTER TABLE wg_customer_vr_satisfactions_questions
  DROP COLUMN experience;

ALTER TABLE wg_customer_vr_satisfactions_responses
  ADD COLUMN experience varchar(10) AFTER customer_id;

ALTER TABLE wg_customer_vr_satisfactions_responses
  ADD COLUMN chart_type varchar(10);




-- auto-generated definition
CREATE TABLE wg_config_vr_certificate_information
(
  id int AUTO_INCREMENT PRIMARY KEY,
  full_name varchar(200),
  job varchar(200),
  created_at datetime DEFAULT CURRENT_TIMESTAMP(),
  updated_at datetime DEFAULT CURRENT_TIMESTAMP()
);


INSERT INTO wg_config_vr_certificate_information (full_name, job)VALUES
  ('Carlos Perez', 'Director General');




INSERT INTO wg_customer_vr_employee_experiences_progress_log
(customer_vr_employee_id, customer_vr_employee_experience_id, experience_code,
 questions, answers, si, no, na, percent, created_at)

SELECT
  emp_exp.customer_vr_employee_id,
  emp_exp.id AS customer_vr_employee_experience_id,
  emp_exp.experience_code,
  COUNT(question.id) AS questions,
  COUNT(answer.id) AS answers,
  COUNT(IF(answer.value = 'SI', answer.id, NULL)) as si,
  COUNT(IF(answer.value = 'NO', answer.id, NULL)) as no,
  COUNT(IF(answer.value = 'NA', answer.id, NULL)) as na,
  IF(COUNT(answer.id) > 0,
     COALESCE(
         ROUND(
             COUNT(IF(answer.value = 'SI', 1, NULL)) /
               COUNT(answer.id)
           , 2) * 100
       , 0)
    , 0) AS percent,
  answer_rv.registration_date as created_at
FROM `wg_customer_vr_employee_experience` as emp_exp
INNER JOIN wg_customer_vr_employee vr on vr.id = emp_exp.customer_vr_employee_id
LEFT JOIN `wg_customer_vr_employee_answer_experience` answer_rv ON answer_rv.customer_vr_employee_id = emp_exp.customer_vr_employee_id
LEFT JOIN wg_customer_vr_employee_question_scene as question ON question.experience_scene_code = emp_exp.experience_scene_code
LEFT JOIN wg_customer_vr_employee_answer_scene as answer ON question.id = answer.customer_vr_employee_question_scene_id
    AND answer_rv.id = answer.customer_vr_employ_answer_experience_id
WHERE emp_exp.application = 'SI'
GROUP BY emp_exp.customer_vr_employee_id, emp_exp.experience_code;



DROP PROCEDURE TL_VR_Employee;

CREATE PROCEDURE TL_VR_Employee(IN customerId bigint, IN sessionId varchar(255))
BEGIN

  -- VR EMPLOYEE - DONT EXISTS
  INSERT INTO wg_customer_vr_employee (customer_employee_id, document_type, customer_id, is_active, created_at, created_by, staging_id)
  SELECT
    ce.id,
    document_type.value,
    O.customer_id,
    1,
    O.created_at,
    O.created_by,
    O.id
  FROM wg_customer_vr_employee_staging O
  LEFT JOIN ( SELECT
                item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
              FROM system_parameters
              WHERE
                namespace = 'wgroup' AND `group` = 'employee_document_type'
            ) document_type ON O.document_type = document_type.item
  LEFT JOIN wg_employee e ON O.document_number = e.documentNumber AND e.documentType = document_type.value
  LEFT JOIN wg_customer_employee ce ON e.id = ce.employee_id AND ce.customer_id = O.customer_id
  LEFT JOIN wg_customer_vr_employee D ON O.customer_id = D.customer_id AND ce.id = D.customer_employee_id
  LEFT JOIN wg_customer_vr_employee_answer_experience vrae2 ON D.id = vrae2.customer_vr_employee_id AND O.registration_date = vrae2.registration_date
  WHERE
    ce.id IS NOT NULL AND O.session_id = sessionId AND O.customer_id = customerId
  GROUP BY O.document_number, O.document_type, O.customer_id, O.registration_date, e.documentType, e.documentNumber
  HAVING count(vrae2.id) = 0;


  -- DAILY VR EMPLOYEE
  INSERT INTO wg_customer_vr_employee_answer_experience (customer_vr_employee_id, registration_date, created_at, created_by, staging_id)
  SELECT
    vr.id,
    O.registration_date,
    O.created_at,
    O.created_by,
    O.id
  FROM wg_customer_vr_employee_staging O
  LEFT JOIN ( SELECT
                item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
              FROM system_parameters
              WHERE
                namespace = 'wgroup' AND `group` = 'employee_document_type'
            ) document_type ON O.document_type = document_type.item
  JOIN wg_employee e ON O.document_number = e.documentNumber AND e.documentType = document_type.value
  JOIN wg_customer_employee ce ON e.id = ce.employee_id AND ce.customer_id = O.customer_id
  JOIN wg_customer_vr_employee vr ON O.customer_id = vr.customer_id AND ce.id = vr.customer_employee_id
  LEFT JOIN wg_customer_vr_employee_answer_experience D ON vr.id = D.customer_vr_employee_id
  LEFT JOIN wg_customer_vr_employee_answer_experience D2 ON vr.id = D2.customer_vr_employee_id AND O.registration_date = D2.registration_date
  WHERE
    D.id IS NULL AND
    O.session_id = sessionId AND O.customer_id = customerId
  GROUP BY vr.id, O.registration_date
  HAVING count(D2.id) = 0;


  -- CONFIG EXPERIENCE
  INSERT INTO wg_customer_vr_employee_experience (customer_vr_employee_id, experience_code, experience_scene_code, application, created_at, created_by, staging_id)
  SELECT
    vr.id,
    experience_vr.value,
    experience_scene.value,
    'SI',
    O.created_at,
    O.created_by,
    O.id
  FROM wg_customer_vr_employee_staging O
  JOIN ( SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE
           namespace = 'wgroup' AND `group` = 'employee_document_type'
       ) document_type ON O.document_type = document_type.item
  JOIN wg_employee e ON O.document_number = e.documentNumber AND e.documentType = document_type.value
  JOIN wg_customer_employee ce ON e.id = ce.employee_id AND ce.customer_id = O.customer_id
  JOIN wg_customer_vr_employee vr ON O.customer_id = vr.customer_id AND ce.id = vr.customer_employee_id
  LEFT JOIN ( SELECT
                item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
              FROM system_parameters
              WHERE
                namespace = 'wgroup' AND `group` = 'experience_vr'
       ) experience_vr ON O.experience = experience_vr.item
  LEFT JOIN ( SELECT
                item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`, code
              FROM system_parameters
              WHERE
                namespace = 'wgroup' AND `group` = 'experience_scene'
       ) experience_scene ON O.experience_scene = experience_scene.item AND experience_vr.value = experience_scene.code
  LEFT JOIN wg_customer_vr_employee_answer_experience ae on ae.customer_vr_employee_id = vr.id and ae.registration_date = O.registration_date
  LEFT JOIN wg_customer_vr_employee_experience D ON D.customer_vr_employee_id = ae.customer_vr_employee_id
      AND D.experience_code = experience_vr.value
      AND D.experience_scene_code = experience_scene.value
  WHERE
    D.id is null
    AND ae.id IS NOT NULL
    AND experience_scene.value IS NOT NULL
    AND O.session_id = sessionId
    AND O.customer_id = customerId
  GROUP BY vr.id, O.experience, O.experience_scene;


  -- ANSWER
  INSERT INTO wg_customer_vr_employee_answer_scene (customer_vr_employ_answer_experience_id, customer_vr_employee_question_scene_id, `value`, observation, created_at, created_by, staging_id)
  SELECT
    vrae.id,
    vrqe.id,
    experience_scene_application.value,
    O.justification,
    O.created_at,
    O.created_by,
    O.id
  FROM wg_customer_vr_employee_staging O
  JOIN ( SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE
           namespace = 'wgroup' AND `group` = 'experience_vr'
       ) experience_vr ON O.experience = experience_vr.item
  JOIN ( SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`, code
         FROM system_parameters
         WHERE
           namespace = 'wgroup' AND `group` = 'experience_scene'
       ) experience_scene ON O.experience_scene = experience_scene.item AND experience_vr.value = experience_scene.code
  JOIN wg_customer_vr_employee_question_scene vrqe ON O.indicator = vrqe.description AND experience_scene.value = vrqe.experience_scene_code
  JOIN ( SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE
           namespace = 'wgroup' AND `group` = 'employee_document_type'
       ) document_type ON O.document_type = document_type.item
  JOIN wg_employee e ON O.document_number = e.documentNumber AND e.documentType = document_type.value
  JOIN wg_customer_employee ce ON e.id = ce.employee_id AND ce.customer_id = O.customer_id
  JOIN wg_customer_vr_employee vr ON O.customer_id = vr.customer_id AND ce.id = vr.customer_employee_id
  JOIN wg_customer_vr_employee_answer_experience vrae ON vr.id = vrae.customer_vr_employee_id AND O.registration_date = vrae.registration_date
  JOIN ( SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE
           namespace = 'wgroup' AND `group` = 'experience_scene_application'
       ) experience_scene_application ON O.value = experience_scene_application.item
  LEFT JOIN wg_customer_vr_employee_answer_scene D ON vrae.id = D.customer_vr_employ_answer_experience_id  AND vrqe.id = D.customer_vr_employee_question_scene_id
  WHERE D.id IS NULL AND O.session_id = sessionId AND O.customer_id = customerId
  GROUP BY O.document_number, O.document_type, O.customer_id, O.registration_date, O.experience, O.experience_scene, O.indicator;


  -- OBSERVATION
  INSERT INTO wg_customer_vr_employee_experience_observation (customer_vr_employ_answer_experience_id, experience_code, observation_type, observation_value, created_at, created_by, staging_id)
  SELECT
    vrae.id,
    experience_vr.value,
    experience_scene_observation_type.value,
    O.observation_value,
    O.created_at,
    O.created_by,
    O.id
  FROM wg_customer_vr_employee_staging O
  JOIN ( SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE
           namespace = 'wgroup' AND `group` = 'employee_document_type'
       ) document_type ON O.document_type = document_type.item
  JOIN wg_employee e ON O.document_number = e.documentNumber AND e.documentType = document_type.value
  JOIN wg_customer_employee ce ON e.id = ce.employee_id AND ce.customer_id = O.customer_id
  JOIN wg_customer_vr_employee vr ON O.customer_id = vr.customer_id AND ce.id = vr.customer_employee_id
  JOIN wg_customer_vr_employee_answer_experience vrae ON vr.id = vrae.customer_vr_employee_id AND O.registration_date = vrae.registration_date
  JOIN ( SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE
           namespace = 'wgroup' AND `group` = 'experience_vr'
       ) experience_vr ON O.experience = experience_vr.item
  JOIN ( SELECT
           item COLLATE utf8_general_ci AS  item, `value` COLLATE utf8_general_ci AS `value`
         FROM system_parameters
         WHERE
           namespace = 'wgroup' AND `group` = 'experience_scene_observation_type'
       ) experience_scene_observation_type ON O.observation_type = experience_scene_observation_type.item
  LEFT JOIN wg_customer_vr_employee_experience_observation D ON vrae.id = D.customer_vr_employ_answer_experience_id  AND experience_vr.value = D.experience_code
  WHERE D.id IS NULL AND O.session_id = sessionId AND O.customer_id = customerId
  GROUP BY O.document_number, O.document_type, O.customer_id, O.registration_date, vrae.id, experience_vr.item;


  -- calcule average
  UPDATE wg_customer_vr_employee vr
    JOIN wg_customer_vr_employee_answer_experience exp_ans ON exp_ans.customer_vr_employee_id = vr.id
    JOIN wg_customer_vr_employee_answer_scene ans ON ans.customer_vr_employ_answer_experience_id = exp_ans.id
    JOIN wg_customer_vr_employee_staging O ON O.id = ans.staging_id and O.customer_id = vr.customer_id
    JOIN (
      SELECT
        wg_customer_vr_employee_experience.customer_vr_employee_id,
        COUNT(wg_customer_vr_employee_question_scene.id) as questions,
        COUNT(wg_customer_vr_employee_answer_scene.id) as answers
      FROM
        wg_customer_vr_employee_experience
        left join wg_customer_vr_employee_answer_experience on
          wg_customer_vr_employee_experience.customer_vr_employee_id = wg_customer_vr_employee_answer_experience.customer_vr_employee_id
        left join wg_customer_vr_employee_question_scene on
          wg_customer_vr_employee_experience.experience_scene_code = wg_customer_vr_employee_question_scene.experience_scene_code
        left join wg_customer_vr_employee_answer_scene on
          wg_customer_vr_employee_question_scene.id = wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id
              and wg_customer_vr_employee_answer_experience.id = wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id
      WHERE wg_customer_vr_employee_experience.application = 'SI'
      group by wg_customer_vr_employee_experience.customer_vr_employee_id
    ) D on vr.id = D.customer_vr_employee_id
  SET vr.average = IF(D.answers > 0, CONVERT(ROUND((D.answers/D.questions)*100,0), CHAR(10)), 0),
    vr.is_active = 1,
    vr.updated_at = NOW()
  WHERE O.session_id = sessionId AND O.customer_id = customerId;


  -- save log experience's answers
  DELETE wg_customer_vr_employee_experiences_progress_log
  FROM wg_customer_vr_employee_experiences_progress_log
  JOIN wg_customer_vr_employee_experience exp on exp.customer_vr_employee_id = wg_customer_vr_employee_experiences_progress_log.customer_vr_employee_id
      AND exp.experience_code = wg_customer_vr_employee_experiences_progress_log.experience_code
  JOIN wg_customer_vr_employee_staging O ON exp.staging_id = O.id
  WHERE O.session_id = sessionId;


  INSERT INTO wg_customer_vr_employee_experiences_progress_log
  (customer_vr_employee_id, customer_vr_employee_experience_id, experience_code,
   questions, answers, si, no, na, percent, created_at)

  SELECT exp.customer_vr_employee_id,
      exp.id AS customer_vr_employee_experience_id,
      exp.experience_code,
      COUNT(DISTINCT question.id) AS questions,
      COUNT(DISTINCT answer.id) AS answers,
      COUNT(DISTINCT IF(answer.value = 'SI', answer.id, NULL)) AS si,
      COUNT(DISTINCT IF(answer.value = 'NO', answer.id, NULL)) AS no,
      COUNT(DISTINCT IF(answer.value = 'NA', answer.id, NULL)) AS na,
      IF(COUNT(answer.id) > 0,
         COALESCE(
             ROUND(
                 COUNT(DISTINCT IF(answer.value = 'SI', answer.id, NULL)) /
                   ((COUNT(DISTINCT IF(answer.value = 'SI', answer.id, NULL))) + (COUNT(DISTINCT IF(answer.value = 'NO', answer.id, NULL)))  )
               , 2) * 100
           , 0)
        , 0) AS percent,
      answer_rv.registration_date AS created_at
    FROM wg_customer_vr_employee_staging O
    JOIN wg_customer_vr_employee_answer_scene answer2 on answer2.staging_id = O.id
    JOIN wg_customer_vr_employee_answer_experience answer_rv ON answer_rv.id = answer2.customer_vr_employ_answer_experience_id
    join wg_customer_vr_employee_experience exp ON exp.customer_vr_employee_id = answer_rv.customer_vr_employee_id
    JOIN wg_customer_vr_employee_question_scene question ON question.experience_scene_code = exp.experience_scene_code
    JOIN wg_customer_vr_employee_answer_scene answer ON answer.customer_vr_employ_answer_experience_id = answer_rv.id
                                                    and answer.customer_vr_employee_question_scene_id = question.id
    WHERE exp.application = 'SI'
      AND O.session_id = sessionId
    GROUP BY exp.customer_vr_employee_id, exp.experience_code;

END;

update wg_customer_vr_employee_question_scene
SET description = TRIM(description);


-- Jose Luis Gutierez


DROP TABLE wg_customer_vr_satisfactions_questions;

CREATE TABLE wg_customer_vr_satisfactions_questions
(
  id int AUTO_INCREMENT COMMENT 'Identificador de la tabla'
    PRIMARY KEY,
  title varchar(250) NULL COMMENT 'La pregunta',
  label varchar(20) NULL COMMENT 'Abreviación de la pregunta',
  answer_type varchar(10) NULL COMMENT 'Grupo de posibles respuestas disponibles',
  chart_type varchar(10) NULL
) COMMENT 'Almacenar las preguntas que se podrán asociar por experiencia';

INSERT INTO wg_customer_vr_satisfactions_questions (id, title, label, answer_type, chart_type) VALUES (1, 'Cómo calificaría usted esta experiencia de realidad virtual inmersiva?', 'Calificación Exp.', 'ANS001', 'line');
INSERT INTO wg_customer_vr_satisfactions_questions (id, title, label, answer_type, chart_type) VALUES (2, 'Disminución de la Accidentalidad', 'Recomendación Exp.', 'ANS003', 'pie');
INSERT INTO wg_customer_vr_satisfactions_questions (id, title, label, answer_type, chart_type) VALUES (7, 'Realismo de la experiencia', 'Realismo Exp.', 'ANS002', 'line');
INSERT INTO wg_customer_vr_satisfactions_questions (id, title, label, answer_type, chart_type) VALUES (8, 'Distracción de accidentes', 'Dis. Accidentes', 'ANS003', 'pie');
INSERT INTO wg_customer_vr_satisfactions_questions (id, title, label, answer_type, chart_type) VALUES (9, 'Recomendaría componentes personalizados', 'Recomendación Comp.', 'ANS003', 'pie');



DROP TABLE wg_customer_vr_satisfactions_answers_types;

CREATE TABLE wg_customer_vr_satisfactions_answers_types
(
  id int AUTO_INCREMENT COMMENT 'Identificador de la tabla'
    PRIMARY KEY,
  code varchar(10) NULL COMMENT 'Código del grupo de la respuesta',
  answer varchar(200) NULL COMMENT 'Respuesta',
  `order` tinyint NULL,
  color varchar(7) NULL
) COMMENT 'Almanecar los tipos de respuestas disponibles agrupados por un código';

INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (1, 'ANS001', 'Muy malo', 1, '#cb3434');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (2, 'ANS001', 'Malo', 2, '#ff0000');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (3, 'ANS001', 'Regular', 3, '#ff7f27');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (4, 'ANS001', 'Bueno', 4, '#b5e61d');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (5, 'ANS001', 'Excelente', 5, '#22b14c');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (6, 'ANS002', 'POCO REAL', 1, '#cb3434');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (7, 'ANS002', 'MEDIANAMENTE REAL', 2, '#ff7f27');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (8, 'ANS002', 'MUY REAL', 3, '#22b14c');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (11, 'ANS003', 'SI', 1, '#22b14c');
INSERT INTO wg_customer_vr_satisfactions_answers_types (id, code, answer, `order`, color) VALUES (12, 'ANS003', 'NO', 2, '#ff0000');
