<?php

namespace Drupal\cig_pods\Form;

use Drupal\asset\Entity\AssetInterface;
Use Drupal\Core\Form\FormStateInterface;
Use Drupal\asset\Entity\Asset;
Use Drupal\Core\Render\Element\Checkboxes;
Use Drupal\Core\Url;
use Drupal\geofield\GeoPHP\GeoPHPWrapper;


class SoilHealthManagementUnitForm extends PodsFormBase {


	public function getShmuTypeOptions(){
    $options = $this->entityOptions('taxonomy_term', 'd_shmu_type');
		return ['' => '- Select -'] + $options;
	}
	public function getExperimentalDesignOptions(){
		$options = $this->entityOptions('taxonomy_term', 'd_experimental_design');
		return ['' => '- Select -'] + $options;

}
	public function getTillageSystemOptions(){
		$options = $this->entityOptions('taxonomy_term', 'd_tillage_system');
		return ['' => '- Select -'] + $options;

	}

	public function getYearOptions(){
		$month_options = [];
		$month_keys =  ["J","F","M","A","M","J","J","A","S","O","N","D"];
		$i = 0;
		foreach($month_keys as $month_key) {
		  $month_options[$i] = $month_key;
		  $i++;
		}
		$i = 0;


		return $month_options;
	}

	public function getMajorResourceConcernOptions(){
		$options = $this->entityOptions('taxonomy_term', 'd_major_resource_concern');
		return $options;
	}

	public function getResourceConcernOptions(){
		$options = $this->entityOptions('taxonomy_term', 'd_resource_concern');
		return $options;
	}

	public function getLandUseOptions(){
		$options = $this->entityOptions('taxonomy_term', 'd_land_use');
		return ['' => '- Select -'] + $options;

	}

	public function getPracticesAddressedOptions(){
		$options = $this->entityOptions('taxonomy_term', 'd_practice');
		return $options;

	}

	public function getProducerOptions(){
    $options = $this->entityOptions('asset', 'producer');
    return ['' => '- Select -'] + $options;
	}

	public function getLandUseModifierOptions(){
		$options = $this->entityOptions('taxonomy_term', 'd_land_use_modifiers');
		return $options;
	}

	public function getCropRotationYearOptions(){
		$max_years = 20; // Maximum number of years for crop rotations.

		$options=[];
		// Include upper bound
		for($i=1; $i < $max_years + 1; $i++){
			$options[$i] = $i;
		}
		return $options;
	}
	public function getCropOptions(){
    $options = $this->entityOptions('taxonomy_term', 'd_crop');
    return $options;
	}

	// goal is to replace this logic
	// $field_shmu_irrigation_water_ph_value = $is_edit ? $shmu->get('field_shmu_irrigation_water_ph')->numerator : '';
	// SHMU is a reference to SoilHealthManagmentUnit entity
	public function getDecimalFromSHMUFractionFieldType(object $shmu, string $field_name){
		return $shmu->get($field_name)-> numerator / $shmu->get($field_name)->denominator;
	}

	// Returns array of options suitable to be passed into '#default_value' for the checkboxes type
	// field_name must be a string relating to a field witn "multiple -> TRUE" in its definition
	public function getDefaultValuesArrayFromMultivaluedSHMUField(object $shmu, string $field_name){
		$field_iter = $shmu->get($field_name);

		$populated_values = [];
		foreach($field_iter as $key => $term){
			$populated_values[] = $term->target_id; // This is the PHP syntax to append to the array
		}
		return $populated_values;
	}
	public function getAsset($id){
		// We use load instead of load by properties here because we are looking by id
		$asset = \Drupal::entityTypeManager()->getStorage('asset')->load($id);
		return $asset;

	}

	// $shmu is expected to be of type EntityInterface
	public function getCropRotationIdsForShmu($shmu){
		$crop_rotation_target_ids = [];

		$field_shmu_crop_rotation_list = $shmu->get('field_shmu_crop_rotation_sequence'); // Expected type of FieldItemList
		foreach($field_shmu_crop_rotation_list as $key=>$value){
			$crop_rotation_target_ids[] = $value->target_id; // $value is of type EntityReferenceItem (has access to value through target_id)
		}
		return $crop_rotation_target_ids;
	}

	// Load from database into form state
	public function loadCropRotationsIntoFormState($crop_rotation_ids, $form_state){

		$ignored_fields = ['uuid','revision_id','langcode','type','revision_user','revision_log_message','uid','name', 'status', 'created', 'changed', 'archived', 'default_langcode', 'revision_default'];

		$rotations = [];
		$i = 0;
		foreach($crop_rotation_ids as $key=>$crop_rotation_id){
			$tmp_rotation = $this->getAsset($crop_rotation_id)->toArray();
			$rotations[$i] = array();
			$rotations[$i]['field_shmu_crop_rotation_crop'] = $tmp_rotation['field_shmu_crop_rotation_crop'];
			$rotations[$i]['field_shmu_crop_rotation_year'] = $tmp_rotation['field_shmu_crop_rotation_year'];
			$rotations[$i]['field_shmu_crop_rotation_crop_present'] = $tmp_rotation['field_shmu_crop_rotation_crop_present'];
			$i++;
		}

		// If rotations is still empty, set a blank crop rotation at index 0
		if($i == 0){
			$rotations[0]['field_shmu_crop_rotation_crop'] = '';
			$rotations[0]['field_shmu_crop_rotation_year'] = '';
			$rotations[0]['field_shmu_crop_rotation_crop_present'] = [];
		}
		$form_state->set('rotations', $rotations);

		return;
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state, AssetInterface $asset = NULL){
    $shmu = $asset;
		$is_edit = $shmu <> NULL;
		$irrigating = false;

		if ($form_state->get('load_done') == NULL){
			$form_state->set('load_done', FALSE);
		}

		// Determine if it is an edit process. If it is, load SHMU into local variable.
		if($is_edit){
			$form_state->set('operation','edit');
			$form_state->set('shmu_id', $shmu->id());
			$shmu_db_crop_rotations = $this->getCropRotationIdsForShmu($shmu);
			if(!$form_state->get('load_done')){
				$this->loadCropRotationsIntoFormState($shmu_db_crop_rotations, $form_state);
				$form_state->set('load_done',TRUE);
			}
			// The list of Crop Rotation assets that
			$form_state->set('original_crop_rotation_ids', $shmu_db_crop_rotations);
		} else {
			if(!$form_state->get('load_done')){
				$this->loadCropRotationsIntoFormState([], $form_state);
				$form_state->set('load_done',TRUE);
			}
			$form_state->set('operation','create');
		}

		// Attach the SHMU css library
		$form['#attached']['library'][] = 'cig_pods/soil_health_management_unit_form';
		$form['#tree'] = TRUE; // Allows getting at the values hierarchy in form state

		// First section
		$form['subform_1'] = [
			'#markup' => '<div class="subform-title-container"><h2>Soil Health Management Unit (SHMU) Setup</h2><h4>5 Fields | Section 1 of 11</h4></div>'
		];

		$field_shmu_involved_producer_value = '';

		// Look for existing producers on the SHMU
		if($is_edit){
			$producer = $shmu->get('field_shmu_involved_producer');
			if($producer <> NULL && $producer->target_id <> NULL){
				$field_shmu_involved_producer_value = $producer->target_id;
			}
		}

		$producer_select_options = $this->getProducerOptions();
		$form['field_shmu_involved_producer'] = [
			'#type' => 'select',
			'#title' => $this->t('Select a Producer'),
			'#options' => $producer_select_options,
			'#default_value' => $field_shmu_involved_producer_value,
			'#required' => TRUE
		];

		$name_value = $is_edit ? $shmu->get('name')->value : ''; // Default Value: Empty string
		$form['name'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Soil Health Management (SHMU) Name'),
			'#description' => '',
			'#default_value' => $name_value,
			'#required' => TRUE
		];

		$field_shmu_type_value = $is_edit ? $shmu->get('field_shmu_type')->target_id: ''; // Default Value: Empty String
		$shmu_type_options = $this->getShmuTypeOptions();
		$form['field_shmu_type'] = [
			'#type' => 'select',
			'#title' => $this->t('Soil Health Management Unit (SHMU) Type'),
			'#options' => $shmu_type_options,
			'#default_value' => $field_shmu_type_value,
			'#required' => TRUE
		];

		$field_shmu_replicate_number_value = $is_edit ? $this-> getDecimalFromSHMUFractionFieldType($shmu, 'field_shmu_replicate_number'): '';
		$form['field_shmu_replicate_number'] = [
			'#type' => 'number',
			'#title' => $this->t('Replicate Number'),
			'#description' => '',
			'#default_value' => $field_shmu_replicate_number_value,
			'#min_value' => 0,
			'#step' => 1, // We enforce integer with step = 1.
			'#required' => TRUE
		];

		$field_treatmenent_narrative_value = $is_edit ? $shmu->get('field_shmu_treatment_narrative')->value: '';

		$form['field_shmu_treatment_narrative'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Treatment Narrative'),
			'#description' => '',
			'#default_value' => $field_treatmenent_narrative_value,
			'#required' => FALSE
		];
		$form['subform_2'] = [
			'#markup' => '<div class="subform-title-container"><h2>Experimental Design</h2><h4>1 Field | Section 2 of 11</h4></div>'
		];

		$field_shmu_experimental_design_value = $is_edit ? $shmu->get('field_shmu_experimental_design')->target_id: '';
		$shmu_experimental_design_options = $this->getExperimentalDesignOptions();
		$form['field_shmu_experimental_design'] = [
			'#type' => 'select',
			'#title' => $this->t('Experimental Design'),
			'#options' => $shmu_experimental_design_options,
			'#default_value' => $field_shmu_experimental_design_value,
			'#required' => TRUE
		];
		// New section (Geometry entry)
		$form['subform_3'] = [
			'#markup' => '<div class="subform-title-container"><h2>Soil Health Management Unit (SHMU) Area</h2><h4>3 Fields | Section 3 of 11</h4> </div>'
		];

		$form['static_1']['content'] = [
			'#markup' => '<div>Draw your SHMU on the Map</div>',
		];

		$form['mymap'] = [
			'#type' => 'farm_map_input',
			 '#required' => TRUE,
			'#map_type' => 'pods',
			 '#behaviors' => [
				'zoom_us',
       		 	 'wkt_refresh',
      		],
      '#map_settings' => [
        'behaviors' => [
          'nrcs_soil_survey' => [
            'visible' => TRUE,
          ],
        ],
      ],
			'#display_raw_geometry' => TRUE,
			'#default_value' => $is_edit ? $shmu->get('field_geofield')->value : '',
		];

		// New section (Soil and Treatment Identification)
		$form['subform_4'] = [
			'#markup' => '<div class="subform-title-container"><h2>Soil and Treatment Identification</h2><h4>2 Fields | Section 4 of 11</h4> </div>'
		];
    $form['ssurgo_lookup'] = [
      '#type' => 'submit',
      '#value' => $this->t('Lookup via SSURGO'),
      '#ajax' => [
        'callback' => '::ssurgoDataCallback',
        'wrapper' => 'ssurgo-data',
      ],
      '#limit_validation_errors' => [['mymap']],
      '#submit' => ['::ssurgoDataLookup'],
    ];
    $form['ssurgo_data_wrapper'] = [
      '#type' => 'container',
      '#prefix' => '<div id="ssurgo-data">',
      '#suffix' => '</div>',
    ];
    $map_unit_symbol_value = $is_edit ? $shmu->get('field_shmu_map_unit_symbol')->value: '';
		$form['ssurgo_data_wrapper']['map_unit_symbol'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Map Unit Symbol'),
      '#description' => $this->t('Click "Lookup via SSURGO" to query the SSURGO database using the geometry in the map above and populate this field with the map symbols present.'),
		  '#default_value' => $map_unit_symbol_value,
    ];
    $surface_texture_value = $is_edit ? $shmu->get('field_shmu_surface_texture')->value: '';
		$form['ssurgo_data_wrapper']['surface_texture'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Surface Texture'),
      '#description' => $this->t('Click "Lookup via SSURGO" to query the SSURGO database using the geometry in the map above and populate this field with the soil textures present.'),
		  '#default_value' => $surface_texture_value,
    ];

		// New section (Land Use History)
		$form['subform_5'] = [
			'#markup' => '<div class="subform-title-container"><h2> Land Use History </h2><h4> 5 Fields | Section 5 of 11</h4></div>'
		];
		$field_shmu_prev_land_use_value = $is_edit ? $shmu->get('field_shmu_prev_land_use')->target_id : '';
		$land_use_options = $this->getLandUseOptions();
		$form['field_shmu_prev_land_use'] = [
			'#type' => 'select',
			'#title' => $this->t('Previous Land Use'),
			'#options' => $land_use_options,
			'#default_value' => $field_shmu_prev_land_use_value,
			'#required' => FALSE
		];

		$field_shmu_prev_land_use_modifiers_values = $is_edit ? $this-> getDefaultValuesArrayFromMultivaluedSHMUField($shmu, 'field_shmu_prev_land_use_modifiers') : [];
		$land_use_modifier_options = $this->getLandUseModifierOptions();
		$form['field_shmu_prev_land_use_modifiers'] = [
			'#type' => 'checkboxes',
			'#title' => $this->t('Previous Land Use Modifiers'),
			'#options' => $land_use_modifier_options,
			'#default_value' => $field_shmu_prev_land_use_modifiers_values,
			'#required' => FALSE
		];



		// For the Date input fields, we have to convert from UNIX to yyyy-mm-dd
		if($is_edit){
			// $field_shmu_date_land_use_changed_value is expected to be a UNIX timestamp
			$field_shmu_date_land_use_changed_value = $shmu->get('field_shmu_date_land_use_changed')[0]->value;
			$default_value_shmu_date_land_use_changed = date("Y-m-d", $field_shmu_date_land_use_changed_value);
		} else {
			$default_value_shmu_date_land_use_changed = '';
		}

		$form['field_shmu_date_land_use_changed'] = [
			'#type' => 'date',
			'#title' => $this->t('Date Land Use Changed'),
			'#description' => '',
			'#default_value' => $default_value_shmu_date_land_use_changed, // Default value for "date" field type is a string in form of 'yyyy-MM-dd'
			'#required' => FALSE
		];

		$field_shmu_current_land_use_value = $is_edit ? $shmu->get('field_shmu_current_land_use')->target_id : '';

		$form['field_shmu_current_land_use'] = [
			'#type' => 'select',
			'#title' => $this->t('Current Land Use'),
			'#options' => $land_use_options,
			'#default_value' => $field_shmu_current_land_use_value,
			'#required' => TRUE
		];

		$field_shmu_current_land_use_modifiers_value = $is_edit ? $this-> getDefaultValuesArrayFromMultivaluedSHMUField($shmu, 'field_shmu_current_land_use_modifiers') : [];

		$form['field_shmu_current_land_use_modifiers'] = [
			'#type' => 'checkboxes',
			'#title' => $this->t('Current Land Use Modifiers'),
			'#options' => $land_use_modifier_options,
			'#default_value' => $field_shmu_current_land_use_modifiers_value,
			'#required' => TRUE
		];

		// New section (Overview of the Production System)
		$form['subform_6'] = [
			'#markup' => '<div class="subform-title-container"><h2>Overview of the Production System</h2><h4>5 Fields | Section 6 of 11</h4> </div>'
		];


		$form['static']['crop_rotation_description'] = [
			'#markup' => '<div> Crop rotation </div>',
		];

		$form['static']['crop_rotation_description_sequence'] = [
			'#markup' => '<div> Overview of Crop Rotation Sequence </div>'
		];

		$form['crop_sequence'] = [
			'#prefix' => '<div id ="crop_sequence">',
			'#suffix' => '</div>',
		];
		// Get Options for Year and Crop Dropdowns
		$crop_options = $this->getCropOptions();
		$crop_options[''] = '-- Select --';

		$crop_rotation_years_options = $this->getCropRotationYearOptions();
		$crop_rotation_years_options[''] = '-- Select --';

		$month_lookup = ["J","F","M","A","M","J","J","A","S","O","N","D"];
		$month_options = $this->getYearOptions();

		$fs_crop_rotations = $form_state -> get('rotations');

		$num_crop_rotations = 1;

		if(count($fs_crop_rotations) <> 0){
			$num_crop_rotations = count($fs_crop_rotations);
		}


		$form_index = 0; // Not to be confused with rotation
		foreach($fs_crop_rotations as $fs_index => $rotation  ){

			$crop_default_value = ''; // Default value for empty Rotation
			$crop_year_default_value = ''; // Default value for empty rotation
			$crop_months_present_lookup = []; // Default value for empty rotation

			$crop_default_value = $rotation['field_shmu_crop_rotation_crop'][0]['target_id'];
			$crop_years_default_value = $rotation['field_shmu_crop_rotation_year'][0]['numerator'];
			$crop_months_present_lookup_raw = $rotation['field_shmu_crop_rotation_crop_present']; // Of type array

			foreach($crop_months_present_lookup_raw as $key=>$value){
				$crop_months_present_lookup[] = $value['numerator']; // Array of values, where val maintains 0 <= val < 12 for val in values
			}

			$form['crop_sequence'][$fs_index] = [
				'#prefix' => '<div id="crop_rotation">',
				'#suffix' => '</div>',
			];

			$form['crop_sequence'][$fs_index]['field_shmu_crop_rotation_year'] = [
				'#type' => 'select',
				'#title' => 'Year',
				'#required' => FALSE,
				'#options' => $crop_rotation_years_options,
				'#default_value' => $crop_years_default_value,
			];
			$form['crop_sequence'][$fs_index]['field_shmu_crop_rotation_crop'] = [
				'#type' => 'select',
				'#title' => 'Crop',
				'#required' => FALSE,
				'#options' => $crop_options,
				'#default_value' => $crop_default_value
			];
			$form['crop_sequence'][$fs_index]['month_wrapper'] = [
				'#prefix' => '<div id="crop_rotation_months"',
				'#suffix' => '</div>',
			];

			$form['crop_sequence'][$fs_index]['month_wrapper']['field_shmu_crop_rotation_crop_present'] = [
				'#type' => 'checkboxes',
				'#title' => '',
				'#title_display' => 'before',
				'#options' => $month_options,
				'#default_value' => $crop_months_present_lookup, // List of months present on that db
			];

			if ($fs_index <> 0) {
				$form['crop_sequence'][$fs_index]['actions']['delete'] = [
					'#type' => 'submit',
					'#name' => 'delete-crop-sequence-' . $fs_index,
					'#submit' => ['::deleteCropRotation'],
					'#ajax' => [
						'callback' => "::deleteCropRotationCallback",
						'wrapper' => 'crop_sequence',
					],
					'#limit_validation_errors' => [],
					'#value' => 'X',
				];
			}

			// Very important
			$form_index = $form_index + 1;
			// End very important
		}

		// Add another button
		$form['addCrop'] = [
			'#type' => 'submit',
			'#submit' => ['::addAnotherCropRotation'],
			'#ajax' => [
				'callback' => '::addAnotherCropRotationCallback',
				'wrapper' => 'crop_sequence',
			],
			'#limit_validation_errors' =>[],
			'#value' => 'Add to Sequence',
		];

		// New section (Cover Crop History)
		$form['subform_7'] = [
			'#markup' => '<div class="subform-title-container"> <h2> Cover Crop History </h2> <h4> 1 Field | Section 7 of 11</h4> </div>'
		];

		$field_shmu_initial_crops_planted = $is_edit ? $this-> getDefaultValuesArrayFromMultivaluedSHMUField($shmu, 'field_shmu_initial_crops_planted') : [];

		$form['field_shmu_initial_crops_planted'] = [
			'#type' => 'checkboxes',
			'#required' => TRUE,
			'#title' => 'What Crops are Currently Planted',
			'#options' => $crop_options,
			'#default_value' => $field_shmu_initial_crops_planted,
		] ;

		// New section (Tillage Type)
		$form['subform_8'] = [
			'#markup' => '<div class="subform-title-container"><h2>Tillage Type</h2><h4> 4 Fields | Section 8 of 11</h4></div>'
		];

		$field_current_tillage_system_value = $is_edit ? $shmu->get('field_current_tillage_system')->target_id : '';
		$tillage_system_options = $this->getTillageSystemOptions();
		$form['field_current_tillage_system'] = [
			'#type' => 'select',
			'#title' => $this->t('Current Tillage System'),
			'#options' => $tillage_system_options,
			'#default_value' => $field_current_tillage_system_value,
			'#required' => TRUE
		];
		$field_years_in_current_tillage_system_value = $is_edit ? $this-> getDecimalFromSHMUFractionFieldType($shmu, 'field_years_in_current_tillage_system'): '';

		$form['field_years_in_current_tillage_system'] = [
			'#type' => 'number',
			'#title' => $this->t('Years in Current Tillage System'),
			'#min_value' => 0,
			'#step' => 1, // Int
			'#description' => '',
			'#default_value' => $field_years_in_current_tillage_system_value,
			'#required' => TRUE
		];

		$field_shmu_previous_tillage_system_value = $is_edit ? $shmu->get('field_shmu_previous_tillage_system')->target_id : '';
		$form['field_shmu_previous_tillage_system'] = [
			'#type' => 'select',
			'#title' => $this->t('Previous Tillage System'),
			'#options' => $tillage_system_options,
			'#default_value' => $field_shmu_previous_tillage_system_value,
			'#required' => TRUE
		];
		$field_years_in_prev_tillage_system_value = $is_edit ? $this-> getDecimalFromSHMUFractionFieldType($shmu, 'field_years_in_prev_tillage_system'): '';

		$form['field_years_in_prev_tillage_system'] = [
			'#type' => 'number',
			'#min_value' => 0,
			'#step' => 1, // Int
			'#title' => $this->t('Years in Previous Tillage System'),
			'#description' => '',
			'#default_value' => $field_years_in_prev_tillage_system_value,
			'#required' => TRUE
		];

		$form['irrigation_radios'] = [
			'#type' => 'radios',
			'#required' => TRUE,
			'#title' => t('Is this SHMU being irrigated?'),
			'#default_value' => 'no',
			'#options' => [
				'yes' => $this->t('Yes'),
				'no' => $this->t('No')
			],
			'#attributes' => [
				'#name' => 'irrigation_radios',
			],
		];


		$form['subform_etc'] = [
			'#type' => 'item',
			'#markup' => '<p>Remember to add an irrigation sample!<p>',
			'#states' => ['visible' => [
				':input[name="irrigation_radios"]' => ['value' => 'yes'],
				],
			],
		];


		// New section (Additional Concerns or Impacts)
		$form['subform_10'] = [
			'#markup' => '<div class="subform-title-container"><h2>Additional Concerns or Impacts</h2><h4> 2 Fields | Section 10 of 11</h4></div>'
		];

		$major_resource_concerns_options = $this->getMajorResourceConcernOptions();

		$field_shmu_major_resource_concern_values = $is_edit ? $this-> getDefaultValuesArrayFromMultivaluedSHMUField($shmu, 'field_shmu_major_resource_concern') : [];

		$form['field_shmu_major_resource_concern'] = [
			'#type' => 'checkboxes',
			'#title' => $this->t('Other Major Resource Concerns'),
			'#options' => $major_resource_concerns_options,
			'#default_value' => $field_shmu_major_resource_concern_values,
			'#required' => FALSE
		];

		$field_shmu_resource_concern_values = $is_edit ? $this-> getDefaultValuesArrayFromMultivaluedSHMUField($shmu, 'field_shmu_resource_concern') : [];

		$resource_concern_options = $this->getResourceConcernOptions();
		$form['field_shmu_resource_concern'] = [
			'#type' => 'checkboxes',
			'#title' => $this->t('Other Specific Resource Concerns'),
			'#options' => $resource_concern_options,
			'#default_value' => $field_shmu_resource_concern_values,
			'#required' => FALSE
		];
		$form['subform_11'] = [
			'#markup' => '<div class="subform-title-container"><h2>NRCS Practices</h2><h4> 1 Field | Section 11 of 11</h4></div>'
		];

		$field_shmu_practices_addressed_values = $is_edit ? $this-> getDefaultValuesArrayFromMultivaluedSHMUField($shmu, 'field_shmu_practices_addressed') : [];
		$practices_addressed_options = $this->getPracticesAddressedOptions();
		$form['field_shmu_practices_addressed'] = [
			'#type' => 'checkboxes',
			'#title' => $this->t('Practices Addressed'),
			'#options' => $practices_addressed_options,
			'#default_value' => $field_shmu_practices_addressed_values,
			'#required' => TRUE
		];

		$form['actions']['send'] = [
			'#type' => 'submit',
			'#value' => 'Save',

		];

		$form['actions']['cancel'] = [
			'#type' => 'submit',
			'#value' => $this->t('Cancel'),
			'#limit_validation_errors' => '',
			'#submit' => ['::redirectAfterCancel'],
		];

		 if($is_edit){
                $form['actions']['delete'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Delete'),
                    '#submit' => ['::deleteShmu'],
                ];
            }

		return $form;

	}

	public function redirectAfterCancel(array $form, FormStateInterface $form_state){
        $form_state->setRedirect('cig_pods.dashboard');
    }

	public function deleteShmu(array &$form, FormStateInterface $form_state){
        $shmu_id = $form_state->get('shmu_id');
        $shmu = \Drupal::entityTypeManager()->getStorage('asset')->load($shmu_id);
		$crop_rotation_ids = $this->getCropRotationIdsForShmu($shmu);

        try{
			$shmu->delete();
			$form_state->setRedirect('cig_pods.dashboard');
		}catch(\Exception $e){
			$this
		  ->messenger()
		  ->addError($this
		  ->t($e->getMessage()));
		}

		foreach($crop_rotation_ids as $crop_rotation_id) {
			try{
				$crop_rotation = \Drupal::entityTypeManager()->getStorage('asset')->load($crop_rotation_id);
				$crop_rotation->delete();
			}catch(\Exception $e){
				$this
			  ->messenger()
			  ->addError($this
			  ->t($e->getMessage()));
			}
		}
    }

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state){
		return;
	}

	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'soil_health_management_unit_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {


		// We aren't interested in some of the attributes that $form_state->getValues() gives us.
		// Tracked in $ignored_fields
		$is_edit = $form_state->get('operation') == 'edit';

		$ignored_fields = ['send','form_build_id','form_token','form_id','op','actions','irrigation_radios','subform_etc','mymap','ssurgo_lookup','ssurgo_data_wrapper','addCrop'];

		$form_values = $form_state->getValues();

		// All of the fields that support multi-select checkboxes on the page
		$checkboxes_fields = ['field_shmu_prev_land_use_modifiers',
						   'field_shmu_current_land_use_modifiers',
						   'field_shmu_major_resource_concern',
						   'field_shmu_resource_concern',
							'field_shmu_practices_addressed'];
		// All of the fields that support date input on the page
		$date_fields = ['field_shmu_date_land_use_changed','field_shmu_irrigation_sample_date'];

		// Specialty crop rotation section fields
	$crop_rotation_fields = ['crop_sequence', 'crop_rotation','field_shmu_crop_rotation_crop','field_shmu_crop_rotation_year','is_present','field_shmu_crop_rotation_sequence'/*, 'field_shmu_initial_crops_planted'*/];

		$shmu = NULL;
		if(!$is_edit){
			$shmu_template = [];
			$shmu_template['type'] = 'soil_health_management_unit';
			$shmu = Asset::create($shmu_template);
		} else {
			// Operation is of type Edit
			$id = $form_state->get('shmu_id');
			$shmu = Asset::load($id);
		}
		foreach($form_values as $key => $value){
			// If it is an ignored field, skip the loop
			if(in_array($key, $ignored_fields)){ continue; }

			// These fields have special handling. Shown below
			if(in_array($key, $crop_rotation_fields)){ continue; }

			if(in_array($key, $checkboxes_fields)){
				// Value is of type array (Multi-select). Use built-in Checkbox method.
				$shmu->set($key,Checkboxes::getCheckedCheckboxes($value)); //Set directly on SHMU object
				continue;
			}
			if(in_array($key,$date_fields)){
				// $value is expected to be a string of format yyyy-mm-dd
				$shmu->set( $key, strtotime( $value ) ); //Set directly on SHMU object
				continue;
			}

			$shmu->set( $key, $value );
		}

		// Map submission logic
		$shmu->set('field_geofield',$form_values['mymap']);

    // Set map unit symbol and surface texture.
    $shmu->set('field_shmu_map_unit_symbol', $form_values['ssurgo_data_wrapper']['map_unit_symbol']);
    $shmu->set('field_shmu_surface_texture', $form_values['ssurgo_data_wrapper']['surface_texture']);

		$num_crop_rotations = count($form_values['crop_sequence']); 

		$crop_options = $this->getCropOptions();

		$crop_rotation_template = [];
		$crop_rotation_template['type'] = 'shmu_crop_rotation';

		for($rotation = 0; $rotation < $num_crop_rotations; $rotation++ ){

			// If they did not select a crop for the row, do not include it in the save
			if($form_values['crop_sequence'][$rotation]['field_shmu_crop_rotation_crop'] == NULL) continue;

			// We alwasys create a new crop rotation asset for each rotation
			$crop_rotation = Asset::create( $crop_rotation_template );

			// read the crop id from select dropdown for given rotation
			$crop_id = $form_values['crop_sequence'][$rotation]['field_shmu_crop_rotation_crop'];
			$crop_rotation->set( 'field_shmu_crop_rotation_crop', $crop_id );

			// read the crop rotation year from select dropdown for given rotation
			$crop_rotation_year = $form_values['crop_sequence'][$rotation]['field_shmu_crop_rotation_year'];
			$crop_rotation->set( 'field_shmu_crop_rotation_year', $crop_rotation_year );

			#
			$months_present  = Checkboxes::getCheckedCheckboxes($form_values['crop_sequence'][$rotation]['month_wrapper']['field_shmu_crop_rotation_crop_present']);
			$crop_rotation -> set('field_shmu_crop_rotation_crop_present', $months_present);

			$crop_rotation_name = $shmu->getName()." - Crop (".$crop_options[$crop_id].") Rotation - Year ".$crop_rotation_year;
			$crop_rotation->set('name',$crop_rotation_name);
			$crop_rotation->save();
			$crop_rotation_ids[] = $crop_rotation -> id(); // Append ID of SHMU Crop Rotation to list
		}

		$shmu->set('field_shmu_crop_rotation_sequence', $crop_rotation_ids);
		$shmu->save();

		// Cleanup - remove the old Crop Rotation Assets that are no longer used
		if($is_edit){
			$trash_rotation_ids = $form_state->get('original_crop_rotation_ids');
			foreach($trash_rotation_ids as $key => $id){
				$crop_rotation_old = Asset::load($id);
				$crop_rotation_old->delete();
			}
		}
		// Cleanup done
		$producer = \Drupal::entityTypeManager()->getStorage('asset')->load($form_state->getValue('field_shmu_involved_producer'));
		$this->setProjectReference($shmu, $producer->get('project')->target_id);

		$form_state->setRedirect('cig_pods.dashboard');
	}

	public function setProjectReference($assetReference, $projectReference){
		$project = \Drupal::entityTypeManager()->getStorage('asset')->load($projectReference);
		$assetReference->set('project', $project);
		$assetReference->save();
	}

  /**
   * Submit function for looking up soil data from SSURGO.
   */
  public function ssurgoDataLookup(array &$form, FormStateInterface $form_state) {

    // Get WKT from the map.
    $wkt = $form_state->getValue('mymap');

    // Validate the WKT.
    $valid_geometry = FALSE;
    if (!empty($wkt)) {
      $geophp = new GeoPHPWrapper();
      try {
        if ($geophp->load($wkt)) {
          $valid_geometry = TRUE;
        }
      } catch (\Exception $e) {
        $valid_geometry = FALSE;
      }
    }
    if (!$valid_geometry) {
      return;
    }

    // Query the NRCS Soil Data Access API for mapunit data.
    $mapunits = \Drupal::service('nrcs.soil_data_access')->mapunitWktQuery($wkt);

    // If map units were found...
    if (!empty($mapunits)) {

      // Extract the mapunit symbol(s) and name(s).
      $musyms = [];
      $munames = [];
      foreach ($mapunits as $mapunit) {
        $musyms[] = $mapunit['musym'];
        $munames[] = $mapunit['muname'];
      }

      // Assemble the symbol and texture inputs.
      $symbols = implode('; ', $musyms);
      $textures = implode('; ', $munames);

      // In order to replace textfield text, we must alter the raw user input and
      // trigger a form rebuild. It cannot be done simply with setValue().
      $input = $form_state->getUserInput();
      $input['ssurgo_data_wrapper']['map_unit_symbol'] = $symbols;
      $input['ssurgo_data_wrapper']['surface_texture'] = $textures;
      $form_state->setUserInput($input);
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * Ajax callback for the soil names field.
   */
  public function ssurgoDataCallback(array &$form, FormStateInterface $form_state) {
    return $form['ssurgo_data_wrapper'];
  }

	// Adds a new row to crop rotation
	public function addAnotherCropRotation(array &$form, FormStateInterface $form_state){
		$rotations = $form_state->get('rotations');
		$new_crop_rotation = [];
		$new_crop_rotation['field_shmu_crop_rotation_crop'] = array();
		$new_crop_rotation['field_shmu_crop_rotation_crop'][0]['target_id'] = '';
		$new_crop_rotation['field_shmu_crop_rotation_year'][0]['numerator'] = '';
		$new_crop_rotation['field_shmu_crop_rotation_crop_present'] = [];

		$crop_options = $this->getCropOptions();
		$rotations[] = $new_crop_rotation;
		$form_state->set('rotations', $rotations);
		$form_state->setRebuild(True);
	}

	public function addAnotherCropRotationCallback(array &$form, FormStateInterface $form_state){
		return $form['crop_sequence'];
	}

	public function deleteCropRotation(array &$form, FormStateInterface $form_state){
	    $idx_to_rm = str_replace('delete-crop-sequence-', '', $form_state->getTriggeringElement()['#name']);

		$rotations = $form_state->get('rotations');

		unset($rotations[$idx_to_rm]); // Remove the index

		$form_state->set('rotations',$rotations);

		$form_state->setRebuild(True);
	}


	public function deleteCropRotationCallback(array &$form, FormStateInterface $form_state){
		return $form['crop_sequence'];
	}
}
