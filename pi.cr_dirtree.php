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

function dirtree($fup_id,$base_list_id='',$base_list_class='',$site_id=1)
{
	// Instantiate EE
	$ee =& get_instance(); // Do we need to do this in a plugin?



	// Grab File Upload Location Info

	// By FUP ID
	if ( is_int($fup_id) ) $fup_q = $ee->db->get_where('upload_prefs',array('id'=>$fup_id,'site_id'=>$site_id));

	// By FUP Name
	if ( is_string($fup_id) ) $fup_q = $ee->db->get_where('upload_prefs',array('Name'=>$fup_id,'site_id'=>$site_id));

	// By neither? DIE!
	if ( ! is_int($fup_id) && ! is_string($fup_id) ) return '';

	// See if we found a FUP
	if ( $fup_q->num_rows() > 0 )
	{

		// Found 'im. Here's your FUP info
		$fup_info = $fup_q->row_array();
		$document_root = $_SERVER['DOCUMENT_ROOT'];
		$full_path = $fup_info['server_path'];
		$relative_path = str_replace($document_root,'',$fup_info['server_path']);

	} else {

		// No FUP by that id / name? DIE!
		return '';

	}



	// Grab Assets Info
	
	// Check to see if Assets table exists.
	$asset_data_q = $ee->db->get('assets');
	if ( $asset_data_q->num_rows() > 0 )
	{
		$asset_data = array();
		foreach ($asset_data_q->result_array() as $r)
		{
			$asset_data[str_replace('{filedir_'.$fup_id.'}',$relative_path,$r['file_path'])] = $r;
		}
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
	if ( $base_list_id != '' )
	{
		$list_id = $dom->createAttribute('id');
		$list_id->value = $base_list_id;
		$node->appendChild($list_id);
	}
	if ( $base_list_class != '' )
	{
		$list_class = $dom->createAttribute('class');
		$list_class->value = $base_list_class;
		$node->appendChild($list_class);
	}
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
				$class = $dom->createAttribute('class');
				$class->value = 'dir';
				$li->appendChild($class);
				break;

			case FALSE:
				// This object is a file
				$li = $dom->createElement('li');
				$li_class = $dom->createAttribute('class');
				$li_class->value = 'file '.array_pop(explode('.',$object->getFilename()));
				$a = $dom->createElement('a', str_replace('_',' ',$object->getFilename()));
				$a_href = $dom->createAttribute('href');
				$a_href->value = str_replace($document_root,'',$object->getPathname());
				$a->appendChild($a_href);
				$li->appendChild($li_class);
				$li->appendChild($a);
				if ( isset($asset_data[$a_href->value]['desc']) ) {
					$span = $dom->createElement('span',$asset_data[$a_href->value]['desc']);
					$span_class = $dom->createAttribute('class');
					$span_class->value = 'desc';
					$span->appendChild($span_class);
					$li->appendChild($span);
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
	return $dom->saveHtml();

}

public function usage()
{
	// USAGE
	// =====
	// Arguments: File Upload Destination ID (or Name), List ID, List Class, Site ID
	// echo ee_directory_to_html(4,'file-list-id','file-list-class',1);
}


?>