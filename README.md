# CR Dirtree #

This plugin will return an HTML-formatted ordered list of all files and folders within the specified file upload destination.

## USAGE ##

	{exp:cr_dirtree fud_id='X'}

## PARAMETERS ##

### fud_id ###

The numeric id representing the file upload destination you wish to view (Example: 4). It is __required__ that you provide either a <code>fud_id</code> or a <code>fud_name</code>.

### fud_name ###

The name given for the file upload destination as found in the file upload preferences (Example: General File Uploads). It is __required__ that you provide either a <code>fud_id</code> or a <code>fud_name</code>.

### base_list_id ###

You may specify an id for the base <code>&lt;ol&gt;</code> using the <code>base_list_id</code> parameter (Example: fileList). _Default value: none_

### base_list_class ###

You may specify a class for the base <code>&lt;ol&gt;</code> using the <code>base_list_class</code> parameter (Example: fileList). _Default value: none_

### site_id ###

You may specify a <code>site_id</code> if you are utilizing the Multi-site Manager (MSM) (Example: 3). _Default value: 1_