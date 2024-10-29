<?php
/*
 * Plugin Name:		All custom fields & groups
 * Description:		Output all custom fields of groups from "Advanced Custom Fields", Pods or etc...
 * Text Domain:		all-custom-fields-groups
 * Domain Path:		/languages
 * Version:		1.08
 * WordPress URI:	https://wordpress.org/plugins/all-custom-fields-groups/
 * Plugin URI:		https://puvox.software/software/wordpress-plugins/?plugin=all-custom-fields-groups
 * Contributors: 	puvoxsoftware,ttodua
 * Author:		Puvox.software
 * Author URI:		https://puvox.software/
 * Donate Link:		https://paypal.me/Puvox
 * License:		GPL-3.0
 * License URI:		https://www.gnu.org/licenses/gpl-3.0.html
 
 * @copyright:		Puvox.software
*/


namespace AllCustomFieldsGroups
{
  if (!defined('ABSPATH')) exit;
  require_once( __DIR__."/library.php" );
  require_once( __DIR__."/library_wp.php" );
  
  class PluginClass extends \Puvox\wp_plugin
  {

	public function declare_settings()
	{
		$this->initial_static_options	= 
		[
			'has_pro_version'        => 0, 
            'show_opts'              => true, 
            'show_rating_message'    => true, 
            'show_donation_footer'   => true, 
            'show_donation_popup'    => true, 
            'menu_pages'             => [
                'first' =>[
                    'title'           => 'All custom fields & groups', 
                    'default_managed' => 'network',            // network | singlesite
                    'required_role'   => 'install_plugins',
                    'level'           => 'submenu', 
                    'page_title'      => 'All custom fields & groups',
                    'tabs'            => [],
                ],
            ]
		];

		$this->initial_user_options		=
		[
			'enable_shortcodes_in_acf_fields'=> false
		];

		$this->shortcodes	= [
			$this->shortcode_name1 =>[
				'description'=>__('Output the all custom fields from groups (from "Advanced Custom Fields", "Pods" or etc.)', 'all-custom-fields-groups'),
				'atts'=>[ 
					[ 'acf_group', 			'i.e: 123 or i.e: Title Of Group',		__('Enter either ID of "Advanced Custom Fields" group (you can find the ID in the url of ACF group-edit page) or title of the group', 'all-custom-fields-groups') ],
					[ 'show_empty_fields', 	'true',				__('Shows/hides the fields that were empty for the post', 'all-custom-fields-groups') ],
					[ 'excluded_fields',	'field1,field2',  	__('Enter keyNames of fields which you want to exclude from output of the group fields', 'all-custom-fields-groups') ],
					[ 'separator',			',<br/>', 			__('In case of multi-option output (like multiple checkboxes or etc...) how the values should be separated from each other. Use <code>'.$this->newlinePhrase.'</code> to express the new-line character.', 'all-custom-fields-groups') ],
					[ 'clearfix',			true, 				__('Include newline separator <code>&lt;div style="clear:both;"&gt;&lt;/div&gt;</code> before&after the output.', 'all-custom-fields-groups') ]
				]
			] 
		];

	}

	public $shortcode_name1 = 'custom_fields_groups';
	public $newlinePhrase = 'NEWLINE';

	public function __construct_my()
	{
		//$this->helpers->register_stylescript('wp', 'style', 'all-custom-fields-groups-styles', 'assets/style.css');

		if ($this->opts['enable_shortcodes_in_acf_fields'])
		{
			// https://www.advancedcustomfields.com/resources/acf-format_value/
			add_filter('acf/format_value/type=textarea',	'do_shortcode');
			add_filter('acf/format_value/type=text',		'do_shortcode');
		}

	}


	// ============================================================================================================== //
	// ============================================================================================================== //
 
	public function custom_fields_groups($atts, $content=false)
	{
		$args = $this->helpers->shortcode_atts($this->shortcode_name1, $this->shortcodes[$this->shortcode_name1]['atts'], $atts);
		return $this->shortcode_wrapper($args, $content=false);
	}

 


	// https://www.advancedcustomfields.com/resources/get_fields/
	// http://www.advancedcustomfields.com/resources/get_field_objects/
	
	public function shortcode_wrapper( $atts , $content=false)
	{ 
		if (!array_key_exists('acf_group', $atts)) 
			return __('"<b>acf_group</b>" attribute not found', 'all-custom-fields-groups');
		
		$acf_group = $atts['acf_group'];

		if (is_numeric($acf_group)) {
			$acf_postgroup_id = $acf_group;
		}
		else{
			$post = get_page_by_title( $acf_group, OBJECT, 'acf-field-group' );
			if (empty($post))
			{
				return __('<b>acf_group</b> with title <code>'.sanitize_title($acf_group).'</code> was not found', 'all-custom-fields-groups');
			}
			$acf_postgroup_id = $post->ID;
		}
		
		$excluded_fields = array_key_exists('excluded_fields', $atts) ? explode(',', $atts['excluded_fields']) : [];
		
		$fields = acf_get_fields($acf_postgroup_id);
		
		$out ='';
		$separator = str_replace($this->newlinePhrase, "\n", $atts['separator']);
		if( $fields )
		{
			foreach( $fields as $field )
			{
				if (in_array($field['key'], $excluded_fields))
					continue;
				
				$value = get_field( $field['name'] );
				if(!empty($value) || $atts['show_empty_fields'] ) 
				{
					$valueContent = '';
					if (!is_array($value)) {
						$valueContent=$value;
					}
					else
					{
						foreach($value as $key2=>$value2)
						{
							$valueContent .= $value2 . $separator;
						}
					}
					$out .= 
					'<tr> 
						<td class="label">'. $field['label']. ':</td> 
						<td class="value">'. $valueContent .'</td>
					</tr>';
				} 
			}
		}
		
		if (!empty($out))
		{
			$out = '<table class="acf_fieldsgroup">'.$out.'</table>';
			$out .= '<style>.acf_fieldsgroup {} </style>';
		}

		return apply_filters( 'custom_fields_groups', $out, $atts );
	} 





	// =================================== Options page ================================ //
	public function opts_page_output()
	{ 
		$this->settings_page_part("start", "first");
		?> 

		<style>
		p.submit { text-align:center; }
		.settingsTitle{display:none;}
		.myplugin {padding:10px;}
		zzz#mainsubmit-button{display:none;}
		</style>
		
		<?php if ($this->active_tab=="Options") 
		{
			//if form updated
			if( $this->checkSubmission() ) 
			{
				$this->opts['enable_shortcodes_in_acf_fields']	= !empty($_POST[ $this->plugin_slug ]['enable_shortcodes_in_acf_fields']);
				//$this->opts['bs_shortcode_default']		= stripslashes( sanitize_text_field($_POST[ $this->plugin_slug ]['bs_shortcode_default']) );
				$this->update_opts(); 
			}
			?> 

			<form class="mainForm" method="post" action="">

			<table class="form-table">
				<tbody>
				<tr class="def">
					<th scope="row">
						<label for="bs_in_content">
							<?php _e('Extra option: Permit shortcodes to be used in Advanced Custom Fields ', 'all-custom-fields-groups');?>
						</label>
					</th>
					<td>
						<p class="description"><?php _e('(by default, it\'s not enabled and is not secure if you have other registered users and if they can be used by them)', 'all-custom-fields-groups');?></p>
						<input id="enable_shortcodes_in_acf_fields" name="<?php echo $this->plugin_slug;?>[enable_shortcodes_in_acf_fields]" type="checkbox" value="1" <?php checked($this->opts['enable_shortcodes_in_acf_fields']); ?>  /> 
					</td>
				</tr>
				</tbody>
			</table>

			<?php $this->nonceSubmit(); ?>

			</form>
		<?php 
		} 
		
		
		$this->settings_page_part("end", "");
	} 



  } // End Of Class

  $GLOBALS[__NAMESPACE__] = new PluginClass();

} // End Of NameSpace

?>