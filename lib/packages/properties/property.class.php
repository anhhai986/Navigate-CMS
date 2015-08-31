<?php
require_once(NAVIGATE_PATH.'/lib/packages/webdictionary/webdictionary.class.php');
require_once(NAVIGATE_PATH.'/lib/packages/templates/template.class.php');

class property
{
	public $id;
	public $website;
	public $element;
	public $template;
	public $name;
	public $type;
	public $options;
	public $dvalue;	// default value
    public $multilanguage; // "true", "false" or empty
    public $helper;
	public $position;
	public $enabled;
	
		// value
		// option
		// multiple option
		// boolean
		// text (multilanguage)
		// textarea (default multilanguage)
		// Date
		// Date & Time
		// Link (default multilanguage)
		// Image (optional multilanguage)
		// File
        // Color
		// Comment
		// Rating
		// Country
		// Coordinates
        // Video
        // Source code (optional multilanguage)
        // Rich text area (default multilanguage)
        // Web user groups
        // Category (Structure entry)
        // Categories (Multiple structure entries)
		// Product (not yet!)
	
	public function load($id)
	{
		global $DB;
		global $website;
		
		if($DB->query('SELECT * FROM nv_properties 
						WHERE id = '.intval($id).'
						  AND website = '.$website->id))
		{
			$data = $DB->result();
			$this->load_from_resultset($data); // there will be as many entries as languages enabled
		}
	}
	
	public function load_from_resultset($rs)
	{
		$main = $rs[0];

		$this->id			= $main->id;
		$this->element 		= $main->element;
		$this->template		= $main->template;		
		$this->name			= $main->name;
		$this->type			= $main->type;
		$this->options		= mb_unserialize($main->options);
		$this->dvalue		= $main->dvalue;		
		$this->multilanguage= $main->multilanguage;
		$this->helper       = $main->helper;
		$this->position		= $main->position;
		$this->enabled		= $main->enabled;	
		
		if($this->type == 'date')
			$this->dvalue = core_ts2date($this->dvalue, false);		
		else if($this->type == 'datetime')
			$this->dvalue	= 	core_ts2date($this->dvalue, true);				
	}
	
	public function load_from_post()
	{
		$this->element  	= $_REQUEST['property-element'];
		$this->template  	= $_REQUEST['property-template'];		
		$this->name			= $_REQUEST['property-name'];
		$this->type			= $_REQUEST['property-type'];
		$this->dvalue		= $_REQUEST['property-dvalue'];
        $this->multilanguage= ($_REQUEST['property-multilanguage']=='1'? 'true' : '');
        $this->helper       = $_REQUEST['property-helper'];

		if($this->type == 'date' || $this->type == 'datetime')
			$this->dvalue	= 	core_date2ts($this->dvalue);

        if(empty($this->type))
            $this->type = 'value';

        if(empty($this->element) || $this->element == 'element')
            $this->element = 'item';
		
		if(isset($_REQUEST['property-position']))
			$this->position		= $_REQUEST['property-position'];
		$this->enabled		= intval($_REQUEST['property-enabled']);	
		
		// parse property options
		$this->options = array();
		
		$options = $_REQUEST['property-options'];
		$options = explode("\n", $options);
		
		foreach($options as $option)
		{
			$option = explode('#', $option, 2);
			if(empty($option[1])) continue;
			$this->options[trim($option[0])] = trim($option[1]);
		}		
	}

    public function load_from_theme($theme_option, $value=null, $source='website', $template='')
    {
        global $website;
        global $theme;

        if(is_string($theme_option))
        {
            // theme_option as ID, not object
            if($source=='website')
            {
                if(empty($theme->options))
                    $theme->options = array();

                foreach($theme->options as $to)
                {
                    if($to->id==$theme_option || $to->name==$theme_option)
                    {
                        $theme_option = $to;
                        @$theme_option->element = 'website';
                        break;
                    }
                }
            }
            else if($source=='template')
            {
                if(empty($theme->templates))
                    $theme->templates = array();

                foreach($theme->templates as $tt)
                {
                    if($tt->type != $template)
                        continue;

                    if(empty($tt->properties))
                        $tt->properties = array();

                    foreach($tt->properties as $tp)
                    {
                        if($tp->id==$theme_option)
                        {
                            $theme_option = $tp;
                            break;
                        }
                    }
                }

                if(empty($theme_option->element) || $theme_option->element == 'element')
                    $theme_option->element = 'item';
            }
        }

        $this->id = $theme_option->id;
        $this->website = $website->id;
       	$this->element = $theme_option->element;
       	$this->template = '';
       	$this->name = $theme_option->name;
       	$this->type = $theme_option->type;
       	$this->options = (array)$theme_option->options;
       	$this->dvalue = $theme_option->dvalue;	// default value
       	$this->multilanguage = $theme_option->multilanguage;
       	$this->helper = $theme_option->helper;
       	$this->position = 0;
       	$this->enabled = 1;

        if(substr($this->name, 0, 1)=='@')  // get translation from theme dictionary
            $this->name = $theme->t(substr($this->name, 1));

        if(substr($this->helper, 0, 1)=='@')
            $this->helper = $theme->t(substr($this->helper, 1));

        $this->value = $value;

        if(!isset($value) && isset($website->theme_options->{$this->id}))
            $this->value = $website->theme_options->{$this->id};

        if(empty($this->value) && empty($this->id))
            $this->value = $this->dvalue;

        if(is_object($this->value))
            $this->value = (array)$this->value;
    }

    public function load_from_object($object, $value=null, $dictionary=null)
    {
        global $website;

        $this->id = $object->id;
        $this->website = $website->id;
       	$this->element = $object->element;
       	$this->template = '';
       	$this->name = $object->name;
       	$this->type = $object->type;
       	$this->options = (array)$object->options;
       	$this->dvalue = $object->dvalue;	// default value
       	$this->multilanguage = $object->multilanguage;
       	$this->helper = $object->helper;
       	$this->position = 0;
       	$this->enabled = 1;

        if(!empty($dictionary))
            $this->name = $dictionary->t($this->name);

        $this->value = $value;

        if(empty($this->value) && empty($this->id))
            $this->value = $this->dvalue;

        if(is_object($this->value))
            $this->value = (array)$this->value;

        // translate option titles (when property type = option)
        if(!empty($this->options))
        {
            $options = array();
            foreach($this->options as $key => $value)
            {
                if(!empty($dictionary))
                    $value = $dictionary->t($value);

                $options[$key] = $value;
            }

            $this->options = json_decode(json_encode($options));
        }
    }
	
	public function save()
	{
		global $DB;

		if(!empty($this->id))
			return $this->update();
		else
			return $this->insert();			
	}
	
	public function delete()
	{
		global $DB;
		global $website;

		// remove all old entries
		if(!empty($this->id))
		{
			$DB->execute('DELETE FROM nv_properties
								WHERE id = '.intval($this->id).'
								  AND website = '.$website->id
						);
		}
		
		return $DB->get_affected_rows();		
	}
	
	public function insert()
	{
		global $DB;
		global $website;
				
		$ok = $DB->execute('
          INSERT INTO nv_properties
		    (id, website, element, template, name, type,
			    options, dvalue, multilanguage, helper, position, enabled)
            VALUES
            ( 0,
              :website,
              :element,
              :template,
              :name,
              :type,
              :options,
              :dvalue,
              :multilanguage,
              :helper,
              :position,
              :enabled
            )',
          array(
            ':website' => $website->id,
            ':element' => $this->element,
            ':template' => $this->template,
            ':name' => $this->name,
            ':type' => $this->type,
            ':options' => serialize($this->options),
            ':dvalue' => $this->dvalue,
            ':multilanguage' => $this->multilanguage,
            ':helper' => $this->helper,
            ':position' => intval($this->position),
            ':enabled' => $this->enabled
          )
        );
			
		if(!$ok) throw new Exception($DB->get_last_error());
		
		$this->id = $DB->get_last_id();
		
		return true;
	}
	
	public function update()
	{
		global $DB;
		global $website;
			
		$ok = $DB->execute('
		    UPDATE nv_properties
                SET
                    element	= :element,
                    template = :template,
                    name = :name,
                    type = :type,
                    options = :options,
                    dvalue = :dvalue,
                    multilanguage = :multilanguage,
                    helper = :helper,
                    position = :position,
                    enabled = :enabled
            WHERE id = :id
              AND website = :website',
            array(
                ':element' => $this->element,
                ':template' => $this->template,
                ':name' => $this->name,
                ':type' => $this->type,
                ':options' => serialize($this->options),
                ':dvalue' => $this->dvalue,
                ':multilanguage' => $this->multilanguage,
                ':helper' => $this->helper,
                ':position' => intval($this->position),
                ':enabled' => $this->enabled,
                ':id' => $this->id,
                ':website' => $website->id
            )
        );
		
		if(!$ok) throw new Exception($DB->get_last_error());
		
		return true;
	}	
		
	public static function elements($template, $element="")
	{
		global $DB;
		global $website;
        global $theme;

        $data = array();

        if(is_numeric($template))
        {
            // properties attached to a custom template (not a theme template)
            if(!empty($element))
                $element = ' AND element = '.protect($element);
            else
                $element = ' AND element != "block"';

            if($DB->query('SELECT *
                           FROM nv_properties
                           WHERE template = '.protect($template).'
                           '.$element.'
                             AND website = '.$website->id.'
                           ORDER BY position ASC, id ASC'))
            {
                $data = $DB->result();
            }
        }
        else
        {
            if($element == 'block')
            {
                // block type properties
                for($b=0; $b < count($theme->blocks); $b++)
                {
                    if($theme->blocks[$b]->id == $template)
                    {
                        $data = $theme->blocks[$b]->properties;
                        break;
                    }
                }
            }
            else if($element == 'block_group_block')
            {
                // block group blocks properties
                for($b=0; $b < count($theme->block_groups); $b++)
                {
                    if($theme->block_groups[$b]->id == $template)
                    {
                        $data = array();
                        foreach($theme->block_groups[$b]->blocks as $bgb)
                        {
                            // note: properties in block group blocks can't have the same name
                            if(isset($bgb->properties))
                                $data = array_merge($data, $bgb->properties);
                        }
                        break;
                    }
                }
            }
            else
            {
                // properties of a theme template
                $theme_template = new template();
                $theme_template->load_from_theme($template);

                $template_properties = $theme_template->properties;

                if(empty($template_properties))
                    $template_properties = array();

                $data = array();

                for($p=0; $p < count($template_properties); $p++)
                {
                    // if we want all properties, no matter the element assigned or
                    // if the property is not assigned to an element, we assume "item", or
                    // if the property is assigned to an element, we check it
                    // note: in this case, "element" is an alias of "item"
                    if( empty($element) ||
                        ($element == 'item' && empty($template_properties[$p]->element)) ||
                        ($element == 'item' && $template_properties[$p]->element=="element") ||
                        $template_properties[$p]->element == $element
                    )
                        $data[] = $template_properties[$p];
                }
            }
        }

		return $data;
	}
	
	public static function types()
	{
		$types = array(
            'value'			=>	t(193, 'Value'),
            'boolean'		=>  t(206, 'Boolean'),
            'option' 		=>	t(194, 'Option'),
            'moption' 		=>	t(211, 'Multiple option'),
            'text'			=>	t(54, 'Text'),
            'textarea'		=>	t(195, 'Textarea'),
            'rich_textarea'	=>	t(488, 'Rich textarea'),
            'date'			=>	t(86, 'Date'),
            'datetime'		=>	t(196, 'Date & time'),
            'link'			=>	t(197, 'Link'),
            'image'			=>	t(157, 'Image'),
            'file'			=>	t(82, 'File'),
            'video'			=>	t(272, 'Video'),
            'color' 		=>  t(441, 'Color'),
            'comment'		=>  t(205, 'Comment'),
            'rating'		=>	t(222, 'Rating'),
            'country'		=>	t(224, 'Country'),
            'coordinates'	=>	t(297, 'Coordinates'),
            'product'		=>	t(198, 'Product'),
            'category'		=>	t(78, 'Category'),
            'categories'	=>	t(330, 'Categories'),
            'item'		    =>	t(180, 'Item'),
            'source_code'   =>  t(489, 'Source code'),
            'webuser_groups'=>  t(512, 'Selected web user groups')
        );
						
		return $types;		
	}

	public static function reorder($element, $template, $order, $enableds=NULL)
	{
		global $DB;
		global $website;
		
		$item = explode("#", $order);
							
		for($i=0; $i < count($item); $i++)
		{		
			if(empty($item[$i])) continue;

			$enabled = '';			
			if(is_array($enableds))
			{
				$enabled = ', enabled = 0 ';
				for($e=0; $e < count($enableds); $e++)
				{
					if($enableds[$e]==$item[$i]) $enabled = ', enabled = 1 ';
				}
			}
			
			$ok =	$DB->execute('UPDATE nv_properties
									 SET position = '.($i+1).' '.$enabled.' 
								   WHERE id = '.$item[$i].'
								     AND website = '.$website->id);
			
			if(!$ok) return array("error" => $DB->get_last_error()); 
		}
			
		return true;	
	}	
	
	public static function load_properties_associative($element, $template, $item_type, $item_id)
	{
		// maybe we have cache of the current website?
		global $properties;
		
		if(isset($properties[$item_type.'-'.$item_id]))
			$props = $properties[$item_type.'-'.$item_id];
		else
			$props = property::load_properties($element, $template, $item_type, $item_id);

		// now create the associative array by property name => value
		$associative_properties = array();
		
		if(!is_array($props)) $props = array();
		foreach($props as $property)
		{
            if(is_numeric($property->id))
                $associative_properties[$property->name] = $property->value;
            else
                $associative_properties[$property->id] = $property->value;
		}

		return $associative_properties;
	}	
	
	public static function load_properties($element, $template, $item_type, $item_id)
	{
		global $DB;
		global $website;
        global $theme;

        if($item_type == 'block_group_block')
        {
            $block = block::block_group_block($template, $element);
            $e_properties = $block->properties;

            // we must find the block group ID to search the assigned property values
            if(!empty($template))
            {
                $block_group_id = $DB->query_single('MAX(id)', 'nv_block_groups', ' code = '.protect($template).' AND website = '.$website->id);
                $item_id = $block_group_id;
                if(empty($block_group_id))
                    $item_id = 0;
            }
        }
        else
        {
		    // load properties associated with the element type
		    $e_properties = property::elements($template, $item_type);
        }

		// load multilanguage strings
		$dictionary = webdictionary::load_element_strings('property-'.$item_type, $item_id);
		
		// load custom properties values
		$DB->query('
		    SELECT * FROM nv_properties_items
 			 WHERE element = '.protect($item_type).'
			   AND node_id = '.protect($item_id).'
			   AND website = '.$website->id,
            'array'
        );
			
		$values = $DB->result();

		if(!is_array($values))
            $values = array();

        $o_properties = array();

        if(!is_array($e_properties))
            $e_properties = array();

        $p = 0;
		foreach($e_properties as $e_property)
		{
            if(is_object($e_property))
                $o_properties[$p] = clone $e_property;
            else
                $o_properties[$p] = $e_property;

            if(isset($o_properties[$p]->dvalue))
                $o_properties[$p]->value = $o_properties[$p]->dvalue;

			foreach($values as $value)
			{
    			if($value['property_id'] == $o_properties[$p]->id)
				{
    				$o_properties[$p]->value = $value['value'];

					if($value['value']=='[dictionary]')
					{
						$o_properties[$p]->value = array();
						foreach($website->languages_list as $lang)
						{
							$o_properties[$p]->value[$lang] = $dictionary[$lang]['property-'.$o_properties[$p]->id.'-'.$lang];
						}
					}
				}
			}

            if(substr($o_properties[$p]->name, 0, 1)=='@')  // get translation from theme dictionary
                $o_properties[$p]->name = $theme->t(substr($o_properties[$p]->name, 1));

            if(is_object($o_properties[$p]->value))
                $o_properties[$p]->value = (array)$o_properties[$p]->value;

            $p++;
		}

		return $o_properties;
	}

    // called when using navigate cms
	public static function save_properties_from_post($item_type, $item_id, $template=null, $element=null)
	{
		global $DB;
		global $website;
		
		$dictionary = array();

		// load properties associated with the element type
        if($item_type=='block_group_block')
        {
            $block = block::block_group_block($template, $element);
            $properties = $block->properties;

            if(!is_numeric($item_id))
            {
                $block_group_id = $DB->query_single('MAX(id)', 'nv_block_groups', ' code = '.protect($template).' AND website = '.$website->id);
                $item_id = $block_group_id;
                if(empty($block_group_id))
                    $item_id = 0;
            }
        }
        else
        {
            if(empty($template)) $template = $_REQUEST['property-template'];
            if(empty($element)) $element = $_REQUEST['property-element'];
            $properties = property::elements($template, $element);
        }

        if(!is_array($properties))
            $properties = array();

		foreach($properties as $property)
		{
			/* we ALWAYS SAVE the property value, even if it is empty
			$property_empty = empty($_REQUEST['property-'.$property->id]);
			if($property_empty) // maybe is a multilanguage property?
			{
				foreach($website->languages_list as $lang)
					$property_empty = $property_empty && empty($_REQUEST['property-'.$property->id.'-'.$lang]);
			}
*/			// has value? (direct or multilanguage)
//			if(!$property_empty)
//			{		
				// multilanguage property?
				if(in_array($property->type, array('text', 'textarea', 'link', 'rich_textarea')) || @$property->multilanguage=='true')
					$_REQUEST['property-'.$property->id] = '[dictionary]';
				
				// date/datetime property?
				if($property->type=='date' || $property->type=='datetime')
					$_REQUEST['property-'.$property->id] = core_date2ts($_REQUEST['property-'.$property->id]);
					
				if($property->type=='moption' && !empty($_REQUEST['property-'.$property->id]))
					$_REQUEST['property-'.$property->id] = implode(',', $_REQUEST['property-'.$property->id]);		
				
				if($property->type=='coordinates')
					$_REQUEST['property-'.$property->id] = $_REQUEST['property-'.$property->id.'-latitude'].'#'.$_REQUEST['property-'.$property->id.'-longitude'];

                if($property->type=='webuser_groups' && !empty($_REQUEST['property-'.$property->id]))
                    $_REQUEST['property-'.$property->id] = 'g'.implode(',g', $_REQUEST['property-'.$property->id]);

                // boolean (checkbox): if not checked,  form does not send the value
                if($property->type=='boolean' && !isset($_REQUEST['property-'.$property->id]))
                    $_REQUEST['property-'.$property->id] = 0;
								
				// remove the old element
				$DB->execute('
				    DELETE
				         FROM nv_properties_items
                        WHERE property_id = '.protect($property->id).'
                          AND element = '.protect($item_type).'
                          AND node_id = '.protect($item_id).'
                          AND website = '.$website->id
                );

				// now we insert a new row
				$DB->execute('
				    INSERT INTO nv_properties_items
					    (id, website, property_id, element, node_id, name, value)
					VALUES
					    (   0,
							:website,
							:property_id,
							:type,
							:item_id,
							:name,
							:value
                        )',
                    array(
                        ':website' => $website->id,
                        ':property_id' => $property->id,
                        ':type' => $item_type,
                        ':item_id' => $item_id,
                        ':name' => $property->name,
                        ':value' => $_REQUEST['property-'.$property->id]
                    )
                );

				// set the dictionary for the multilanguage properties
                $default_language = '';
                if($property->multilanguage == 'false' || $property->multilanguage === false)
                    $default_language = $website->languages_list[0];

				if(in_array($property->type, array('text', 'textarea', 'rich_textarea')) || @$property->multilanguage=='true')
				{
					foreach($website->languages_list as $lang)
					{
                        if(!empty($default_language))   // property is NOT multilanguage, use the first value for all languages
                            $_REQUEST['property-'.$property->id.'-'.$lang] = $_REQUEST['property-'.$property->id.'-'.$default_language];
						$dictionary[$lang]['property-'.$property->id.'-'.$lang] = $_REQUEST['property-'.$property->id.'-'.$lang];
					}					
				}
                else if($property->type == 'link')
                {
                    foreach($website->languages_list as $lang)
                    {
                        $link = $_REQUEST['property-'.$property->id.'-'.$lang.'-link'].
                                    '##'.$_REQUEST['property-'.$property->id.'-'.$lang.'-title'].
                                    '##'.$_REQUEST['property-'.$property->id.'-'.$lang.'-target'];

                        $dictionary[$lang]['property-'.$property->id.'-'.$lang] = $link;

                        if(!empty($default_language))   // property is NOT multilanguage, use the first value for all languages
                            $dictionary[$lang]['property-'.$property->id.'-'.$lang] = $dictionary[$lang]['property-'.$property->id.'-'.$default_language];
                    }
                }
            /*
                        }
                        else
                        {
                            // remove the property value assigned (if any)
                            $DB->execute('DELETE FROM nv_properties_items
                                                WHERE property_id = '.protect($property->id).'
                                                  AND element = '.protect($item_type).'
                                                  AND node_id = '.protect($item_id));
                        }
            */
		}
		
		if(!empty($dictionary))
			webdictionary::save_element_strings('property-'.$_REQUEST['property-element'], $item_id, $dictionary);

        return true;
	}

    // save properties from an associative array (ID => VALUE)
    // multilanguage values (ID => array(LANG => VALUE, LANG => VALUE...)
    // moption values (ID => array(x,y,z...)
    // dates => timestamps
    // coordinates (ID => array("latitude" => ..., "longitude" => ...)
    // change only the given properties, not the other existing ones
    public static function save_properties_from_array($item_type, $item_id, $template, $properties_assoc=array())
   	{
   		global $DB;
   		global $website;

   		$dictionary = array();

   		// load properties associated with the element type
   		$properties = property::elements($template, $item_type);

        if(!is_array($properties))
            $properties = array();

        foreach($properties as $property)
   		{
            if(!isset($properties_assoc[$property->name]) && !isset($properties_assoc[$property->id]))
                continue;

            $values_dict = array();

            // we try to find the property value by "property name", if empty then we try to find it via "property id"
            $value = $properties_assoc[$property->name];

            if(empty($value))
                $value = $properties_assoc[$property->id];

            // multilanguage property?
            if(in_array($property->type, array('text', 'textarea', 'link', 'rich_textarea')) || @$property->multilanguage=='true')
            {
                $values_dict = $properties_assoc[$property->name];
                if(empty($values_dict))
                    $values_dict = $properties_assoc[$property->id];

                $value = '[dictionary]';
            }

            if($property->type=='moption' && !empty($_REQUEST['property-'.$property->id]))
                $value = implode(',', $value);

            if($property->type=='coordinates')
                $value = $value['latitude'].'#'.$value['longitude'];

            if($property->type=='webuser_groups' && !empty($value))
                $value = 'g'.implode(',g', $value);

            // boolean (checkbox): if not checked,  form does not send the value
            if($property->type=='boolean' && empty($value))
                $value = 0;

               // remove the old element
            $DB->execute('DELETE FROM nv_properties_items
                            WHERE property_id = '.protect($property->id).'
                              AND element = '.protect($item_type).'
                              AND node_id = '.protect($item_id).'
                              AND website = '.$website->id);

            // now we insert a new row
            $DB->execute('
				    INSERT INTO nv_properties_items
					    (id, website, property_id, element, node_id, name, value)
					VALUES
					    (   0,
							:website,
							:property_id,
							:type,
							:item_id,
							:name,
							:value
                        )',
                array(
                    ':website' => $website->id,
                    ':property_id' => $property->id,
                    ':type' => $item_type,
                    ':item_id' => $item_id,
                    ':name' => $property->name,
                    ':value' => $value
                )
            );

            // set the dictionary for the multilanguage properties
            $default_language = '';
            if($property->multilanguage == 'false' || $property->multilanguage === false)
                $default_language = $website->languages_list[0];

            if(in_array($property->type, array('text', 'textarea', 'rich_textarea')) || @$property->multilanguage=='true')
            {
                foreach($website->languages_list as $lang)
                {
                    if(!empty($default_language))   // property is NOT multilanguage, use the first value for all languages
                        $_REQUEST['property-'.$property->id.'-'.$lang] = $_REQUEST['property-'.$property->id.'-'.$default_language];

                    $dictionary[$lang]['property-'.$property->id.'-'.$lang] = $_REQUEST['property-'.$property->id.'-'.$lang];
                }
            }
            else if($property->type == 'link')
            {
                foreach($website->languages_list as $lang)
                {
                    $link = $_REQUEST['property-'.$property->id.'-'.$lang.'-link'].
                        '##'.$_REQUEST['property-'.$property->id.'-'.$lang.'-title'].
                        '##'.$_REQUEST['property-'.$property->id.'-'.$lang.'-target'];

                    $dictionary[$lang]['property-'.$property->id.'-'.$lang] = $link;

                    if(!empty($default_language))   // property is NOT multilanguage, use the first value for all languages
                        $dictionary[$lang]['property-'.$property->id.'-'.$lang] = $dictionary[$lang]['property-'.$property->id.'-'.$default_language];
                }
            }

   		}

   		if(!empty($dictionary))
   			webdictionary::save_element_strings('property-'.$item_type, $item_id, $dictionary);

       return true;
   	}

    // modify a single property
    public static function change($item_type, $item_id, $property_name, $property_value, $template="")
    {
        global $DB;
        global $website;

        if(empty($template))
        {
            // discover the template associated with the element
            $x = new $item_type();
            $x->load($item_id);
            $template = $x->template;
        }

        // retrieve the property object
        $rs = $DB->query('SELECT * FROM nv_properties
                            WHERE website = '.protect($website->id).'
                              AND template = '.protect($template).'
                              AND element = '.protect($item_type).'
                              AND name = '.protect($property_name).'
                            LIMIT 1');

        $rs = $DB->result();

        $property = new property();
        $property->load_from_resultset($rs);

        // delete previous assigned property
        // remove the old element
        $DB->execute('DELETE FROM nv_properties_items
                       WHERE property_id = '.protect($property->id).'
                         AND element = '.protect($item_type).'
                         AND node_id = '.protect($item_id).'
                         AND name = '.protect($property->name).'
                         AND website = '.$website->id);

        $value = $property_value;

        // multilanguage property?
        if(in_array($property->type, array('text', 'textarea', 'link', 'rich_textarea')) || $property->multilanguage=='true')
        {
            $values_dict = $value;
            $value = '[dictionary]';
            $dictionary = array();

            foreach($website->languages_list as $lang)
            {
                $dictionary[$lang]['property-'.$property->id.'-'.$lang] = $values_dict[$lang];
            }

            webdictionary::save_element_strings($item_type, $item_id, $dictionary);
   		}

        if($property->type=='moption' && !empty($_REQUEST['property-'.$property->id]))
            $value = implode(',', $value);

        if($property->type=='coordinates')
            $value = $value['latitude'].'#'.$value['longitude'];

        if($property->type=='webuser_groups' && !empty($value))
            $value = 'g'.implode(',g', $value);


        // now we insert a new row
        $DB->execute('INSERT INTO nv_properties_items
                       (id, website, property_id, element, node_id, name, value)
                       VALUES
                       (0,
                        '.$website->id.',
                        '.protect($property->id).',
                        '.protect($item_type).',
                        '.protect($item_id).',
                        '.protect($property->name).',
                        '.protect($value).'
                       )');

        return true;

    }

    public static function remove_properties($element_type, $element_id)
    {
        global $DB;
        global $website;

        webdictionary::save_element_strings('property-'.$element_type, $element_id, array());

        $DB->execute('
            DELETE FROM nv_properties_items
                  WHERE website = '.$website->id.'
                    AND element = '.protect($element_type).'
                    AND node_id = '.intval($element_id).'
        ');
    }

	public static function countries($lang="", $alpha3=false)
	{
		global $DB;
		
		// static function can be called from navigate or from a webget (user then is not a navigate user)
		if(empty($lang)) 
		{
			global $user;
			$lang = $user->language;
		}

        $code = 'country_code';
        if($alpha3)
            $code = 'alpha3';

		$DB->query('SELECT '.$code.' AS country_code, name
					FROM nv_countries
		 			WHERE lang = '.protect($lang).'
					ORDER BY name ASC');
					
		$rs = $DB->result();
		
		if(empty($rs))
		{
			// failback, load English names	
			$DB->query('SELECT '.$code.' AS country_code, name
						FROM nv_countries
						WHERE lang = "en"
						ORDER BY name ASC');
						
			$rs = $DB->result();
		}
		
		$out = array();
		
		foreach($rs as $country)
		{
			$out[$country->country_code] = $country->name;	
		}
		
		return $out;
	}

    public static function languages()
	{
		global $DB;

        $DB->query('SELECT code, name FROM nv_languages');
        $languages_rs = $DB->result();
        $out = array();

        foreach($languages_rs as $lang)
            $out[$lang->code] = $lang->name;

		return $out;
	}
	
	public static function timezones($country=NULL, $lang="")
	{
		$out = array();
		
		if(!empty($country))
			$timezone_identifiers = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, strtoupper($country));
		else
			$timezone_identifiers = DateTimeZone::listIdentifiers(); // DateTimeZone::ALL
			
		foreach( $timezone_identifiers as $value )
		{
			//if ( preg_match( '/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value ) || true )
			//{
				$ex = explode("/", $value, 2); // obtain continent, city	

				$this_tz = new DateTimeZone($value);
				$now = new DateTime("now", $this_tz);
				$offset = $this_tz->getOffset($now);
				$utc = $offset / 3600;
										
				if($utc > 0) $utc = '+'.$utc;
				else if($utc == 0) $utc = '-'.$utc;

				$continent = $ex[0];	 

				switch($continent)
				{
					case 'Africa':
						$continent = t(284, 'Africa');
						break;

					case 'America':
						$continent = t(310, 'America');
						break;
						
					case 'Antartica':
						$continent = t(311, 'Antartica');
						break;						

					case 'Arctic':
						$continent = t(312, 'Arctic');
						break;						

					case 'Asia':
						$continent = t(313, 'Asia');
						break;						

					case 'Atlantic':
						$continent = t(314, 'Atlantic');
						break;						

					case 'Europe':
						$continent = t(315, 'Europe');
						break;						

					case 'Indian':
						$continent = t(316, 'Indian');
						break;						

					case 'Pacific':
						$continent = t(317, 'Pacific');
						break;	
						
					default:
						// leave it in english
				}
				$city = str_replace('_', ' ', $ex[1]);
				
				if(!empty($city))			
					$out[$value] = $offset.'#'.'(UTC'.$utc.') '.$continent.'/'.$city;
				else
					$out[$value] = $offset.'#'.'(UTC'.$utc.') '.$value;
			//}
		}		
		
		asort($out, SORT_NUMERIC);
		
		$rows = array();
		
		foreach($out as $value => $text)
		{
			$rows[$value] = substr($text, strpos($text, '#')+1);
		}
		
		return $rows;
	}

    public static function find($type, $property, $value)
    {
        global $DB;
        global $website;

        $DB->query('
            SELECT * FROM nv_properties_items
            WHERE website = '.protect($website->id).'
              AND property_id = '.protect($property).'
              AND value = '.protect($value),
            'object');

        return $DB->result();
    }
	
    public function backup($type='json')
    {
        global $DB;
        global $website;

        $out = array();

        $DB->query('SELECT * FROM nv_properties WHERE website = '.protect($website->id), 'object');

        if($type='json')
            $out['nv_properties'] = json_encode($DB->result());

        $DB->query('SELECT * FROM nv_properties_items WHERE website = '.protect($website->id), 'object');

        if($type='json')
            $out['nv_properties_items'] = json_encode($DB->result());

        if($type='json')
            $out = json_encode($out);

        return $out;
    }
}
?>