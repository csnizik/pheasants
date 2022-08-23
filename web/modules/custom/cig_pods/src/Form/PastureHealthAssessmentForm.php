<?php

namespace Drupal\cig_pods\Form;

Use Drupal\Core\Form\FormBase;
Use Drupal\Core\Form\FormStateInterface;
Use Drupal\asset\Entity\Asset;
Use Drupal\Core\Url;


class PastureHealthAssessmentForm extends FormBase {

    public function getSHMUOptions(){
		$shmu_assets = \Drupal::entityTypeManager() -> getStorage('asset') -> loadByProperties(
			['type' => 'soil_health_management_unit']
		 );
		 $shmu_options = [];
		 $shmu_options[''] = '- Select -';
		 $shmu_keys = array_keys($shmu_assets);
		 foreach($shmu_keys as $shmu_key) {
		   $asset = $shmu_assets[$shmu_key];
		   $shmu_options[$shmu_key] = $asset -> getName();
		 }

		 return $shmu_options;
	}

	public function getLandUseOptions(){
		$land_use_assets = \Drupal::entityTypeManager() -> getStorage('taxonomy_term') -> loadByProperties(
			['vid' => 'd_land_use']
		 );
		 $land_use_options = [];
		 $land_use_options[''] = '- Select -';
		 $land_use_keys = array_keys($land_use_assets);
		 foreach($land_use_keys as $land_use_key) {
		   $asset = $land_use_assets[$land_use_key];
		   $land_use_options[$land_use_key] = $asset -> getName();
		 }

		 return $land_use_options;
	}

    /**
   * {@inheritdoc}
   */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
		$form['#attached']['library'][] = 'cig_pods/pasture_health_assessment_form';
        $form['#attached']['library'][] = 'cig_pods/css_form';
		$form['#tree'] = TRUE;

		$severity_options = ['' => '- Select -', 5 => 'Extreme to Total', 4 => 'Moderate to Extreme', 3 => 'Moderate', 2 => 'Slight to Moderate', 1 => 'None to Slight'];

		$is_edit = $id <> NULL;

		if($is_edit){
			$form_state->set('operation', 'edit');
			$form_state->set('assessment_id', $id);
			$assessment = \Drupal::entityTypeManager()->getStorage('asset')->load($id);
		} else {
			$form_state->set('operation', 'create');
		}

        $form['producer_title'] = [
			'#markup' => '<h1> <b> Assessments </b> </h1>',
		];
		// TOOD: Attach appropriate CSS for this to display correctly
		$form['subform_1'] = [
			'#markup' => '<div class="subform-title-container"><h2>Determining Indicators of Pastureland Health Assessment</h2><h4>Section 1 of 1</h4></div>'
		];

        $pasture_health_assessment_shmu_value = $is_edit ? $assessment->get('pasture_health_assessment_shmu')->target_id : '';
		$form['pasture_health_assessment_shmu'] = [
			'#type' => 'select',
			'#title' => 'Select a Soil Health Management Unit (SHMU)',
			'#options' => $this->getSHMUOptions(),
			'#default_value' => $pasture_health_assessment_shmu_value,
			'#required' => TRUE,
		];

		$pasture_health_assessment_land_use_value = $is_edit ? $assessment->get('pasture_health_assessment_land_use')->target_id : '';
		$form['pasture_health_assessment_land_use'] = [
			'#type' => 'select',
			'#title' => 'Land Use',
			'#options' => $this->getLandUseOptions(),
			'#default_value' => $pasture_health_assessment_land_use_value,
			'#required' => TRUE,
		];

        $pasture_health_assessment_erosion_sheet_value = $is_edit ? $assessment->get('pasture_health_assessment_erosion_sheet')->value : '';

		$form['pasture_health_assessment_erosion_sheet'] = [
			'#type' => 'select',
			'#title' => $this->t('Erosion (Sheet and Rill)'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_erosion_sheet_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_erosion_gullies_value = $is_edit ? $assessment->get('pasture_health_assessment_erosion_gullies')->value : '';

		$form['pasture_health_assessment_erosion_gullies'] = [
			'#type' => 'select',
			'#title' => $this->t('Erosion (Gullies if present)'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_erosion_gullies_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_erosion_wind_scoured_value = $is_edit ? $assessment->get('pasture_health_assessment_erosion_wind_scoured')->value : '';

		$form['pasture_health_assessment_erosion_wind_scoured'] = [
			'#type' => 'select',
			'#title' => $this->t('Erosion, Wind-Scoured and/or Depositional Areas'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_erosion_wind_scoured_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_erosion_streambank_value = $is_edit ? $assessment->get('pasture_health_assessment_erosion_streambank')->value : '';

		$form['pasture_health_assessment_erosion_streambank'] = [
			'#type' => 'select',
			'#title' => $this->t('Erosion (Streambank or Shoreline)'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_erosion_streambank_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_water_flow_patterns_value = $is_edit ? $assessment->get('pasture_health_assessment_water_flow_patterns')->value : '';

		$form['pasture_health_assessment_water_flow_patterns'] = [
			'#type' => 'select',
			'#title' => $this->t('Water flow patterns'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_water_flow_patterns_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_bare_ground_value = $is_edit ? $assessment->get('pasture_health_assessment_bare_ground')->value : '';

		$form['pasture_health_assessment_bare_ground'] = [
			'#type' => 'select',
			'#title' => $this->t('Bare Ground (Percent)'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_bare_ground_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_padestals_value = $is_edit ? $assessment->get('pasture_health_assessment_padestals')->value : '';

		$form['pasture_health_assessment_padestals'] = [
			'#type' => 'select',
			'#title' => $this->t('Pedestals and/or Terracettes'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_padestals_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_litter_movement_value = $is_edit ? $assessment->get('pasture_health_assessment_litter_movement')->value : '';

		$form['pasture_health_assessment_litter_movement'] = [
			'#type' => 'select',
			'#title' => $this->t('Litter movement (Wind or Water)'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_litter_movement_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_composition_value = $is_edit ? $assessment->get('pasture_health_assessment_composition')->value : '';

		$form['pasture_health_assessment_composition'] = [
			'#type' => 'select',
			'#title' => $this->t('Effects of Plant Community Composition and Distribution on Infiltration and Runoff'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_composition_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_soil_surface_value = $is_edit ? $assessment->get('pasture_health_assessment_soil_surface')->value : '';

		$form['pasture_health_assessment_soil_surface'] = [
			'#type' => 'select',
			'#title' => $this->t('Soil surface loss or degratation'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_soil_surface_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_compaction_layer_value = $is_edit ? $assessment->get('pasture_health_assessment_compaction_layer')->value : '';

		$form['pasture_health_assessment_compaction_layer'] = [
			'#type' => 'select',
			'#title' => $this->t('Compaction Layer'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_compaction_layer_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_live_plant_value = $is_edit ? $assessment->get('pasture_health_assessment_live_plant')->value : '';

		$form['pasture_health_assessment_live_plant'] = [
			'#type' => 'select',
			'#title' => $this->t('Live plant foliar cover (hydrologic and erosion benefits)'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_live_plant_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_forage_plant_value = $is_edit ? $assessment->get('pasture_health_assessment_forage_plant')->value : '';

		$form['pasture_health_assessment_forage_plant'] = [
			'#type' => 'select',
			'#title' => $this->t('Forage Plant Diversity'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_forage_plant_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_percent_desirable_value = $is_edit ? $assessment->get('pasture_health_assessment_percent_desirable')->value : '';

		$form['pasture_health_assessment_percent_desirable'] = [
			'#type' => 'select',
			'#title' => $this->t('Percent Desirable Forage Plants (for Identified Livestock Class)'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_percent_desirable_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_invasive_plants_value = $is_edit ? $assessment->get('pasture_health_assessment_invasive_plants')->value : '';

		$form['pasture_health_assessment_invasive_plants'] = [
			'#type' => 'select',
			'#title' => $this->t('Invasive Plants'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_invasive_plants_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_annual_production_value = $is_edit ? $assessment->get('pasture_health_assessment_annual_production')->value : '';

		$form['pasture_health_assessment_annual_production'] = [
			'#type' => 'select',
			'#title' => $this->t('Annual Production'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_annual_production_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_plant_vigor_value = $is_edit ? $assessment->get('pasture_health_assessment_plant_vigor')->value : '';

		$form['pasture_health_assessment_plant_vigor'] = [
			'#type' => 'select',
			'#title' => $this->t('Plant Vigor with an Emphasis on Reproductive Capability of Perennial'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_plant_vigor_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_dying_plants_value = $is_edit ? $assessment->get('pasture_health_assessment_dying_plants')->value : '';

		$form['pasture_health_assessment_dying_plants'] = [
			'#type' => 'select',
			'#title' => $this->t('Dead or Dying Plants or Plant'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_dying_plants_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_little_cover_value = $is_edit ? $assessment->get('pasture_health_assessment_little_cover')->value : '';

		$form['pasture_health_assessment_little_cover'] = [
			'#type' => 'select',
			'#title' => $this->t('Litter cover and depth'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_little_cover_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_nontoxic_legumes_value = $is_edit ? $assessment->get('pasture_health_assessment_nontoxic_legumes')->value : '';

		$form['pasture_health_assessment_nontoxic_legumes'] = [
			'#type' => 'select',
			'#title' => $this->t('Percentage Nontoxic legumes'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_nontoxic_legumes_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_uniformity_value = $is_edit ? $assessment->get('pasture_health_assessment_uniformity')->value : '';

		$form['pasture_health_assessment_uniformity'] = [
			'#type' => 'select',
			'#title' => $this->t('Uniformity of Use'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_uniformity_value,
			'#required' => FALSE,
		];

		$pasture_health_assessment_livestock_value = $is_edit ? $assessment->get('pasture_health_assessment_livestock')->value : '';

		$form['pasture_health_assessment_livestock'] = [
			'#type' => 'select',
			'#title' => $this->t('Livestock Concentration Areas'),
			'#options' => $severity_options,
			'#default_value' => $pasture_health_assessment_livestock_value,
			'#required' => FALSE,
		];

		$form['actions']['save'] = [
			'#type' => 'submit',
			'#value' => 'Save'
		];

		$form['actions']['cancel'] = [
			'#type' => 'submit',
			'#value' => $this->t('Cancel'),
			'#submit' => ['::dashboardRedirect'],
			'#limit_validation_errors' => '',

		];

        if($is_edit){
            $form['actions']['delete'] = [
                '#type' => 'submit',
                '#value' => $this->t('Delete'),
                '#submit' => ['::deleteFieldAssessment'],
            ];
        }

        return $form;
    }

    /**
   * {@inheritdoc}
   */
    public function validateForm(array &$form, FormStateInterface $form_state){
        return;
    }

    public function dashboardRedirect(array &$form, FormStateInterface $form_state){
        $form_state->setRedirect('cig_pods.awardee_dashboard_form');
    }

	public function deleteFieldAssessment(array &$form, FormStateInterface $form_state){

		$assessment_id = $form_state->get('assessment_id');
		$pasturehealth= \Drupal::entityTypeManager()->getStorage('asset')->load($assessment_id);

		try{
            $pasturehealth->delete();
			$form_state->setRedirect('cig_pods.awardee_dashboard_form');
        }catch(\Exception $e){
            $this
          ->messenger()
          ->addError($this
          ->t($e->getMessage()));
        }
	}

	public function createElementNames(){
		return array('pasture_health_assessment_shmu', 'pasture_health_assessment_land_use', 'pasture_health_assessment_erosion_sheet', 'pasture_health_assessment_erosion_gullies', 
		'pasture_health_assessment_erosion_wind_scoured', 'pasture_health_assessment_erosion_streambank', 'pasture_health_assessment_water_flow_patterns', 'pasture_health_assessment_bare_ground', 
		'pasture_health_assessment_padestals', 'pasture_health_assessment_litter_movement', 'pasture_health_assessment_composition', 'pasture_health_assessment_soil_surface',
		'pasture_health_assessment_compaction_layer', 'pasture_health_assessment_live_plant', 'pasture_health_assessment_forage_plant', 'pasture_health_assessment_percent_desirable', 
		'pasture_health_assessment_invasive_plants', 'pasture_health_assessment_annual_production', 'pasture_health_assessment_plant_vigor', 'pasture_health_assessment_dying_plants', 
		'pasture_health_assessment_little_cover', 'pasture_health_assessment_nontoxic_legumes', 'pasture_health_assessment_uniformity', 'pasture_health_assessment_livestock');
	}
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

		$pasture_health_submission = [];
        if($form_state->get('operation') === 'create'){
            $elementNames = $this->createElementNames();
            foreach($elementNames as $elemName){
                $pasture_health_submission[$elemName] = $form_state->getValue($elemName);
            }

            $pasture_health_submission['type'] = 'pasture_health_assessment';
            $pastureAssessment = Asset::create($pasture_health_submission);
			$pastureAssessment->set('name', 'Pasture Health');
            $pastureAssessment -> save();

            $form_state->setRedirect('cig_pods.awardee_dashboard_form');

        }else{
            $id = $form_state->get('assessment_id');
            $pastureHealthAssessment = \Drupal::entityTypeManager()->getStorage('asset')->load($id);

            $elementNames = $this->createElementNames();
		    foreach($elementNames as $elemName){
                $pastureHealthAssessment->set($elemName, $form_state->getValue($elemName));
            }
			$pastureHealthAssessment->set('name', 'Pasture Health');
            $pastureHealthAssessment->save();
            $form_state->setRedirect('cig_pods.awardee_dashboard_form');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'pasture_health_assessments_form';
    }
}