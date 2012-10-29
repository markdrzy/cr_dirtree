<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine DirTree Plugin
 *
 * @package		DirTree Plugin
 * @subpackage	Plugins
 * @category	Plugins
 * @author		Mark Drzycimski
 * @link		http://https://github.com/mark-cr
 */

$plugin_info = array(
	'pi_name' => 'CR Dirtree',
	'pi_version' => '0.9',
	'pi_author' => 'Mark Drzycimski',
	'pi_author_url' => 'http://github.com/mark-cr',
	'pi_description' => 'Return an ordered list representing an ExpressionEngine File Upload Directory.',
	'pi_usage' => Cr_dirtree::usage());

class Cr_dirtree {

	public $return_data = '';

	public function __construct()
	{
		// Instantiate EE
		$ee =& get_instance();
	
	
	
		// Get plugin parameters
		$fud_id				= ( $ee->TMPL->fetch_param('fud_id') )? $ee->TMPL->fetch_param('fud_id'): FALSE;
		$fud_name			= ( $ee->TMPL->fetch_param('fud_name') )? $ee->TMPL->fetch_param('fud_name'): FALSE;
		$base_list_id		= ( $ee->TMPL->fetch_param('base_list_id') )? $ee->TMPL->fetch_param('base_list_id'): '';
		$base_list_class	= ( $ee->TMPL->fetch_param('base_list_class') )? $ee->TMPL->fetch_param('base_list_class'): '';
		$site_id			= ( $ee->TMPL->fetch_param('site_id') )? $ee->TMPL->fetch_param('site_id'): 1;
	
	
	
		// Grab File Upload Destination Info
	
		// By FUD ID
		if ( isset($fud_id) && $fud_id ) $fud_q = $ee->db->get_where('upload_prefs',array('id'=>$fud_id,'site_id'=>$site_id));
	
		// By FUD Name
		if ( isset($fud_name) && $fud_name ) $fud_q = $ee->db->get_where('upload_prefs',array('Name'=>$fud_name,'site_id'=>$site_id));
	
		// No FUD identification provided? DIE!
		if ( ! isset($fud_id) && ! isset($fud_name) ) return '<p>No FUD identifier provided.</p>';
	
		// See if we found a FUD
		if ( $fud_q->num_rows() > 0 )
		{
	
			// Found 'im. Here's your FUD info
			$fud_info = $fud_q->row_array();
			$document_root = $_SERVER['DOCUMENT_ROOT'];
			$full_path = $fud_info['server_path'];
			$relative_path = str_replace($document_root,'',$fud_info['server_path']);
	
		} else {
	
			// No FUD by that id / name? DIE!
			return '<p>No FUD found with that ID ('.$fud_id.').</p><pre>'.print_r($ee->db->get_where('upload_prefs',array('id'=>$fud_id,'site_id'=>$site_id)),TRUE).'</pre>';
	
		}
	
	
	
		// Grab Assets Info
		
		// Check to see if Assets table exists.
		if ( $ee->db->query('SHOW TABLES LIKE \''.$ee->db->dbprefix.'assets\';')->num_rows() == 1 )
		{
			$asset_data_q = $ee->db->get('assets');
			if ( $asset_data_q->num_rows() > 0 )
			{
				$asset_data = array();
				foreach ($asset_data_q->result_array() as $r)
				{
					$asset_data[str_replace('{filedir_'.$fud_id.'}',$relative_path,$r['file_path'])] = $r;
				}
			}
			// Define Assets fields / columns that should be ignored and not output
			$assets_ignored_fields = array(
				'asset_id',
				'file_path'
				);
		}
	
		// Initialize Directory Object
		$objects = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$full_path,
				FilesystemIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::SELF_FIRST
		);
	
		// DOM Boilerplate
		$dom = new DomDocument('1.0');
	
		// Begin writing DOM elements
		$list = $dom->createElement('ol');
		$dom->appendChild($list);
		$node = $list;
		
		// Add ID attribute, if provided
		if ( $base_list_id != '' ) $node->setAttribute('id',$base_list_id);
		
		// Add CLASS attribute, if provided
		if ( $base_list_class != '' ) $node->setAttribute('class',$base_list_class);
		
		// Set initial depth
		$depth = 0;
	
	
	
		// Iterate Recursive Directory Listing Object
		foreach( $objects as $name => $object )
		{
	
			// Create Elements
			switch ( $object->isDir() )
			{
				case TRUE:
					// This object is a directory
					$li = $dom->createElement('li', str_replace('_',' ',$object->getFilename()));
					$li->setAttribute('class','dir');
					break;
	
				case FALSE:
					// This object is a file
					$li = $dom->createElement('li');
					$li->setAttribute('class','file '.array_pop(explode('.',$object->getFilename())));
					$a = $dom->createElement('a', str_replace('_',' ',$object->getFilename()));
					$file_url = str_replace($document_root,'',$object->getPathname());
					$a->setAttribute('href',$file_url);
					$li->appendChild($a);
					
					// Add Assets data, if available
					if ( isset($asset_data[$file_url]) && is_array($asset_data[$file_url]) && ! empty($asset_data[$file_url]) ) {
						$info_div = $dom->createElement('div');
						$info_div->setAttribute('class','file-asset-info');
						foreach ( $asset_data[$file_url] as $k => $v )
						{
							if ( ! in_array($k,$assets_ignored_fields) && $v != '' )
							{
								$span = $dom->createElement('span',$v);
								$span->setAttribute('class',$k);
								$info_div->appendChild($span);
							}
						}
						$li->appendChild($info_div);
					}
					
					break;
			}
	
			if ( $objects->getDepth() < $depth )
			{
				// Depth has decreased, shift $node to appropriate parent
				$difference = $depth - $objects->getDepth();
				for ($i = 0; $i < $difference; $difference--){
					$node = $node->parentNode->parentNode;
				}
			}
	
			if ( $objects->getDepth() > $depth )
			{
				// Depth has increased, move node to new level
				$parent_li = $node->lastChild;
				$ol = $dom->createElement('ol');
				$parent_li->appendChild($ol);
				$node = $ol;
			}
	
			// Add the element to the node
			$node->appendChild($li);
	
			// Sounding the deeps
			$depth = $objects->getDepth();
	
		}
	
	
	
		// Output DOM as HTML
		$dom->formatOutput = TRUE;
		return $this->return_data = $dom->saveHtml();
	
	}



	/**
	 * Usage
	 *
	 * This function describes how the plugin is used.
	 *
	 * @access  public
	 * @return  string
	 */
	public static function usage()
	{
		ob_start(); ?>

# CR Dirtree #

This plugin will return an HTML-formatted ordered list of all files and folders within the specified file upload destination.

## USAGE ##

	{exp:cr_dirtree fud_id='X'}

## PARAMETERS ##

### fud_id ###

The numeric id representing the file upload destination you wish to view (Example: 4). It is __required__ that you provide either an _fud_id_ or an _fud_name_.

### fud_name ###

The name given for the file upload destination as found in the file upload preferences (Example: General File Uploads). It is __required__ that you provide either an _fud_id_ or an _fud_name_.

### base_list_id ###

You may specify an id for the base <pre><ol></pre> using the _base_list_id_ parameter (Example: fileList). _Default value: none_

### base_list_class ###

You may specify a class for the base <pre><ol></pre> using the _base_list_class_ parameter (Example: fileList). _Default value: none_

### site_id ###

You may specify a _site_id_ if you are utilizing the Multi-site Manager (MSM) (Example: 3). _Default value: 1_

	<?php
		$buffer = ob_get_contents();
		ob_end_clean();

		return $buffer;
	}
	// END

}
/* End of file pi.cr_dirtree.php */
/* Location: ./system/expressionengine/third_party/cr_dirtree/pi.cr_dirtree.php */
