<?php

/*
 * Custom code for rule - Parametros de Costos
 */
/*
 * Load today date
 */
$today = date('d-m-Y');

// Load fields from Existencias Semanales
$nid = $node->nid;
$exist_wrapper = entity_metadata_wrapper('node', $nid);
$diesel = $exist_wrapper->field_diesel_exist->value();

$query = db_select('field_data_field_barco_viaje_semanal', 'bvsemanal');
$query->addField('bvsemanal', 'field_barco_viaje_semanal_target_id', 'bvtid');
$query->addField('bvsemanal', 'entity_id', 'eid');
$query->condition('bvsemanal.entity_id', $nid, '=');
$exeResults = $query->execute();
$results = $exeResults->fetchAll();
foreach ($results as $result) {
  $bvnid = $result->bvtid;
}

$query2 = db_select('field_data_field_barco_viaje_param', 'bvparam');
$query2->addField('bvparam', 'entity_id', 'eidparam');
$query2->addField('bvparam', 'field_barco_viaje_param_target_id', 'tidparam');
$query2->condition('bvparam.field_barco_viaje_param_target_id', $bvnid, '=');
$exeResults2 = $query2->execute();
$results2 = $exeResults2->fetchAll();
foreach ($results2 as $result2) {
  $eidparam = $result2->eidparam;
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
$precio_diesel = $param->field_precio_diesel_param->value();
//$fecha_zarpe = $param->field_fecha_zarpe_viaje->value();
$fecha_zarpe = date('d/m/Y', $param->field_fecha_zarpe_viaje->value());

/*
 * Calculate Days from Departure
 */
$dias = strtotime($today) - strtotime($fecha_zarpe);
$dias_pesca = floor($dias/3600/24);

drupal_set_message(t('NID Existencias: @nid Diesel: @diesel NID Viaje: @tid '
    . 'NID Parametros: @eidparam Precio del Diesel: @precio_diesel '
    . 'Fecha de Zarpe: @fecha_zarpe Dias de Pesca: @dias_pesca', 
    array(
      '@nid' => $nid, 
      '@diesel' => $diesel, 
      '@tid' => $bvnid, 
      '@eidparam' => $eidparam, 
      '@precio_diesel' => $precio_diesel,
      '@fecha_zarpe' => $fecha_zarpe,
      '@dias_pesca' => $dias_pesca,
    )));

?>
