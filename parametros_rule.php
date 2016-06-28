<?php
/*
 * Custom code for rule - Parametros de Costos
 */
/*
 * Load today date
 */
$today = date('Y-m-d');

// Load fields from Existencias Semanales
/*
 * field_diesel_exist
 */
$nid = $node->nid;
$exist_wrapper = entity_metadata_wrapper('node', $nid);
$diesel_actual = $exist_wrapper->field_diesel_exist->value(); 
$fecha_exist = $exist_wrapper->field_fecha_exist->value();

/*
 * Query to get the Barco Viaje NID
 */
$query = db_select('field_data_field_barco_viaje_semanal', 'bvsemanal');
$query->addField('bvsemanal', 'field_barco_viaje_semanal_target_id', 'bvtid');
$query->addField('bvsemanal', 'entity_id', 'eid');
$query->condition('bvsemanal.entity_id', $nid, '=');
$exeResults = $query->execute();
$results = $exeResults->fetchAll();
foreach ($results as $result) {
  $bvnid = $result->bvtid;
}

/*
 * Query to get the Parametros de Costos NID
 */
$query2 = db_select('field_data_field_barco_viaje_param', 'bvparam');
$query2->addField('bvparam', 'entity_id', 'eidparam');
$query2->addField('bvparam', 'field_barco_viaje_param_target_id', 'tidparam');
$query2->condition('bvparam.field_barco_viaje_param_target_id', $bvnid, '=');
$exeResults2 = $query2->execute();
$results2 = $exeResults2->fetchAll();
foreach ($results2 as $result2) {
  $eidparam = $result2->eidparam;
}

/*
 * Query to get the sum of all fish until today.
 */
$query3 = db_select('field_data_field_buscar_viaje_diario', 'rdiatid');
$query3->join('field_data_field_total_capt_diario', 'joincapt', 'rdiatid.entity_id = joincapt.entity_id');
$query3->addField('rdiatid', 'entity_id', 'eidiario');
$query3->addField('rdiatid', 'field_buscar_viaje_diario_target_id', 'tidiario');
$query3->addField('joincapt', 'field_total_capt_diario_value', 'totalcapt');
$query3->condition('rdiatid.field_buscar_viaje_diario_target_id', $bvnid, '=');
$query3->addExpression('SUM(field_total_capt_diario_value)', 'totalsum');
$exeResults3 = $query3->execute();
$results3 = $exeResults3->fetchAll();
foreach ($results3 as $result3) {
  $tidiario = $result3->tidiario;
  /*
   * Toneladas Acumuladas
   */
  $totalsum = $result3->totalsum;
}

// Load fields from Parametros de Costos
/*
 * field_costo_variable_param
 * field_costo_fijo_param
 * field_exist_diesel_param
 * field_precio_diesel_param
 * field_viaje
 * field_fecha_zarpe_viaje
 */
$param = entity_metadata_wrapper('node', $eidparam);
$costo_variable = $param->field_costo_variable_param->value();
$costo_fijo = $param->field_costo_fijo_param->value();
$exist_diesel = $param->field_exist_diesel_param->value();
$precio_diesel = $param->field_precio_diesel_param->value();
$viaje = $param->field_viaje->value();
$fecha_zarpe = date('Y-m-d', $param->field_fecha_zarpe_viaje->value());

/*
 * Calculate Days from Departure
 */
$dias = strtotime($today) - strtotime($fecha_zarpe);
/*
 * Dias de Pesca
 */
$dias_pesca = floor($dias/3600/24);

/*
 * Costo por Tonelada
 * 
 */
$dias_costo_fijo = $dias_pesca * $costo_fijo;
$total_costo_variable = $totalsum * $costo_variable;
$costos = $dias_costo_fijo + $total_costo_variable;
$litros_diesel = ($exist_diesel - $diesel_actual) * $precio_diesel;
$costo_tonelada = ($costos + $litros_diesel)/$totalsum; 

$uidparam = strval($nid) . strval($bvnid) . strval($eidparam);

$queryuid = db_select('field_data_field_uid_sparam', 'uidtable');
$queryuid->addField('uidtable', 'field_uid_sparam_value', 'uidvalue');
$queryuid->addField('uidtable', 'entity_id', 'eidsparam');
$queryuid->condition('uidtable.field_uid_sparam_value', $uidparam, '=');
$uidresults = $queryuid->execute();
$uidres = $uidresults->fetchAll();
foreach ($uidres as $uids) {
  $uidvalue = $uids->uidvalue;
  $eidsparam = $uids->eidsparam;
}


// Create the Entity: parametros_semanale
global $user;
$values = array(
  'type' => 'parametros_semanales',
  'uid' => $user->uid,
  'status' => 1,
  'comment' => 0,
  'promote' => 0,
);

if ($uidparam == $uidvalue) {
  // Update Parametros de Costos Semanal
  $entity = entity_metadata_wrapper('node', $eidsparam);
  $entity->field_costo_sparam->set($costo_tonelada);
  $entity->save();
  drupal_set_message(t('Parámetros actualizados correctamente. El costo por tonelada de '
      . 'esta semana fue: @costo_tonelada', array(
        '@costo_tonelada' => number_format($costo_tonelada, 2),
      )));
}
else {
  //Create Parametros de Costos Semanal
  $node = entity_create('node', $values);

  $entity = entity_metadata_wrapper('node', $node);
  $entity->field_barco_viaje_sparam->set($bvnid);
  $entity->field_dias_de_pesca_sparam->set($dias_pesca);
  $entity->field_toneladas_sparam->set($totalsum);
  $entity->field_costo_sparam->set($costo_tonelada);
  $entity->field_fecha_sparam->set($fecha_exist);
  $entity->field_uid_sparam->set($uidparam);
  $entity->save();
  
  drupal_set_message(t('Parámetros creados existosamente. El costo por tonelada de '
      . 'esta semana fue: @costo_tonelada', array(
        '@costo_tonelada' => number_format($costo_tonelada, 2),
      )));
}
?>
