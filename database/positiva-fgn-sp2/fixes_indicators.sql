alter table wg_positiva_fgn_consolidated_indicators
  add gestpos_activity_id int null comment 'Id de la actividad gestpos';

alter table wg_positiva_fgn_consolidated_indicators
  add gestpos_task_id int null comment 'Id de la tarea gestpos';

alter table wg_positiva_fgn_consolidated_indicators
  add total_programmed int NULL COMMENT 'Total programado incluyendo cuando las actividades no aporten a cumplimiento.';

alter table wg_positiva_fgn_consolidated_indicators
  add total_executed int NULL COMMENT 'Total ejecutado incluyendo cuando las actividades no aporten a cumplimiento.';

alter table wg_positiva_fgn_consolidated_indicators
  add total_hour_programmed int NULL COMMENT 'Total de horas programadas incluyendo cuando las actividades no aporten a cumplimiento.';

alter table wg_positiva_fgn_consolidated_indicators
  add total_hour_executed int NULL COMMENT 'Total de horas ejecutadas incluyendo cuando las actividades no aporten a cumplimiento.';

alter table wg_positiva_fgn_consolidated_indicators
  add total_call int NULL COMMENT 'Total de convocados incluyendo cuando las actividades no aporten a cobertura.';

alter table wg_positiva_fgn_consolidated_indicators
  add total_assistants int NULL COMMENT 'Total de asistentes incluyendo cuando las actividades no aporten a cobertura.';

alter table wg_positiva_fgn_consolidated_indicators
  add config_id int null comment 'Id de la configuración de la actividad';

